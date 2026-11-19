<?php

namespace App\Http\Requests;

use App\Models\InvitationGuest;
use Illuminate\Validation\Rule;

class UpdateInvitationGuestRequest extends StoreInvitationGuestRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'max_guests' => [
                'required', 'integer', 'min:1', 'max:65535',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $guest = $this->route('guest');
                    if ($guest instanceof InvitationGuest && $value < $guest->attending_count) {
                        $fail('Batas tamu tidak boleh lebih kecil dari jumlah yang sudah dikonfirmasi.');
                    }
                },
            ],
        ]);
    }
}