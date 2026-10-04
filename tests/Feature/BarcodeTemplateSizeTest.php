<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Admin\Barcode\StoreBarcodeTemplateRequest;
use App\Http\Requests\Admin\Barcode\UpdateBarcodeTemplateRequest;
use App\Models\Barcode\BarcodeTemplate;
use App\Models\Product\Product;
use App\Repositories\Contracts\BarcodeTemplateRepositoryInterface;
use App\Services\Barcode\BarcodeTemplateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class BarcodeTemplateSizeTest extends TestCase
{
    public function test_size_validation_accepts_boundaries_and_optional_compatibility_key(): void
    {
        $this->assertFalse($this->sizeValidator(StoreBarcodeTemplateRequest::class, [
            'width' => 10,
            'height' => 150,
        ])->fails());
        $this->assertFalse($this->sizeValidator(UpdateBarcodeTemplateRequest::class, [
            'paper_size' => '37x22',
            'width' => 37,
            'height' => 22,
        ])->fails());
    }

    public function test_size_validation_rejects_out_of_range_and_fractional_dimensions(): void
    {
        $tooSmall = $this->sizeValidator(StoreBarcodeTemplateRequest::class, [
            'width' => 9,
            'height' => 9,
        ]);
        $tooLarge = $this->sizeValidator(UpdateBarcodeTemplateRequest::class, [
            'width' => 151,
            'height' => 151,
        ]);
        $fractional = $this->sizeValidator(StoreBarcodeTemplateRequest::class, [
            'width' => 25.5,
            'height' => 15.5,
        ]);

        $this->assertTrue($tooSmall->fails());
        $this->assertTrue($tooLarge->fails());
        $this->assertTrue($fractional->fails());
        $this->assertArrayHasKey('width', $tooSmall->errors()->toArray());
        $this->assertArrayHasKey('height', $tooLarge->errors()->toArray());
    }

    public function test_service_derives_paper_size_from_validated_dimensions(): void
    {
        $repository = Mockery::mock(BarcodeTemplateRepositoryInterface::class);
        $service = new BarcodeTemplateService($repository);
        $normalize = new ReflectionMethod($service, 'normalizeSize');

        $payload = $normalize->invoke($service, [
            'paper_size' => 'untrusted-value',
            'width' => 37,
            'height' => 22,
        ]);

        $this->assertSame('37x22', $payload['paper_size']);
        $this->assertSame('75x50', BarcodeTemplate::sizeKey(75, 50));
    }

    public function test_micro_labels_use_compact_preview_and_pdf_styles(): void
    {
        $template = new BarcodeTemplate();
        $template->setRawAttributes([
            'id' => 1,
            'name' => 'Micro label',
            'paper_size' => '25x15',
            'width' => 25,
            'height' => 15,
            'font_size' => 10,
            'show_name' => true,
            'show_price' => true,
            'show_sku' => false,
            'show_manufacture_date' => false,
            'show_expiry_date' => false,
            'show_barcode' => false,
            'show_qr' => false,
            'status' => true,
        ], true);

        $product = new Product();
        $product->setRawAttributes([
            'id' => 1,
            'name' => 'Compact product',
            'stock' => 0,
            'selling_price' => '25.00',
        ], true);

        foreach (['barcode.preview', 'barcode.pdf'] as $view) {
            $html = view($view, ['template' => $template, 'items' => [$product]])->render();

            $this->assertStringContainsString('width: 25mm', $html);
            $this->assertStringContainsString('height: 15mm', $html);
            $this->assertStringContainsString('gap: 0.15mm', $html);
            $this->assertStringContainsString('padding: 0.6mm', $html);
        }
    }

    public function test_label_content_and_long_names_are_centered_in_preview_and_pdf(): void
    {
        $template = new BarcodeTemplate();
        $template->setRawAttributes([
            'id' => 1,
            'name' => 'Retail label',
            'paper_size' => '40x30',
            'width' => 40,
            'height' => 30,
            'font_size' => 10,
            'show_name' => true,
            'show_price' => true,
            'show_sku' => false,
            'show_manufacture_date' => false,
            'show_expiry_date' => false,
            'show_barcode' => true,
            'show_qr' => false,
            'status' => true,
        ], true);

        $product = new Product();
        $product->setRawAttributes([
            'id' => 1,
            'name' => 'Surf Excel Easy Wash Detergent Powder 1kg',
            'barcode' => '8901030753006',
            'stock' => 0,
            'selling_price' => '135.00',
        ], true);

        foreach (['barcode.preview', 'barcode.pdf'] as $view) {
            $html = view($view, ['template' => $template, 'items' => [$product]])->render();

            $this->assertStringContainsString('justify-content: center', $html);
            $this->assertStringContainsString('align-items: center', $html);
            $this->assertStringContainsString('margin: 0 auto', $html);
            $this->assertStringContainsString('class="name name-long"', $html);
            $this->assertStringContainsString($product->name, $html);
            $this->assertStringContainsString(
                '<title>Retail label - 40 x 30 mm Barcode Labels</title>',
                $html
            );
            $this->assertStringContainsString('Template: Retail label', $html);
            $this->assertStringContainsString('Label size: 40 x 30 mm', $html);
        }
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, float|int|string>  $payload
     */
    private function sizeValidator(string $requestClass, array $payload): \Illuminate\Contracts\Validation\Validator
    {
        /** @var FormRequest $request */
        $request = $requestClass::create('/', 'POST', $payload);
        $rules = Arr::only($request->rules(), ['paper_size', 'width', 'height']);

        return Validator::make($payload, $rules);
    }
}
