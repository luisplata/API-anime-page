<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Anime;
use Illuminate\Database\Eloquent\Factories\Factory;

class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition()
    {
        return [
            'anime_id' => Anime::factory(),
            'number' => $this->faker->numberBetween(1, 50),
            'title' => $this->faker->sentence(3),
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
