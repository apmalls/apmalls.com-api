<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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

            'user_id' => [
                'nullable',
                'exists:users,id',
            ],

            'customer_type' => [
                'required',
                'in:Retail,Wholesale,Walk-in',
            ],

            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mobile' => [
                'required',
                'string',
                'max:20',
                'unique:customers,mobile',
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
                'unique:customers,email',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'gst_number' => [
                'nullable',
                'string',
                'max:20',
                'unique:customers,gst_number',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'anniversary_date' => [
                'nullable',
                'date',
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

            'reward_points' => [
                'nullable',
                'integer',
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
     * Custom Messages
     */
    public function messages(): array
    {
        return [

            'customer_type.required' => 'Customer type is required.',

            'customer_type.in' => 'Invalid customer type.',

            'first_name.required' => 'First name is required.',

            'mobile.required' => 'Mobile number is required.',

            'mobile.unique' => 'Mobile number already exists.',

            'email.email' => 'Please enter a valid email address.',

            'email.unique' => 'Email already exists.',

            'gst_number.unique' => 'GST number already exists.',

            'is_active.required' => 'Status is required.',

        ];
    }
}
