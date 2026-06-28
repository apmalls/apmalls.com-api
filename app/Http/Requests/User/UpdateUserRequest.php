<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [

            'first_name' => 'required|string|max:100',

            'last_name' => 'nullable|string|max:100',

            'username' => [
                'nullable',
                Rule::unique('users')->ignore($user),
            ],

            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user),
            ],

            'mobile' => [
                'required',
                Rule::unique('users')->ignore($user),
            ],

            'role' => 'required|exists:roles,name',

            'is_active' => 'boolean'

        ];
    }
}
