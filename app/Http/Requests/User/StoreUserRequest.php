<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'first_name' => 'required|string|max:100',

            'last_name' => 'nullable|string|max:100',

            'username' => 'nullable|string|max:100|unique:users,username',

            'email' => 'required|email|unique:users,email',

            'mobile' => 'required|digits:10|unique:users,mobile',

            'role' => 'required|exists:roles,name',

            'is_active' => 'boolean'
        ];
    }
}
