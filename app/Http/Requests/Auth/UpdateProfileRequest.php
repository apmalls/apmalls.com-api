<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
             'first_name' => ['required', 'string', 'max:100'],

            'last_name' => ['nullable', 'string', 'max:100'],

            'username' => [
                'nullable',
                'string',
                Rule::unique('users')->ignore($this->user()),
            ],

            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($this->user()),
            ],

            'mobile' => [
                'required',
                'digits:10',
                Rule::unique('users')->ignore($this->user()),
            ],

            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }
}
