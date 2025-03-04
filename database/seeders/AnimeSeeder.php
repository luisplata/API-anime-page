<?php

namespace Database\Seeders;

use App\Models\Anime;
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
        Anime::factory(30)->create();
    }
}
