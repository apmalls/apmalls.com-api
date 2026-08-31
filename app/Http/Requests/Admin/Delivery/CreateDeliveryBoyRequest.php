<?php

namespace App\Http\Requests\Admin\Delivery;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDeliveryBoyRequest extends FormRequest
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
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:delivery_boys,user_id'],
            'first_name' => ['required_without:user_id', 'nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required_without:user_id', 'nullable', 'email', 'unique:users,email'],
            'password' => ['required_without:user_id', 'nullable', 'string', 'min:8', 'confirmed'],

            'employee_code' => ['required', 'string', 'max:50', 'unique:delivery_boys,employee_code'],

            'phone' => [
                'required', 'string', 'max:20', 'unique:delivery_boys,phone',
                Rule::unique('users', 'mobile')->ignore($this->integer('user_id')),
            ],
            'alternate_phone' => ['nullable', 'string', 'max:20'],

            'vehicle_type' => ['required', 'string', 'max:50'],
            'vehicle_number' => ['required', 'string', 'max:50'],

            'license_number' => ['required', 'string', 'max:100'],
            'aadhaar_no' => ['required', 'digits:12', 'unique:delivery_boys,aadhaar_no'],
            'pan_no' => ['required', 'string', 'size:10', 'unique:delivery_boys,pan_no'],

            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

            'address' => ['required', 'string'],

            'is_available' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
