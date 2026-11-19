<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\InvitationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InvitationEvent> */
class InvitationEventFactory extends Factory
{
    protected $model = InvitationEvent::class;

    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'name' => 'Acara utama',
            'type' => 'resepsi',
            'start_at' => now()->addMonth()->setTime(10, 0),
            'end_at' => now()->addMonth()->setTime(13, 0),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => fake()->company(),
            'address' => fake()->address(),
            'maps_url' => null,
        ];
    }
}