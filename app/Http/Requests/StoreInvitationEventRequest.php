<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationEventRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['akad', 'resepsi', 'ngunduh_mantu', 'other'])],
            'start_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'end_at' => ['nullable', 'date_format:Y-m-d\\TH:i', 'after_or_equal:start_at'],
            'timezone' => ['required', 'timezone'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'maps_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}