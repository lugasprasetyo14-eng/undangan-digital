<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\InvitationGuest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<InvitationGuest> */
class InvitationGuestFactory extends Factory
{
    protected $model = InvitationGuest::class;

    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'name' => fake()->name(),
            'slug' => Str::lower(Str::random(12)),
            'phone' => fake()->optional()->numerify('08##########'),
            'email' => fake()->optional()->safeEmail(),
            'category' => fake()->optional()->randomElement(['family', 'friend', 'colleague', 'vip']),
            'max_guests' => 1,
            'rsvp_token' => Str::random(64),
            'qr_token' => Str::random(64),
            'invited_at' => now(),
            'rsvp_status' => InvitationGuest::RSVP_PENDING,
            'attending_count' => 0,
        ];
    }
}