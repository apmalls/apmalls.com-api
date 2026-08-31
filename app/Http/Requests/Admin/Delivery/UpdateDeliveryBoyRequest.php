<?php

namespace App\Http\Requests\Admin\Delivery;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryBoyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $deliveryBoyId = (int) $this->route('id');

        return [

            'employee_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('delivery_boys', 'employee_code')
                    ->ignore($deliveryBoyId),
            ],

            'phone' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('delivery_boys', 'phone')
                    ->ignore($deliveryBoyId),
            ],

            'alternate_phone' => ['nullable', 'string', 'max:20'],

            'vehicle_type' => ['sometimes', 'string', 'max:50'],

            'vehicle_number' => ['sometimes', 'string', 'max:50'],

            'license_number' => ['sometimes', 'string', 'max:100'],

            'aadhaar_no' => [
                'sometimes',
                'digits:12',
                Rule::unique('delivery_boys', 'aadhaar_no')
                    ->ignore($deliveryBoyId),
            ],

            'pan_no' => [
                'sometimes',
                'string',
                'size:10',
                Rule::unique('delivery_boys', 'pan_no')
                    ->ignore($deliveryBoyId),
            ],

            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

            'address' => ['sometimes', 'string'],

            'is_available' => ['sometimes', 'boolean'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
