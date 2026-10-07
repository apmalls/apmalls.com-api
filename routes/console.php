<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('brands:import-logos {--apply : Populate missing logos after target/backup confirmation}', function () {
    $connection = \Illuminate\Support\Facades\DB::connection();
    $driver = $connection->getDriverName();
    $database = $connection->getDatabaseName();
    $host = $connection->getConfig('host') ?? 'local';
    $this->info("Target: {$driver} / {$host} / {$database}");
    $apply = (bool) $this->option('apply');
    if ($apply && ! $this->confirm('Have you confirmed this database target and taken a backup?', false)) {
        $this->warn('Import not applied. Run without --apply to preview.');
        return 1;
    }
    try {
        $results = app(\App\Services\Brand\BrandLogoImporter::class)->run($apply);
    } catch (\Throwable $error) {
        $this->error('Import stopped: '.$error->getMessage());
        return 1;
    }
    $this->table(['Brand slug', 'Result', 'Details'], $results);
    $this->info($apply ? 'Missing-logo import finished. Featured selections were not changed.' : 'Preview only: no files or brand fields changed.');
    return collect($results)->contains('status', 'failed') ? 1 : 0;
})->purpose('Import verified official logos only into existing brands with missing logos');

Artisan::command('orders:retry-notifications {--id= : Retry one notification record} {--include-queued : Recover queued records too, invalidating earlier jobs}', function () {
    $query = \App\Models\Sale\OrderEmailDelivery::query()
        ->whereIn('status', $this->option('include-queued') ? ['pending', 'failed', 'blocked', 'queued'] : ['pending', 'failed', 'blocked']);
    if ($this->option('id') !== null) {
        if (! ctype_digit((string) $this->option('id')) || (int) $this->option('id') < 1) {
            $this->error('The notification ID must be a positive integer.');
            return 1;
        }
        $query->whereKey((int) $this->option('id'));
    }
    $requested = 0;
    $query->chunkById(100, function ($records) use (&$requested) {
        foreach ($records as $record) {
            if (app(\App\Services\Sale\OrderNotificationService::class)->retry($record->id, (bool) $this->option('include-queued'))) {
                $requested++;
            }
        }
    });
    $this->info("Requested {$requested} notification retries. Check ledger status and queue failures for results.");
    return 0;
})->purpose('Retry unsent order/delivery notification records without modifying orders or payments');
