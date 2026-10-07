<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product\Brand;
use App\Services\Brand\BrandLogoImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BrandLogoImportTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;
    private string $png;

    protected function setUp(): void
    {
        parent::setUp();
        config(['logging.default' => 'null']);
        $this->directory = sys_get_temp_dir().'/apmalls-brand-import-'.bin2hex(random_bytes(8));
        File::makeDirectory($this->directory);
        $this->png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jQioAAAAASUVORK5CYII=');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function brand(string $slug, array $values = []): Brand
    {
        return Brand::create(array_merge(['name' => $slug, 'slug' => $slug, 'description' => 'Keep this description',
            'is_active' => false, 'featured' => true], $values));
    }

    private function manifest(array $slugs): BrandLogoImporter
    {
        $entries = [];
        foreach ($slugs as $slug) {
            File::put($this->directory.'/'.$slug.'.png', $this->png);
            $entries[] = ['slug' => $slug, 'status' => 'verified', 'source_page' => 'https://official.example/',
                'asset_url' => 'https://official.example/'.$slug.'.png', 'file' => $slug.'.png',
                'sha256' => hash('sha256', $this->png), 'width' => 1, 'height' => 1];
        }
        File::put($this->directory.'/manifest.json', json_encode(['version' => 1, 'brands' => $entries]));
        return new BrandLogoImporter($this->directory.'/manifest.json');
    }

    public function test_preview_is_read_only_and_apply_changes_only_the_logo(): void
    {
        $brand = $this->brand('missing-logo');
        DB::table('brands')->where('id', $brand->id)->update(['created_at' => '2020-01-01 00:00:00', 'updated_at' => '2021-01-01 00:00:00']);
        $before = (array) DB::table('brands')->find($brand->id);
        $importer = $this->manifest(['missing-logo']);
        $this->assertSame('eligible', $importer->run()[0]['status']);
        $this->assertSame($before, (array) DB::table('brands')->find($brand->id));
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame('imported', $importer->run(true)[0]['status']);
        $after = (array) DB::table('brands')->find($brand->id);
        $this->assertSame($this->png, Storage::disk('public')->get($after['logo']));
        unset($before['logo'], $after['logo']);
        $this->assertSame($before, $after, 'All metadata, featured flags, audit fields and timestamps must be preserved.');
        $this->assertSame('existing', $importer->run(true)[0]['status']);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_blank_logos_import_but_uploads_trashed_and_unknown_brands_are_preserved(): void
    {
        $blank = $this->brand('blank-logo', ['logo' => '   ']);
        $uploaded = $this->brand('uploaded', ['logo' => 'brands/staff.png']);
        Storage::disk('public')->put('brands/staff.png', 'staff asset');
        $trashed = $this->brand('trashed');
        $trashed->delete();
        $count = Brand::withTrashed()->count();
        $rows = $this->manifest(['blank-logo', 'uploaded', 'trashed', 'unknown'])->run(true);
        $this->assertSame(['imported', 'existing', 'trashed', 'missing'], array_column($rows, 'status'));
        $this->assertNotSame('   ', $blank->fresh()->logo);
        $this->assertSame('brands/staff.png', $uploaded->fresh()->logo);
        $this->assertSame('staff asset', Storage::disk('public')->get('brands/staff.png'));
        $this->assertNull(Brand::withTrashed()->find($trashed->id)->logo);
        $this->assertSame($count, Brand::withTrashed()->count());
    }

    public function test_entire_bundle_is_validated_before_any_write(): void
    {
        $brand = $this->brand('first');
        $importer = $this->manifest(['first', 'second']);
        File::put($this->directory.'/second.png', 'corrupted');
        try {
            $importer->run(true);
            $this->fail('A corrupted asset must stop the import.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('checksum or dimensions', $error->getMessage());
        }
        $this->assertNull($brand->fresh()->logo);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_storage_failure_cleans_partial_files_and_leaves_brand_unchanged(): void
    {
        $brand = $this->brand('storage-failure');
        $importer = $this->manifest(['storage-failure']);
        $disk = Storage::disk('public');
        $proxy = \Mockery::mock($disk)->makePartial();
        $destination = null;
        $proxy->shouldReceive('put')->once()->andReturnUsing(function ($path, $bytes) use ($disk, &$destination) {
            $destination = $path;
            $disk->put($path, $bytes);
            return false;
        });
        $proxy->shouldReceive('delete')->once()->andReturnUsing(fn ($path) => $disk->delete($path));
        Storage::shouldReceive('disk')->with('public')->andReturn($proxy);
        $this->assertSame('failed', $importer->run(true)[0]['status']);
        $this->assertNull($brand->fresh()->logo);
        $disk->assertMissing($destination);
    }

    public function test_eligibility_is_rechecked_if_logo_changes_during_copy(): void
    {
        $brand = $this->brand('changed');
        $importer = $this->manifest(['changed']);
        $disk = Storage::disk('public');
        $proxy = \Mockery::mock($disk)->makePartial();
        $proxy->shouldReceive('put')->once()->andReturnUsing(function ($path, $bytes) use ($disk, $brand) {
            $disk->put($path, $bytes);
            // Simulate a write between the initial eligibility read and conditional update.
            DB::table('brands')->where('id', $brand->id)->update(['logo' => 'brands/new-staff-upload.png']);
            return true;
        });
        $proxy->shouldReceive('delete')->once()->andReturnUsing(fn ($path) => $disk->delete($path));
        Storage::shouldReceive('disk')->with('public')->andReturn($proxy);
        $this->assertSame('failed', $importer->run(true)[0]['status']);
        $this->assertSame([], $disk->allFiles());
        $this->assertNull($brand->fresh()->logo, 'The whole simulated transaction rolls back; no imported path remains.');
    }

    public function test_command_defaults_to_preview_and_requires_confirmation_to_apply(): void
    {
        $importer = $this->manifest(['tata']);
        $this->brand('tata');
        $this->app->instance(BrandLogoImporter::class, $importer);
        $status = \Illuminate\Support\Facades\Artisan::call('brands:import-logos');
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('Preview only', $output);
        $this->assertSame(1, $this->applyCommand('no'));
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, $this->applyCommand('yes'));
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    private function applyCommand(string $answer): int
    {
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--apply' => true]);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $answer."\n");
        rewind($stream);
        $input->setStream($stream);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        try {
            $status = \Illuminate\Support\Facades\Artisan::all()['brands:import-logos']->run($input, $output);
            $this->assertStringNotContainsString('Import stopped:', $output->fetch());
            return $status;
        } finally {
            fclose($stream);
        }
    }

    public function test_reviewed_bundle_has_unique_slugs_and_valid_integrity(): void
    {
        // Bundle validation runs even when no brands exist, without contacting official sites.
        $results = (new BrandLogoImporter())->run();
        $this->assertCount(25, $results);
        $this->assertCount(25, array_unique(array_column($results, 'slug')));
        $this->assertNotContains('eligible', array_column($results, 'status'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
