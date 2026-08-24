<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use Illuminate\Http\RedirectResponse;

interface GoogleAuthServiceInterface
{
    /**
     * Redirect user to Google.
     */
    public function redirect(): RedirectResponse;

    /**
     * Handle Google callback.
     */
    public function callback(): RedirectResponse;
}
