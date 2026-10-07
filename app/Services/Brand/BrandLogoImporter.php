<?php

declare(strict_types=1);

namespace App\Services\Brand;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BrandLogoImporter
{
    public function __construct(private ?string $manifestPath = null) {}

    /** @return array<int, array{slug: string, status: string, detail: string}> */
    public function run(bool $apply = false): array
    {
        $manifestPath = $this->manifestPath ?? resource_path('brand-logos/manifest.json');
        if (! is_file($manifestPath)) {
            throw new RuntimeException('Official brand-logo manifest not found.');
        }
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['version'] ?? null) !== 1 || ! is_array($manifest['brands'] ?? null)) {
            throw new RuntimeException('Invalid official brand-logo manifest.');
        }

        // Validate the complete reviewed bundle before touching any database records or files.
        $assets = [];
        $seen = [];
        foreach ($manifest['brands'] as $entry) {
            $slug = $entry['slug'] ?? '';
            if (! is_string($slug) || ! preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) || isset($seen[$slug])) {
                throw new RuntimeException('Invalid or duplicate manifest brand slug.');
            }
            $seen[$slug] = true;
            if (($entry['status'] ?? null) === 'unresolved') {
                continue;
            }
            if (($entry['status'] ?? null) !== 'verified'
                || ($entry['file'] ?? null) !== $slug.'.png'
                || ! preg_match('/\A[a-f0-9]{64}\z/', $entry['sha256'] ?? '')
                || ! str_starts_with($entry['source_page'] ?? '', 'https://')
                || ! str_starts_with($entry['asset_url'] ?? '', 'https://')) {
                throw new RuntimeException("Invalid logo manifest entry: {$slug}.");
            }
            $file = dirname($manifestPath).'/'.$entry['file'];
            if (! is_file($file) || is_link($file) || filesize($file) > 2 * 1024 * 1024) {
                throw new RuntimeException("Missing or invalid bundled logo: {$slug}.");
            }
            $bytes = file_get_contents($file);
            if (! hash_equals($entry['sha256'], hash('sha256', $bytes))) {
                throw new RuntimeException("Logo checksum or dimensions do not match: {$slug}.");
            }
            $dimensions = getimagesizefromstring($bytes);
            if (! $dimensions || $dimensions[2] !== IMAGETYPE_PNG
                || $dimensions[0] !== ($entry['width'] ?? null)
                || $dimensions[1] !== ($entry['height'] ?? null)
                || max($dimensions[0], $dimensions[1]) > 640) {
                throw new RuntimeException("Logo checksum or dimensions do not match: {$slug}.");
            }
            $assets[$slug] = $bytes;
        }

        $results = [];
        foreach ($manifest['brands'] as $entry) {
            $slug = $entry['slug'];
            $row = ['slug' => $slug, 'status' => 'unresolved', 'detail' => $entry['reason'] ?? 'No verified official asset.'];
            if (! isset($assets[$slug])) {
                $results[] = $row;
                continue;
            }
            $destination = null;
            $disk = Storage::disk('public');
            try {
                $row = DB::transaction(function () use ($apply, $slug, $entry, $assets, $disk, &$destination) {
                    $query = DB::table('brands')->where('slug', $slug);
                    $brand = ($apply ? $query->lockForUpdate() : $query)->first();
                    if (! $brand) {
                        return ['slug' => $slug, 'status' => 'missing', 'detail' => 'No matching brand; no record created.'];
                    }
                    if ($brand->deleted_at !== null) {
                        return ['slug' => $slug, 'status' => 'trashed', 'detail' => 'Trashed brand unchanged.'];
                    }
                    if ($brand->logo !== null && trim($brand->logo) !== '') {
                        return ['slug' => $slug, 'status' => 'existing', 'detail' => 'Existing logo preserved.'];
                    }
                    if (! $apply) {
                        return ['slug' => $slug, 'status' => 'eligible', 'detail' => 'Missing logo would be populated.'];
                    }

                    $destination = 'brands/official/'.$slug.'-'.substr($entry['sha256'], 0, 12).'-'.Str::uuid().'.png';
                    if (! $disk->put($destination, $assets[$slug])) {
                        throw new RuntimeException('Public storage write failed.');
                    }
                    // Conditional update protects against an intervening staff upload and preserves timestamps/audit fields.
                    $updated = DB::table('brands')->where('id', $brand->id)->whereNull('deleted_at')
                        ->where('slug', $slug)->where(function ($query) {
                            $query->whereNull('logo')->orWhereRaw("TRIM(logo) = ''");
                        })->update(['logo' => $destination]);
                    if ($updated !== 1) {
                        throw new RuntimeException('Brand changed during import.');
                    }
                    return ['slug' => $slug, 'status' => 'imported', 'detail' => $destination];
                });
            } catch (Throwable) {
                if ($destination !== null) {
                    try {
                        $cleaned = $disk->delete($destination);
                    } catch (Throwable) {
                        $cleaned = false;
                    }
                } else {
                    $cleaned = true;
                }
                $row = ['slug' => $slug, 'status' => 'failed', 'detail' => $cleaned
                    ? 'Import failed; brand unchanged. Correct database/storage and retry.'
                    : 'Import failed; brand unchanged. Remove orphaned file: '.$destination];
            }
            $results[] = $row;
        }
        return $results;
    }
}
