<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface DashboardRepositoryInterface
{
    public function getDashboard(User $user): array;
}
