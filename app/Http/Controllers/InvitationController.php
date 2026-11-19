<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationRequest;
use App\Http\Requests\UpdateInvitationRequest;
use App\Models\Invitation;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Support\InvitationSchedule;
use App\Support\InvitationThemeRenderer;

class InvitationController extends Controller
{
    public function index(): View
    {
        $invitations = request()->user()->invitations()->with('theme')->latest()->get();
        return view('dashboard.invitations.index', compact('invitations'));
    }

    public function create(): View
    {
        return view('dashboard.invitations.create', ['themes' => Theme::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['status'] = Invitation::STATUS_DRAFT;
        $invitation = $request->user()->invitations()->create($data);
        return to_route('dashboard.invitations.edit', $invitation)->with('status', 'invitation-created');
    }

    public function edit(Invitation $invitation): View
    {
        Gate::authorize('update', $invitation);
        $invitation->load(['theme', 'events', 'media', 'giftOptions', 'wishlists.activeReservation', 'messages']);
        return view('dashboard.invitations.edit', ['invitation' => $invitation, 'themes' => Theme::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function update(UpdateInvitationRequest $request, Invitation $invitation): RedirectResponse
    {
        Gate::authorize('update', $invitation);
        $invitation->update($request->validated());
        return back()->with('status', 'invitation-updated');
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('delete', $invitation);
        $invitation->delete();
        return to_route('dashboard.invitations.index')->with('status', 'invitation-deleted');
    }

    public function preview(Invitation $invitation): View
    {
        Gate::authorize('preview', $invitation);
        $invitation->load(['theme', 'events']);

        return view(InvitationThemeRenderer::viewFor($invitation), [
            'invitation' => $invitation,
            'preview' => true,
            'upcomingEvent' => InvitationSchedule::upcoming($invitation->events),
            'guest' => null,
            'content' => true,
        ]);
    }

    public function publish(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('publish', $invitation);
        $invitation->update(['status' => Invitation::STATUS_PUBLISHED, 'published_at' => now()]);
        return back()->with('status', 'invitation-published');
    }

    public function unpublish(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('unpublish', $invitation);
        $invitation->update(['status' => Invitation::STATUS_DRAFT, 'published_at' => null]);
        return back()->with('status', 'invitation-unpublished');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'undangan';
        $slug = $base;
        $counter = 2;
        while (Invitation::where('slug', $slug)->exists()) $slug = $base.'-'.$counter++;
        return $slug;
    }
}