<?php

namespace App\Repositories\Contracts;

use App\Models\Setting\GeneralSetting;

interface GeneralSettingRepositoryInterface
{
    public function get(): GeneralSetting;

    public function update(
        array $data
    ): GeneralSetting;
}
