<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Anime>
 */
class AnimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true); // Generate a unique title

        return [
            'title' => ucfirst($title),
            'slug' => Str::slug($title), // Convert title to slug
            'image' => "https://www3.animeflv.net/uploads/animes/covers/4130.jpg", // Random anime image
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
