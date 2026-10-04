<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Admin\Barcode\StoreBarcodeTemplateRequest;
use App\Http\Requests\Admin\Barcode\UpdateBarcodeTemplateRequest;
use App\Http\Resources\Barcode\BarcodeTemplateResource;
use App\Models\Barcode\BarcodeTemplate;
use App\Models\Product\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BarcodeTemplateProductDatesTest extends TestCase
{
    public function test_date_flags_are_optional_boolean_template_fields(): void
    {
        $this->assertFalse($this->dateFlagValidator(StoreBarcodeTemplateRequest::class, [])->fails());
        $this->assertFalse($this->dateFlagValidator(UpdateBarcodeTemplateRequest::class, [
            'show_manufacture_date' => true,
            'show_expiry_date' => false,
        ])->fails());

        $invalid = $this->dateFlagValidator(StoreBarcodeTemplateRequest::class, [
            'show_manufacture_date' => 'yes',
            'show_expiry_date' => 'no',
        ]);

        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('show_manufacture_date', $invalid->errors()->toArray());
        $this->assertArrayHasKey('show_expiry_date', $invalid->errors()->toArray());
    }

    public function test_resource_serializes_both_date_flags(): void
    {
        $template = $this->template([
            'show_manufacture_date' => true,
            'show_expiry_date' => false,
        ]);

        $payload = (new BarcodeTemplateResource($template))->resolve();

        $this->assertTrue($payload['show_manufacture_date']);
        $this->assertFalse($payload['show_expiry_date']);
    }

    public function test_preview_and_pdf_print_enabled_dates_in_day_month_year_format(): void
    {
        $template = $this->template([
            'show_name' => true,
            'show_price' => true,
            'show_sku' => true,
            'show_manufacture_date' => true,
            'show_expiry_date' => true,
        ]);
        $product = $this->product('2026-09-01', '2027-08-31');

        foreach (['barcode.preview', 'barcode.pdf'] as $view) {
            $html = view($view, ['template' => $template, 'items' => [$product]])->render();

            $this->assertStringContainsString('MFG: 01/09/2026', $html);
            $this->assertStringContainsString('EXP: 31/08/2027', $html);
            $this->assertStringContainsString('gap: 0.3mm', $html);
            $this->assertLessThan(strpos($html, 'MFG:'), strpos($html, 'SKU-TEST'));
            $this->assertLessThan(strpos($html, 'EXP:'), strpos($html, 'MFG:'));
        }
    }

    public function test_missing_or_disabled_product_dates_do_not_print_placeholder_lines(): void
    {
        $enabledTemplate = $this->template([
            'show_manufacture_date' => true,
            'show_expiry_date' => true,
        ]);
        $disabledTemplate = $this->template();
        $datedProduct = $this->product('2026-09-01', '2027-08-31');
        $undatedProduct = $this->product(null, null);

        $missingDates = view('barcode.preview', [
            'template' => $enabledTemplate,
            'items' => [$undatedProduct],
        ])->render();
        $disabledDates = view('barcode.preview', [
            'template' => $disabledTemplate,
            'items' => [$datedProduct],
        ])->render();

        $this->assertStringNotContainsString('MFG:', $missingDates);
        $this->assertStringNotContainsString('EXP:', $missingDates);
        $this->assertStringNotContainsString('MFG:', $disabledDates);
        $this->assertStringNotContainsString('EXP:', $disabledDates);
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, bool|string>  $payload
     */
    private function dateFlagValidator(string $requestClass, array $payload): \Illuminate\Contracts\Validation\Validator
    {
        /** @var FormRequest $request */
        $request = $requestClass::create('/', 'POST', $payload);
        $rules = Arr::only($request->rules(), [
            'show_manufacture_date',
            'show_expiry_date',
        ]);

        return Validator::make($payload, $rules);
    }

    /**
     * @param  array<string, bool|int|string>  $overrides
     */
    private function template(array $overrides = []): BarcodeTemplate
    {
        $template = new BarcodeTemplate();
        $template->setRawAttributes(array_merge([
            'id' => 1,
            'name' => 'Date label',
            'paper_size' => '40x30',
            'width' => 40,
            'height' => 30,
            'font_size' => 10,
            'show_name' => false,
            'show_price' => false,
            'show_sku' => false,
            'show_manufacture_date' => false,
            'show_expiry_date' => false,
            'show_barcode' => false,
            'show_qr' => false,
            'status' => true,
        ], $overrides), true);

        return $template;
    }

    private function product(?string $manufactureDate, ?string $expiryDate): Product
    {
        $product = new Product();
        $product->setRawAttributes([
            'id' => 1,
            'name' => 'Dated product',
            'sku' => 'SKU-TEST',
            'stock' => 0,
            'selling_price' => '100.00',
            'manufacture_date' => $manufactureDate,
            'expiry_date' => $expiryDate,
        ], true);

        return $product;
    }
}
