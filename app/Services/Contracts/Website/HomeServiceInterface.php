<?php

namespace App\Services\Contracts\Website;

interface HomeServiceInterface
{/**
 * Get website home page data.
 *
 * @return array<string, mixed>
 */
    public function index(): array;
}
