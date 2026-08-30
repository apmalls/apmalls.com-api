<?php

namespace App\Repositories\Setting;



use App\Models\Setting\GeneralSetting;
use App\Repositories\Contracts\GeneralSettingRepositoryInterface;

class GeneralSettingRepository implements GeneralSettingRepositoryInterface
{
    public function get(): GeneralSetting
    {
        return GeneralSetting::firstOrCreate(
            ['id' => 1],
            $this->defaults()
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


    public function getForUpdate(): GeneralSetting
    {
        return GeneralSetting::query()
            ->lockForUpdate()
            ->firstOrCreate([
                'id' => 1,
            ], $this->defaults());
    }

    private function defaults(): array
    {
        return [
            'company_name' => 'AP Malls',
            'currency_name' => 'Indian Rupee',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'default_tax' => 0,
            'barcode_type' => 'CODE128',
            'barcode_prefix' => 'PRD',
            'barcode_start_number' => 100000,
            'thermal_paper_size' => '80mm',
            'auto_print_invoice' => false,
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd-m-Y',
            'time_format' => 'H:i',
            'status' => true,
        ];
    }

}
