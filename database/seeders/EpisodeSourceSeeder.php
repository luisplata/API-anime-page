<?php

namespace Database\Seeders;

use App\Models\Episode;
use App\Models\EpisodeSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EpisodeSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $episodes = Episode::all();

        foreach ($episodes as $episode) {
            EpisodeSource::insert([
                [
                    'episode_id' => $episode->id,
                    'quality' => '1080p',
                    'url' => "https://example.com/videos/{$episode->anime->slug}-{$episode->number}-1080p"
                ],
                [
                    'episode_id' => $episode->id,
                    'quality' => '720p',
                    'url' => "https://example.com/videos/{$episode->anime->slug}-{$episode->number}-720p"
                ],
            ]);
        }
    }
}
