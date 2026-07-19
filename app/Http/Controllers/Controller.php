<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\FileUploadTrait;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;
    use FileUploadTrait;

    /**
     * Start Transaction
     */
    protected function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * Commit Transaction
     */
    protected function commit(): void
    {
        DB::commit();
    }

    /**
     * Rollback Transaction
     */
    protected function rollback(): void
    {
        DB::rollBack();
    }

    /**
     * Handle Exception
     */
    protected function handleException(Throwable $e): JsonResponse
    {
        $statusCode = $e->getCode() >= 100 && $e->getCode() < 600
            ? $e->getCode()
            : 500;

        /*
        |--------------------------------------------------------------------------
        | Log Error in Production
        |--------------------------------------------------------------------------
        */

        if (!app()->isLocal()) {

            Log::error(static::class . ' Error: ' . $e->getMessage(), [

                'file'  => $e->getFile(),

                'line'  => $e->getLine(),

                'trace' => $e->getTraceAsString(),

            ]);

        }

        return response()->json([

            'success' => false,

            'message' => app()->isLocal()
                ? $e->getMessage()
                : 'An error occurred while processing your request.',

            'code' => $statusCode,

        ], $statusCode >= 400 && $statusCode < 600 ? $statusCode : 500);
    }

    /**
     * Cleanup Uploaded File
     */
    protected function cleanupUploadedFile(?string $filePath): void
    {
        if (!empty($filePath)) {

            $this->deleteFile($filePath);

        }
    }

    /**
     * Cleanup Multiple Uploaded Files
     */
    protected function cleanupUploadedFiles(array $files): void
    {
        foreach ($files as $file) {

            $this->cleanupUploadedFile($file);

        }
    }
}
