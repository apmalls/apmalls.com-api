<?php

namespace App\Repositories\Setting;



use App\Models\Setting\GeneralSetting;
use App\Repositories\Contracts\GeneralSettingRepositoryInterface;

class GeneralSettingRepository implements GeneralSettingRepositoryInterface
{
    public function get(): GeneralSetting
    {
        return GeneralSetting::firstOrCreate(
            ['id' => 1]
        );
    }

    public function update(
        array $data
    ): GeneralSetting {

        $setting = $this->get();

        $setting->update($data);

        return $setting->fresh([
            'printer',
            'barcodeTemplate',
            'invoiceTemplate'
        ]);
    }
}
