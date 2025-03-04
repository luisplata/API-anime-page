<?php

namespace Database\Seeders;

use App\Models\Anime;
use App\Models\Episode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EpisodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $animes = Anime::all();

        foreach ($animes as $anime) {
            for ($i = 1; $i <= 15; $i++) {
                Episode::create([
                    'anime_id' => $anime->id,
                    'number' => $i,
                    'title' => "Episode $i of {$anime->title}"
                ]);
            }
        }
    }
}
