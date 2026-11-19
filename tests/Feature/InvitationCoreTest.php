<?php

use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Support\InvitationSchedule;

uses(RefreshDatabase::class);

function invitationPayload(Theme $theme, array $overrides = []): array
{
    return array_merge([
        'title' => 'Hari Bahagia',
        'event_type' => 'wedding',
        'groom_name' => 'Raka',
        'bride_name' => 'Ayu',
        'story' => 'Cerita kami.',
        'theme_id' => $theme->id,
    ], $overrides);
}

it('protects invitation routes from guests and other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $invitation = Invitation::factory()->create(['user_id' => $owner->id, 'groom_name' => 'Raka', 'bride_name' => 'Ayu']);

    $this->get(route('dashboard.invitations.index'))->assertRedirect(route('login'));
    $this->actingAs($other)->get(route('dashboard.invitations.edit', $invitation))->assertForbidden();
    $this->actingAs($other)->delete(route('dashboard.invitations.destroy', $invitation))->assertForbidden();
});

it('creates, updates, and deletes an owned invitation with a stable unique slug', function () {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();
    $this->actingAs($user);

    $this->post(route('dashboard.invitations.store'), invitationPayload($theme))
        ->assertRedirect();
    $invitation = $user->invitations()->firstOrFail();
    expect($invitation->status)->toBe('draft')->and($invitation->slug)->toBe('hari-bahagia');

    $slug = $invitation->slug;
    $this->put(route('dashboard.invitations.update', $invitation), invitationPayload($theme, ['title' => 'Judul Baru']))
        ->assertRedirect();
    expect($invitation->refresh()->slug)->toBe($slug)->and($invitation->title)->toBe('Judul Baru');

    $this->delete(route('dashboard.invitations.destroy', $invitation))->assertRedirect(route('dashboard.invitations.index'));
    $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
});

it('renders the edit page after saving a draft', function () {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.invitations.store'), invitationPayload($theme, [
        'title' => 'QA Draft Render',
    ]));
    $invitation = $user->invitations()->where('title', 'QA Draft Render')->firstOrFail();

    $response->assertRedirect(route('dashboard.invitations.edit', $invitation));
    $this->get(route('dashboard.invitations.edit', $invitation))
        ->assertOk()
        ->assertSee('QA Draft Render')
        ->assertSee('Simpan perubahan')
        ->assertSee('Publish sekarang');
});

it('gives create and multiple edit event fields unique ids with matching labels and descriptions', function () {
    $owner = User::factory()->create();
    $invitation = Invitation::factory()->create(['user_id' => $owner->id]);
    $events = InvitationEvent::factory()->count(2)->create(['invitation_id' => $invitation->id]);
    $response = $this->actingAs($owner)->get(route('dashboard.invitations.edit', $invitation))->assertOk();
    $dom = new DOMDocument();
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    $ids = [];
    foreach ($xpath->query('//*[@id]') as $element) {
        $ids[] = $element->getAttribute('id');
    }
    expect(count($ids))->toBe(count(array_unique($ids)));
    foreach (array_merge(['event-create'], $events->map(fn ($event) => 'event-edit-'.$event->id)->all()) as $prefix) {
        foreach (['name', 'type', 'start_at', 'end_at', 'timezone', 'venue_name', 'address', 'maps_url'] as $name) {
            $id = $prefix.'-'.$name;
            expect($xpath->query('//*[@id="'.$id.'" and @name="'.$name.'"]')->length)->toBe(1);
            expect($xpath->query('//label[@for="'.$id.'"]')->length)->toBe(1);
        }
    }
    foreach ($xpath->query('//*[@aria-describedby]') as $element) {
        foreach (explode(' ', $element->getAttribute('aria-describedby')) as $id) {
            expect($xpath->query('//*[@id="'.$id.'"]')->length)->toBe(1);
        }
    }
    expect($dom->getElementById('event-create-timezone')->getAttribute('aria-describedby'))->toBe('event-create-timezone-hint');
});

it('validates invitations and prevents status fields from being changed by edit input', function () {
    $user = User::factory()->create();
    $theme = Theme::factory()->create();
    $this->actingAs($user);
    $response = $this->post(route('dashboard.invitations.store'), ['title' => '', 'theme_id' => $theme->id]);
    $response->assertSessionHasErrors(['title', 'event_type', 'groom_name', 'bride_name']);

    $invitation = Invitation::factory()->create(['user_id' => $user->id]);
    $this->put(route('dashboard.invitations.update', $invitation), invitationPayload($theme, [
        'status' => 'published', 'published_at' => now(), 'user_id' => User::factory()->create()->id,
    ]))->assertRedirect();
    expect($invitation->refresh()->status)->toBe('draft')->and($invitation->user_id)->toBe($user->id);
});

it('validates event boundaries and keeps local Makassar time unchanged', function () {
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $payload = ['name' => 'Resepsi', 'type' => 'resepsi', 'start_at' => '2027-01-10T10:00', 'end_at' => '2027-01-10T09:00', 'timezone' => 'Asia/Makassar'];
    $this->post(route('dashboard.invitations.events.store', $invitation), $payload)->assertSessionHasErrors('end_at');
    $this->post(route('dashboard.invitations.events.store', $invitation), [...$payload, 'end_at' => null])->assertRedirect();
    $event = $invitation->events()->firstOrFail();
    expect($event->start_at->format('H:i'))->toBe('10:00')->and($event->timezone)->toBe('Asia/Makassar');
});

it('rejects an event belonging to another invitation', function () {
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create(['user_id' => $user->id]);
    $otherInvitation = Invitation::factory()->create(['user_id' => $user->id]);
    $event = InvitationEvent::factory()->create(['invitation_id' => $otherInvitation->id]);
    $this->actingAs($user)->delete(route('dashboard.invitations.events.destroy', [$invitation, $event]))->assertNotFound();
});

it('allows only the owner to preview and controls public visibility by status', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'user_id' => $owner->id,
        'groom_name' => 'Raka',
        'bride_name' => 'Ayu',
    ]);

    $this->actingAs($other)->get(route('dashboard.invitations.preview', $invitation))->assertForbidden();
    $this->get(route('invitations.public', $invitation->slug))->assertNotFound();
    $this->actingAs($owner)->get(route('dashboard.invitations.preview', $invitation))->assertOk();

    $this->actingAs($owner)->post(route('dashboard.invitations.publish', $invitation))->assertRedirect();
    $this->get(route('invitations.public', $invitation->slug))->assertOk()->assertSee('Raka');
    $this->actingAs($owner)->post(route('dashboard.invitations.unpublish', $invitation))->assertRedirect();
    $this->get(route('invitations.public', $invitation->slug))->assertNotFound();
    expect($invitation->refresh()->status)->toBe('draft')->and($invitation->published_at)->toBeNull();
});

it('renders each theme consistently for preview and public pages with a safe fallback', function () {
    $user = User::factory()->create();

    foreach (['elegant-ivory', 'modern-minimal', 'romantic-blush'] as $slug) {
        $theme = Theme::factory()->create(['slug' => $slug]);
        $invitation = Invitation::factory()->published()->create(['user_id' => $user->id, 'theme_id' => $theme->id]);

        $this->actingAs($user)->get(route('dashboard.invitations.preview', $invitation))->assertOk()->assertSee($invitation->groom_name);
        $this->get(route('invitations.public', $invitation->slug))->assertOk()->assertSee($invitation->bride_name);
    }

    $fallback = Theme::factory()->create(['slug' => 'unrecognised-theme']);
    $invitation = Invitation::factory()->published()->create(['user_id' => $user->id, 'theme_id' => $fallback->id]);
    $this->get(route('invitations.public', $invitation->slug))->assertOk()->assertSee('Dengan penuh sukacita');
});

it('renders invitation metadata and timezone-aware calendar links', function () {
    $user = User::factory()->create();
    $theme = Theme::factory()->create(['slug' => 'modern-minimal']);
    $invitation = Invitation::factory()->published()->create([
        'user_id' => $user->id,
        'theme_id' => $theme->id,
        'title' => 'Hari Bahagia Raka dan Ayu',
        'groom_name' => 'Raka',
        'bride_name' => 'Ayu',
    ]);
    InvitationEvent::factory()->create([
        'invitation_id' => $invitation->id,
        'start_at' => '2027-06-10 10:00:00',
        'end_at' => '2027-06-10 12:00:00',
        'timezone' => 'Asia/Makassar',
        'maps_url' => 'javascript:alert(1)',
    ]);

    $response = $this->get(route('invitations.public', $invitation->slug));
    $response->assertOk()
        ->assertSee('noindex, nofollow')
        ->assertSee('Hari Bahagia Raka dan Ayu')
        ->assertSee('10 Jun 2027, 10:00')
        ->assertSee('Asia/Makassar')
        ->assertDontSee('javascript:alert(1)')
        ->assertSee('20270610T020000Z');
});

it('converts local event time to UTC for Jakarta, Makassar, and Jayapura calendars', function () {
    foreach ([
        ['Asia/Jakarta', '03:00:00Z'],
        ['Asia/Makassar', '02:00:00Z'],
        ['Asia/Jayapura', '01:00:00Z'],
    ] as [$timezone, $utcTime]) {
        $event = InvitationEvent::factory()->make([
            'start_at' => '2027-06-10 10:00:00',
            'end_at' => null,
            'timezone' => $timezone,
        ]);

        expect(InvitationSchedule::localStart($event)->format('H:i'))->toBe('10:00')
            ->and(InvitationSchedule::calendarUrl($event))->toContain('20270610T'.str_replace(':', '', $utcTime));
    }
});

it('does not show countdown markup when no event is upcoming', function () {
    $invitation = Invitation::factory()->published()->create();
    InvitationEvent::factory()->create([
        'invitation_id' => $invitation->id,
        'start_at' => '2020-01-01 10:00:00',
        'timezone' => 'Asia/Jayapura',
    ]);

    $this->get(route('invitations.public', $invitation->slug))
        ->assertOk()
        ->assertDontSee('data-countdown');
});