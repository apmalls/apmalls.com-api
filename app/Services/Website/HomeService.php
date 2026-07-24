<?php

namespace App\Services\Website;

use App\Repositories\Contracts\Website\HomeRepositoryInterface;
use App\Services\Contracts\Website\HomeServiceInterface;

class HomeService implements HomeServiceInterface
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected HomeRepositoryInterface $homeRepository,
    ) {}

    /**
     * Get website home page data.
     *
     * @return array<string, mixed>
     */
    public function index(): array
    {
        return $this->homeRepository->index();
    }
}
