<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitGuestRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invitation = \App\Models\Invitation::published()->where('slug', $this->route('slug'))->firstOrFail();
        abort_unless(is_string($this->input('to')) && $invitation->guests()->where('rsvp_token', $this->input('to'))->exists(), 404);
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['attending', 'not_attending'])],
            'to' => ['required', 'string', 'size:64'],
            'attending_count' => ['nullable', 'required_if:status,attending', 'integer', 'min:0', 'max:65535'],
        ];
    }
}