<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitGuestRsvpRequest;
use App\Models\Invitation;
use App\Models\InvitationGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuestRsvpController extends Controller
{
    public function store(SubmitGuestRsvpRequest $request, string $slug): RedirectResponse
    {
        $invitation = Invitation::published()->where('slug', $slug)->firstOrFail();
        $guest = $invitation->guests()->where('rsvp_token', $request->input('to'))->firstOrFail();
        $key = 'rsvp|'.$guest->id.'|'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Terlalu banyak percobaan RSVP. Coba lagi nanti.');
        RateLimiter::hit($key, 60);

        $status = $request->validated('status');
        $count = (int) $request->validated('attending_count');
        DB::transaction(function () use ($guest, $status, $count) {
            $locked = InvitationGuest::whereKey($guest->id)->lockForUpdate()->firstOrFail();
            if ($status === 'attending' && ($count < 1 || $count > $locked->max_guests)) {
                throw ValidationException::withMessages(['attending_count' => 'Jumlah hadir harus antara 1 dan '.$locked->max_guests.' orang, termasuk tamu utama.']);
            }
            $locked->update(['rsvp_status' => $status, 'attending_count' => $status === 'attending' ? $count : 0, 'rsvp_at' => now()]);
        });
        return to_route('invitations.public', ['slug' => $slug, 'to' => $guest->rsvp_token])->with('status', 'rsvp-saved');
    }
}