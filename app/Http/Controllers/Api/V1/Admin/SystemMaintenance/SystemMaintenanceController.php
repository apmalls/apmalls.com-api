<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\SystemMaintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class SystemMaintenanceController extends Controller
{
    public function refresh(): JsonResponse
    {
        $executed = [];
        $composerOutput = [];
        $composerStatus = null;

        try {
            Artisan::call('optimize:clear');
            $executed[] = 'optimize:clear';

            try {
                Artisan::call('permission:cache-reset');
                $executed[] = 'permission:cache-reset';
            } catch (\Throwable $e) {
            }

            try {
                Artisan::call('queue:restart');
                $executed[] = 'queue:restart';
            } catch (\Throwable $e) {
            }

            $projectRoot = base_path();

            if (PHP_OS_FAMILY === 'Windows') {
                $command = 'cd /d "' . $projectRoot . '" && composer dump-autoload -o 2>&1';
            } else {
                $command = 'cd "' . $projectRoot . '" && composer dump-autoload -o 2>&1';
            }

            exec($command, $composerOutput, $composerStatus);

            $executed[] = 'composer dump-autoload';

            return response()->json([
                'success' => true,
                'message' => 'System refresh completed successfully.',
                'executed_commands' => $executed,
                'composer_status' => $composerStatus,
                'composer_output' => $composerOutput,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'executed_commands' => $executed,
            ], 500);
        }
    }
}