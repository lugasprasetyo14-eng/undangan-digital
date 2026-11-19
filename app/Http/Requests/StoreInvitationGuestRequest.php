<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invitation = $this->route('invitation');
        $guest = $this->route('guest');
        abort_if($guest && $guest->invitation_id !== $invitation->id, 404);
        return $this->user()->can('update', $invitation);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(['family', 'friend', 'colleague', 'vip', 'other'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'max_guests' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
        ];
    }
}