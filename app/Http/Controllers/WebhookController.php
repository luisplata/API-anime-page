<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\EpisodeSource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function webhook(Request $request)
    {
        try {
            // Validar el JSON entrante
            $data = $request->validate([
                'name' => 'required|array',
                'slug' => 'required|string|unique:animes,slug',
                'description' => 'nullable|string',
                'image' => 'nullable|url',
                'episodes' => 'required|array',
                'episodes.*.title' => 'required|string',
                'episodes.*.number' => 'required|integer',
                'episodes.*.source' => 'required|array',
                'episodes.*.source.*.name' => 'required|string',
                'episodes.*.source.*.url' => 'required|url'
            ]);

            // Convertir `name` en un string concatenado
            $animeTitle = implode(" ", $data['name']);

            // Crear o actualizar Anime
            $anime = Anime::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $animeTitle,
                    'description' => $data['description'],
                    'image' => $data['image']
                ]
            );

            // Procesar cada episodio
            foreach ($data['episodes'] as $ep) {
                $episode = Episode::updateOrCreate(
                    ['anime_id' => $anime->id, 'number' => $ep['number']],
                    ['title' => $ep['title']]
                );

                // Procesar cada fuente de video
                foreach ($ep['source'] as $src) {
                    EpisodeSource::updateOrCreate(
                        ['episode_id' => $episode->id, 'quality' => $src['name']], // `name` se guarda en `quality`
                        ['url' => $src['url']]
                    );
                }
            }

            return response()->json(['message' => 'Anime y episodios guardados exitosamente'], 200);
        } catch (Exception $e) {
            Log::error('Error al procesar webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar los datos'], 500);
        }
    }
}
