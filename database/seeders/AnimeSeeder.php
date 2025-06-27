<?php

namespace Database\Seeders;

use App\Models\Anime;
use App\Models\AnimeAlterName;
use App\Models\AnimeGenre;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        //insert 30 animes
        Anime::factory(30)->create()->each(function ($anime) {
            // Crear entre 1 y 3 nombres alternativos por anime
            for ($i = 0; $i < rand(1, 3); $i++) {
                AnimeAlterName::create([
                    'anime_id' => $anime->id,
                    'name' => fake()->words(rand(1, 3), true),
                ]);
            }

            // Crear entre 1 y 3 géneros por anime
            $genres = ['Acción', 'Aventura', 'Comedia', 'Drama', 'Fantasía', 'Romance', 'Ciencia Ficción'];
            foreach (fake()->randomElements($genres, rand(1, 3)) as $genre) {
                AnimeGenre::create([
                    'anime_id' => $anime->id,
                    'genre' => $genre,
                ]);
            }
        });
    }
}
