<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\StaffProductResource;
use App\Models\Product\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductDateFieldsTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_product_date_validation_accepts_optional_and_historical_dates(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 12, 0, 0, 'Asia/Kolkata'));

        $this->assertDateFieldsAreValid(StoreProductRequest::class, []);
        $this->assertDateFieldsAreValid(StoreProductRequest::class, [
            'manufacture_date' => '2026-10-03',
        ]);
        $this->assertDateFieldsAreValid(StoreProductRequest::class, [
            'expiry_date' => '2025-01-01',
        ]);
        $this->assertDateFieldsAreValid(UpdateProductRequest::class, [
            'manufacture_date' => '2026-09-01',
            'expiry_date' => '2026-10-01',
        ]);
    }

    public function test_product_date_validation_rejects_future_manufacture_and_reversed_dates(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 12, 0, 0, 'Asia/Kolkata'));

        $futureManufacture = $this->dateValidator(StoreProductRequest::class, [
            'manufacture_date' => '2026-10-04',
        ]);
        $reversedDates = $this->dateValidator(UpdateProductRequest::class, [
            'manufacture_date' => '2026-10-03',
            'expiry_date' => '2026-10-02',
        ]);

        $this->assertTrue($futureManufacture->fails());
        $this->assertArrayHasKey('manufacture_date', $futureManufacture->errors()->toArray());
        $this->assertTrue($reversedDates->fails());
        $this->assertArrayHasKey('expiry_date', $reversedDates->errors()->toArray());
    }

    public function test_expiry_status_uses_the_business_date(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 23, 45, 0, 'Asia/Kolkata'));

        $this->assertSame('not_set', $this->productWithExpiry(null)->expiryStatus());
        $this->assertSame('expired', $this->productWithExpiry('2026-10-02')->expiryStatus());
        $this->assertSame('expires_today', $this->productWithExpiry('2026-10-03')->expiryStatus());
        $this->assertSame('valid', $this->productWithExpiry('2026-10-04')->expiryStatus());
    }

    public function test_product_dates_are_available_to_staff_but_not_public_resources(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 12, 0, 0, 'Asia/Kolkata'));

        $product = new Product();
        $product->setRawAttributes([
            'id' => 1,
            'name' => 'Dated product',
            'stock' => 0,
            'manufacture_date' => '2026-09-01',
            'expiry_date' => '2026-10-03',
        ], true);

        $staffPayload = (new StaffProductResource($product))->resolve();
        $publicPayload = (new ProductResource($product))->resolve();

        $this->assertSame('2026-09-01', $staffPayload['manufacture_date']);
        $this->assertSame('2026-10-03', $staffPayload['expiry_date']);
        $this->assertSame('expires_today', $staffPayload['expiry_status']);
        $this->assertArrayNotHasKey('manufacture_date', $publicPayload);
        $this->assertArrayNotHasKey('expiry_date', $publicPayload);
        $this->assertArrayNotHasKey('expiry_status', $publicPayload);
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, string>  $payload
     */
    private function assertDateFieldsAreValid(string $requestClass, array $payload): void
    {
        $this->assertFalse($this->dateValidator($requestClass, $payload)->fails());
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, string>  $payload
     */
    private function dateValidator(string $requestClass, array $payload): \Illuminate\Contracts\Validation\Validator
    {
        /** @var FormRequest $request */
        $request = $requestClass::create('/', 'POST', $payload);
        $rules = Arr::only($request->rules(), ['manufacture_date', 'expiry_date']);

        return Validator::make($payload, $rules, $request->messages());
    }

    private function productWithExpiry(?string $expiryDate): Product
    {
        $product = new Product();
        $product->setRawAttributes(['expiry_date' => $expiryDate], true);

        return $product;
    }
}
