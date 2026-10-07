<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->hasRole('Super Admin') && $user->is_active && $user->email_verified_at !== null;
    }

    public function rules(): array
    {
        return [
            'ratings' => ['required', 'array:functionality,usability,reliability,communication,handover'],
            'ratings.functionality' => ['required', 'integer', 'between:1,5'],
            'ratings.usability' => ['required', 'integer', 'between:1,5'],
            'ratings.reliability' => ['required', 'integer', 'between:1,5'],
            'ratings.communication' => ['required', 'integer', 'between:1,5'],
            'ratings.handover' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'owner_approved' => ['required', 'accepted'],
        ];
    }
}
