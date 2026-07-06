<?php

namespace Database\Factories;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => null,
            'type' => fake()->randomElement(AnnouncementType::cases()),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'image_url' => null,
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
