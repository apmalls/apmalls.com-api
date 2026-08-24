<?php

namespace App\Models\Setting;

use App\Models\Barcode\BarcodeTemplate;
use App\Models\Invoice\InvoiceTemplate;
use App\Models\Printer\Printer;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable = [

        'company_name',
        'company_email',
        'company_phone',
        'company_website',
        'company_logo',
        'company_address',

        'currency_name',
        'currency_code',
        'currency_symbol',

        'default_tax',

        'barcode_type',
        'barcode_prefix',
        'barcode_start_number',

        'default_printer_id',
        'default_barcode_template_id',
        'default_invoice_template_id',

        'thermal_paper_size',
        'auto_print_invoice',

        'timezone',
        'date_format',
        'time_format',

        'status',

    ];

    protected $casts = [

        'auto_print_invoice' => 'boolean',
        'status' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function printer()
    {
        return $this->belongsTo(Printer::class, 'default_printer_id');
    }

    public function barcodeTemplate()
    {
        return $this->belongsTo(
            BarcodeTemplate::class,
            'default_barcode_template_id'
        );
    }

    public function invoiceTemplate()
    {
        return $this->belongsTo(
            InvoiceTemplate::class,
            'default_invoice_template_id'
        );
    }
}
