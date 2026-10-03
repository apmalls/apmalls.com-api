<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            Schema::create('website_offers', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('sub_title')->nullable();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('desktop_image')->nullable();
                $table->string('mobile_image')->nullable();
                $table->enum('type', ['image', 'video'])->default('image');
                $table->string('display_mode', 32)->default('image_with_text');
                $table->text('video_url')->nullable();
                $table->string('position')->default('offer');
                $table->string('button_text')->nullable();
                $table->text('button_url')->nullable();
                $table->boolean('open_new_tab')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->dateTime('start_date')->nullable();
                $table->dateTime('end_date')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['status', 'sort_order']);
                $table->index(['start_date', 'end_date']);
            });

            // Move database records only; uploaded files keep their original paths.
            DB::table('website_banners')->where('banner_type', 'offer')->orderBy('id')
                ->lockForUpdate()->chunkById(200, function ($records): void {
                    $rows = $records->map(function ($record): array {
                        $row = (array) $record;
                        unset($row['banner_type']);
                        return $row;
                    })->all();
                    DB::table('website_offers')->insert($rows);
                    DB::table('website_banners')->whereIn('id', $records->pluck('id'))->delete();
                });
            $this->synchronizeSequence('website_offers');
            $this->copyPermissions();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            // Check every collision before restoring or removing any records.
            $conflict = DB::table('website_offers as offers')
                ->join('website_banners as banners', function ($join): void {
                    $join->on('offers.id', '=', 'banners.id')->orOn('offers.slug', '=', 'banners.slug');
                })->exists();
            if ($conflict) {
                throw new RuntimeException('Cannot restore website offers: a banner ID or slug conflicts. Resolve the conflict before rolling back; no offers were removed.');
            }

            DB::table('website_offers')->orderBy('id')->lockForUpdate()
                ->chunkById(200, function ($records): void {
                    DB::table('website_banners')->insert($records->map(
                        fn ($record): array => (array) $record + ['banner_type' => 'offer']
                    )->all());
                });
            $this->synchronizeSequence('website_banners');
            Schema::drop('website_offers');
            $tables = config('permission.table_names');
            $ids = DB::table($tables['permissions'])->where('name', 'like', 'website-offer.%')->pluck('id');
            DB::table($tables['role_has_permissions'])->whereIn('permission_id', $ids)->delete();
            DB::table($tables['model_has_permissions'])->whereIn('permission_id', $ids)->delete();
            DB::table($tables['permissions'])->whereIn('id', $ids)->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function synchronizeSequence(string $table): void
    {
        if (DB::getDriverName() !== 'pgsql') return;
        $maximum = (int) DB::table($table)->max('id');
        DB::select("SELECT setval(pg_get_serial_sequence(?, 'id'), ?, ?)", [
            $table, max(1, $maximum), $maximum > 0 ? 'true' : 'false',
        ]);
    }

    private function copyPermissions(): void
    {
        $tables = config('permission.table_names');
        foreach (['list', 'view', 'create', 'update', 'delete', 'restore', 'force-delete', 'change-status'] as $action) {
            // Create even when a fresh install has not seeded banner permissions yet.
            DB::table($tables['permissions'])->insertOrIgnore([
                'name' => "website-offer.{$action}", 'guard_name' => 'web',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $destination = DB::table($tables['permissions'])->where('name', "website-offer.{$action}")->where('guard_name', 'web')->value('id');
            foreach (DB::table($tables['permissions'])->where('name', "website-banner.{$action}")->where('guard_name', 'web')->get() as $source) {
                foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
                    foreach (DB::table($tables[$pivot])->where('permission_id', $source->id)->get() as $grant) {
                        $row = (array) $grant;
                        $row['permission_id'] = $destination;
                        DB::table($tables[$pivot])->insertOrIgnore($row);
                    }
                }
            }
        }
    }
};
