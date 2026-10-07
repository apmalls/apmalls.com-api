<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
