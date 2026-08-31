<?php

namespace App\Http\Requests\Website\Checkout;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->customer()->exists() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = (int) ($this->user()?->customer?->id ?? 0);
        $ownedAddress = Rule::exists('customer_addresses', 'id')
            ->where(fn ($query) => $query
                ->where('customer_id', $customerId)
                ->whereNull('deleted_at'));

        return [

            'billing_address_id' => [
                'required',
                'integer',
                $ownedAddress,
            ],

            'shipping_address_id' => [
                'required',
                'integer',
                $ownedAddress,
            ],

            'payment_mode_id' => [
                'required',
                'integer',
                Rule::exists('payment_modes', 'id')
                    ->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at')),
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:500',
            ],

        ];
    }
}
