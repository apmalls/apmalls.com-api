<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait FileUploadTrait
{
    /**
     * Upload File
     */
    protected function uploadFile(
        UploadedFile $file,
        string $folder = 'uploads',
        string $disk = 'public'
    ): string {

        return $file->store($folder, $disk);
    }

    /**
     * Delete File
     */
    protected function deleteFile(
        ?string $path,
        string $disk = 'public'
    ): bool {

        if (!$path) {
            return false;
        }

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Replace File
     */
    protected function replaceFile(
        UploadedFile $file,
        ?string $oldFile,
        string $folder = 'uploads',
        string $disk = 'public'
    ): string {

        $this->deleteFile($oldFile, $disk);

        return $this->uploadFile($file, $folder, $disk);
    }
}
