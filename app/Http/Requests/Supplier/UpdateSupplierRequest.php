<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
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
        $id = $this->route('id');

        return [

            'user_id' => [
                'nullable',
                'exists:users,id',
            ],

            'company_name' => [

                'required',

                'string',

                'max:255',

                Rule::unique('suppliers', 'company_name')->ignore($id),

            ],

            'contact_person' => [
                'required',
                'string',
                'max:255',
            ],

            'mobile' => [

                'required',

                'string',

                'max:20',

                Rule::unique('suppliers', 'mobile')->ignore($id),

            ],

            'alternate_mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [

                'nullable',

                'email',

                'max:255',

                Rule::unique('suppliers', 'email')->ignore($id),

            ],

            'gst_number' => [

                'nullable',

                'string',

                'max:20',

                Rule::unique('suppliers', 'gst_number')->ignore($id),

            ],

            'pan_number' => [

                'nullable',

                'string',

                'max:20',

                Rule::unique('suppliers', 'pan_number')->ignore($id),

            ],


            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'account_holder_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'account_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'ifsc_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'opening_balance' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'credit_limit' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

        ];
    }

    /**
     * Custom Validation Messages
     */
    public function messages(): array
    {
        return [

            'company_name.required' => 'Company name is required.',
            'company_name.unique' => 'Company name already exists.',

            'contact_person.required' => 'Contact person is required.',

            'mobile.required' => 'Mobile number is required.',
            'mobile.unique' => 'Mobile number already exists.',

            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'Email already exists.',

            'gst_number.unique' => 'GST number already exists.',

            'pan_number.unique' => 'PAN number already exists.',

            'is_active.required' => 'Status is required.',

        ];
    }
}
