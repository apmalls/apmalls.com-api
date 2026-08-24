<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Contracts\GoogleAuthServiceInterface;
use Illuminate\Http\RedirectResponse;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleAuthServiceInterface $googleAuthService
    ) {
    }

    /**
     * Redirect Google Login.
     */
    public function redirect(): RedirectResponse
    {
        return $this->googleAuthService->redirect();
    }

    /**
     * Google Callback.
     */
    public function callback(): RedirectResponse
    {
        return $this->googleAuthService->callback();
    }
}
