<?php
namespace App\Http\Controllers;
use App\Models\{Invitation, InvitationWishlist, WishlistReservation};
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{DB, RateLimiter};
use Illuminate\Validation\ValidationException;
class PublicContentController extends Controller
{
    public function message(string $slug): RedirectResponse { $invitation=Invitation::published()->where('slug',$slug)->firstOrFail(); request()->validate(['name'=>'required|string|max:120','message'=>'required|string|max:2000']); $key='message|'.$invitation->id.'|'.request()->ip(); abort_if(RateLimiter::tooManyAttempts($key,5),429); RateLimiter::hit($key,60); $guest=$invitation->guests()->where('rsvp_token',request('to'))->first(); abort_if(request('to') && !$guest,404); $invitation->messages()->create(['name'=>request('name'),'message'=>request('message'),'guest_id'=>$guest?->id]); return back()->with('status','message-pending'); }
    public function reserve(string $slug, InvitationWishlist $wishlist): RedirectResponse
    {
        $invitation = Invitation::published()->where('slug', $slug)->firstOrFail();
        abort_unless($wishlist->invitation_id === $invitation->id, 404);
        $guest = $invitation->guests()->where('rsvp_token', request('to'))->firstOrFail();
        $url = route('invitations.public', ['slug' => $invitation->slug, 'to' => $guest->rsvp_token]);
        $key = 'reserve|'.$wishlist->id.'|'.$guest->id;
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429);
        RateLimiter::hit($key, 60);
        try {
            DB::transaction(function () use ($wishlist, $guest) {
                $locked = InvitationWishlist::whereKey($wishlist->id)->lockForUpdate()->firstOrFail();
                $reservation = $locked->activeReservation()->first();
                if ($reservation) {
                    if ($reservation->guest_id === $guest->id) {
                        return;
                    }
                    throw ValidationException::withMessages(['wishlist' => 'Hadiah ini sudah dipesan tamu lain. Silakan pilih hadiah lain.']);
                }
                $locked->reservations()->create(['guest_id' => $guest->id, 'reserved_at' => now(), 'active_slot' => 1]);
            });
        } catch (ValidationException $e) {
            return redirect($url)->withErrors($e->errors());
        }
        return redirect($url)->with('status', 'wishlist-reserved');
    }
}