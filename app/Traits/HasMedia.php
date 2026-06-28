<?php

namespace App\Traits;

trait HasMedia
{
    /**
     * Generate Full File URL
     */
    protected function fileUrl(?string $path): ?string
    {
        return filled($path)
            ? url('storage/' . ltrim($path, '/'))
            : null;
    }
}
