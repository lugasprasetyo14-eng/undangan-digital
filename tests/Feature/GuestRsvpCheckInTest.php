<?php

use App\Models\Invitation;
use App\Models\InvitationGuest;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function guestInvitation(User $user, array $attributes = []): Invitation
{
    return Invitation::factory()->published()->create(array_merge(['user_id' => $user->id], $attributes));
}

it('protects guest management and nested guests from other accounts', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $invitation = guestInvitation($owner);
    $guest = InvitationGuest::factory()->create(['invitation_id' => $invitation->id]);

    $this->actingAs($other)->get(route('dashboard.invitations.guests.index', $invitation))->assertForbidden();
    $this->actingAs($other)->get(route('dashboard.invitations.guests.edit', [$invitation, $guest]))->assertForbidden();
    $this->actingAs($owner)->get(route('dashboard.invitations.guests.edit', [$invitation, InvitationGuest::factory()->create()]))->assertNotFound();
});

it('creates personal tokens and resolves a token only within its invitation', function () {
    $owner = User::factory()->create();
    $first = guestInvitation($owner);
    $second = guestInvitation($owner);
    $guest = InvitationGuest::factory()->create(['invitation_id' => $first->id, 'name' => 'Tamu Rahasia']);

    $this->get(route('invitations.public', [$second->slug]).'?to='.$guest->rsvp_token)
        ->assertOk()->assertDontSee('Tamu Rahasia')->assertDontSee('Konfirmasi kehadiran');
    $this->get(route('invitations.public', [$first->slug]).'?to=invalid-token')
        ->assertOk()->assertDontSee('Tamu Rahasia')->assertDontSee('Konfirmasi kehadiran');
    $this->get(route('invitations.public', [$first->slug]).'?to='.$guest->rsvp_token)
        ->assertOk()->assertSee('Untuk Tamu Rahasia')->assertSee('Konfirmasi kehadiran')
        ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
    expect($guest->refresh()->opened_at)->not->toBeNull();
});

it('accepts only personal RSVP and keeps one guest record as the source of truth', function () {
    $owner = User::factory()->create();
    $invitation = guestInvitation($owner);
    $guest = InvitationGuest::factory()->create(['invitation_id' => $invitation->id, 'max_guests' => 3]);

    $this->from(route('invitations.public', [$invitation->slug]).'?to='.$guest->rsvp_token)
        ->post(route('invitations.rsvp', $invitation->slug), ['to' => $guest->rsvp_token, 'status' => 'attending', 'attending_count' => 3])
        ->assertRedirect()->assertSessionHas('status', 'rsvp-saved');
    expect($guest->refresh()->rsvp_status)->toBe('attending')->and($guest->attending_count)->toBe(3);

    $this->post(route('invitations.rsvp', $invitation->slug), ['to' => $guest->rsvp_token, 'status' => 'attending', 'attending_count' => 4])->assertSessionHasErrors('attending_count');
    $this->post(route('invitations.rsvp', $invitation->slug), ['status' => 'attending', 'attending_count' => 1])->assertNotFound();
    expect($invitation->guests()->count())->toBe(1);
});

it('rejects RSVP outside max guests and prevents lowering a confirmed limit', function () {
    $owner = User::factory()->create();
    $invitation = guestInvitation($owner);
    $guest = InvitationGuest::factory()->create(['invitation_id' => $invitation->id, 'max_guests' => 2]);

    $this->post(route('invitations.rsvp', $invitation->slug), ['to' => $guest->rsvp_token, 'status' => 'attending', 'attending_count' => 3])->assertSessionHasErrors('attending_count');
    $guest->update(['rsvp_status' => 'attending', 'attending_count' => 2]);
    $this->actingAs($owner)->put(route('dashboard.invitations.guests.update', [$invitation, $guest]), ['name' => $guest->name, 'max_guests' => 1])->assertSessionHasErrors('max_guests');
    expect($guest->refresh()->max_guests)->toBe(2);
});

it('reports accurate RSVP statistics after resubmission', function () {
    $owner = User::factory()->create();
    $invitation = guestInvitation($owner);
    $first = InvitationGuest::factory()->create(['invitation_id' => $invitation->id, 'max_guests' => 3]);
    $second = InvitationGuest::factory()->create(['invitation_id' => $invitation->id, 'max_guests' => 2]);

    $this->post(route('invitations.rsvp', $invitation->slug), ['to' => $first->rsvp_token, 'status' => 'attending', 'attending_count' => 3]);
    $this->post(route('invitations.rsvp', $invitation->slug), ['to' => $second->rsvp_token, 'status' => 'attending', 'attending_count' => 2]);
    $this->post(route('invitations.rsvp', $invitation->slug), ['to' => $first->rsvp_token, 'status' => 'not_attending', 'attending_count' => 0]);

    $this->actingAs($owner)->get(route('dashboard.invitations.guests.index', $invitation))
        ->assertSee('2')->assertSee('1');
    expect($invitation->guests()->where('rsvp_status', 'attending')->sum('attending_count'))->toBe(2);
});

it('exports safe CSV without RSVP or QR tokens', function () {
    $owner = User::factory()->create();
    $invitation = guestInvitation($owner);
    $guest = InvitationGuest::factory()->create(['invitation_id' => $invitation->id, 'name' => '=Formula']);

    $response = $this->actingAs($owner)->get(route('dashboard.invitations.guests.export', $invitation));
    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->getContent())->not->toContain($guest->rsvp_token)->and($response->getContent())->not->toContain($guest->qr_token)->and($response->getContent())->toContain("'=Formula");
});

it('does not check in on QR GET and check-in is owner-only, published-only, and idempotent', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $invitation = guestInvitation($owner);
    $guest = InvitationGuest::factory()->create(['invitation_id' => $invitation->id]);

    $this->actingAs($owner)->get(route('dashboard.invitations.guests.verify', [$invitation, $guest->qr_token]))->assertOk();
    expect($guest->refresh()->checked_in_at)->toBeNull();
    $this->actingAs($other)->post(route('dashboard.invitations.guests.check-in', [$invitation, $guest->qr_token]))->assertForbidden();
    $this->actingAs($owner)->post(route('dashboard.invitations.guests.check-in', [$invitation, $guest->qr_token]))->assertRedirect();
    $timestamp = $guest->refresh()->checked_in_at;
    $this->actingAs($owner)->post(route('dashboard.invitations.guests.check-in', [$invitation, $guest->qr_token]))->assertRedirect();
    expect($guest->refresh()->checked_in_at->equalTo($timestamp))->toBeTrue();

    $invitation->update(['status' => Invitation::STATUS_DRAFT]);
    $this->actingAs($owner)->post(route('dashboard.invitations.guests.check-in', [$invitation, $guest->qr_token]))->assertNotFound();
});

it('does not include personal guest data in invitation metadata', function () {
    $owner = User::factory()->create();
    $invitation = guestInvitation($owner);
    $guest = InvitationGuest::factory()->create(['invitation_id' => $invitation->id, 'name' => 'Nama Personal']);

    $this->get(route('invitations.public', [$invitation->slug]).'?to='.$guest->rsvp_token)
        ->assertSee('Nama Personal')
        ->assertDontSee('og:title" content="Nama Personal')
        ->assertDontSee('og:description" content="Nama Personal');
});