<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationEventRequest;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InvitationEventController extends Controller
{
    public function store(StoreInvitationEventRequest $request, Invitation $invitation): RedirectResponse
    {
        Gate::authorize('update', $invitation);
        $invitation->events()->create($request->validated());
        return back()->with('status', 'event-created');
    }

    public function update(StoreInvitationEventRequest $request, Invitation $invitation, InvitationEvent $event): RedirectResponse
    {
        Gate::authorize('update', $invitation);
        abort_unless($event->invitation_id === $invitation->id, 404);
        $event->update($request->validated());
        return back()->with('status', 'event-updated');
    }

    public function destroy(Invitation $invitation, InvitationEvent $event): RedirectResponse
    {
        Gate::authorize('update', $invitation);
        abort_unless($event->invitation_id === $invitation->id, 404);
        $event->delete();
        return back()->with('status', 'event-deleted');
    }
}