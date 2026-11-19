<?php

namespace App\Http\Requests;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', Rule::in(['wedding', 'engagement', 'birthday', 'corporate', 'other'])],
            'groom_name' => ['required', 'string', 'max:255'],
            'bride_name' => ['required', 'string', 'max:255'],
            'story' => ['nullable', 'string', 'max:10000'],
            'theme_id' => ['required', 'integer', Rule::exists('themes', 'id')->where('is_active', true)],
        ];
    }
}