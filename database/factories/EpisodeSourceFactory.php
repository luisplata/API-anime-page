<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\EpisodeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class EpisodeSourceFactory extends Factory
{
    protected $model = EpisodeSource::class;

    public function definition()
    {
        return [
            'episode_id' => Episode::factory(),
            'name' => $this->faker->word,
            'url' => $this->faker->url
        ];
    }
}
