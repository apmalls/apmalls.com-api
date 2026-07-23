<?php

namespace App\Services\Contracts;

use App\Models\Setting\GeneralSetting;



interface GeneralSettingServiceInterface
{
    public function get(): GeneralSetting;

    public function update(
        array $data
    ): GeneralSetting;
}
