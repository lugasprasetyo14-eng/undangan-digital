<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Invitation> */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'theme_id' => Theme::factory(),
            'slug' => Str::slug(fake()->unique()->sentence(3)),
            'title' => 'Undangan '.fake()->name(),
            'event_type' => 'wedding',
            'groom_name' => fake()->name('male'),
            'bride_name' => fake()->name('female'),
            'story' => fake()->optional()->paragraph(),
            'status' => Invitation::STATUS_DRAFT,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => Invitation::STATUS_PUBLISHED, 'published_at' => now()]);
    }
}