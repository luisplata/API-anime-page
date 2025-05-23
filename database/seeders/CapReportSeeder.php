<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CapReport;

class CapReportSeeder extends Seeder
{
    public function run(): void
    {
        CapReport::create([
            'episode_id' => 1,
            'reason' => 'Audio desincronizado',
            'description' => 'El audio está atrasado respecto al video.',
            'reported_by' => 'usuario1@email.com',
        ]);

        CapReport::create([
            'episode_id' => 2,
            'reason' => 'Video cortado',
            'description' => 'El video se corta a los 10 minutos.',
            'reported_by' => 'usuario2@email.com',
        ]);

        CapReport::create([
            'episode_id' => 1,
            'reason' => 'Sin subtítulos',
            'description' => 'No aparecen los subtítulos en el reproductor.',
            'reported_by' => 'usuario3@email.com',
        ]);
    }
}