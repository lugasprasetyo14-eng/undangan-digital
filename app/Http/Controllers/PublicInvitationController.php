<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Support\InvitationSchedule;
use App\Support\InvitationThemeRenderer;
use Illuminate\View\View;
use Illuminate\Http\Response;

class PublicInvitationController extends Controller
{
    public function show(string $slug): Response
    {
        $invitation = Invitation::published()->where('slug', $slug)->with(['theme', 'events', 'media', 'giftOptions', 'wishlists.activeReservation', 'messages' => fn ($q) => $q->where('status', 'approved')])->firstOrFail();
        $guest = null;
        $token = request()->query('to');
        if (is_string($token) && strlen($token) === 64) {
            $guest = $invitation->guests()->where('rsvp_token', $token)->first();
            if ($guest && ! $guest->opened_at) {
                $invitation->guests()->whereKey($guest->id)->whereNull('opened_at')->update(['opened_at' => now()]);
                $guest->refresh();
            }
        }

        $response = response()->view(InvitationThemeRenderer::viewFor($invitation), [
            'invitation' => $invitation,
            'preview' => false,
            'upcomingEvent' => InvitationSchedule::upcoming($invitation->events),
            'guest' => $guest,
            'content' => true,
        ]);

        if (request()->query->has('to')) {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('Referrer-Policy', 'no-referrer');
        }

        return $response;
    }
}