<?php

namespace App\Services\Setting;

use App\Models\Setting\GeneralSetting;
use App\Repositories\Contracts\GeneralSettingRepositoryInterface;
use App\Services\Contracts\GeneralSettingServiceInterface;
use Illuminate\Support\Facades\DB;

class GeneralSettingService implements GeneralSettingServiceInterface
{
    public function __construct(
        protected GeneralSettingRepositoryInterface $repository
    ) {
    }

    public function get(): GeneralSetting
    {
        return $this->repository->get();
    }

    public function update(
        array $data
    ): GeneralSetting {

        return DB::transaction(function () use ($data) {

            return $this->repository->update($data);

        });
    }
}
