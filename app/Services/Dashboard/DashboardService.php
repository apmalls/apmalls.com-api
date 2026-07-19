<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository
    ) {
    }

    public function getDashboard(User $user): array
    {
        return $this->dashboardRepository->getDashboard($user);
    }
}
