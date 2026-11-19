<?php

use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\ThemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates draft invitations and links the core relationships', function () {
    $invitation = Invitation::factory()->create();
    $event = InvitationEvent::factory()->create(['invitation_id' => $invitation->id]);

    expect($invitation->status)->toBe(Invitation::STATUS_DRAFT)
        ->and($invitation->user)->toBeInstanceOf(User::class)
        ->and($invitation->theme)->toBeInstanceOf(Theme::class)
        ->and($invitation->events->first()->is($event))->toBeTrue()
        ->and($event->invitation->is($invitation))->toBeTrue();
});

it('enforces unique invitation slugs', function () {
    $invitation = Invitation::factory()->create(['slug' => 'hari-bahagia']);

    expect(fn () => Invitation::factory()->create(['slug' => $invitation->slug]))
        ->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

it('cascades invitation deletion to events but restricts theme deletion', function () {
    $invitation = Invitation::factory()->create();
    InvitationEvent::factory()->create(['invitation_id' => $invitation->id]);
    $theme = $invitation->theme;

    $invitation->delete();
    expect(InvitationEvent::where('invitation_id', $invitation->id)->count())->toBe(0);

    Invitation::factory()->create(['theme_id' => $theme->id]);
    expect(fn () => $theme->delete())->toThrow(\Illuminate\Database\QueryException::class);
});

it('seeds the three themes idempotently', function () {
    $this->seed(ThemeSeeder::class);
    $this->seed(ThemeSeeder::class);

    expect(Theme::whereIn('slug', ['elegant-ivory', 'modern-minimal', 'romantic-blush'])->count())->toBe(3)
        ->and(Theme::whereNull('preview_url')->count())->toBe(3);
});

it('keeps phase three tables and does not create later billing tables', function () {
    expect(Schema::hasTable('themes'))->toBeTrue()
        ->and(Schema::hasTable('invitations'))->toBeTrue()
        ->and(Schema::hasTable('invitation_events'))->toBeTrue()
        ->and(Schema::hasTable('invitation_guests'))->toBeTrue()
        ->and(Schema::hasTable('payments'))->toBeFalse();
});