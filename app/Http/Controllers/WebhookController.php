<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\EpisodeSource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    private function parseStringToBool($string)
    {
        $trueValues = ['true', '1', 'yes', 'on'];
        $falseValues = ['false', '0', 'no', 'off'];

        $string = strtolower(trim($string));

        if (in_array($string, $trueValues, true)) {
            return true;
        } elseif (in_array($string, $falseValues, true)) {
            return false;
        }

        return null; // or throw an exception if the value is not recognized
    }

    public function webhook(Request $request)
    {
        $secret = env('WEBHOOK_SECRET');

        if ($request->header('X-Webhook-Token') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            // Validar el JSON entrante
            $data = $request->validate([
                '*.name' => 'required|array',
                '*.slug' => 'required|string',
                '*.description' => 'nullable|string',
                '*.image' => 'nullable|url',
                '*.caps' => 'required|array',
                '*.caps.*.title' => 'required|string',
                '*.caps.*.number' => 'required|integer',
                '*.caps.*.link' => 'required|string',
                '*.caps.*.source' => 'required|array',
                '*.caps.*.source.*.name' => 'required|string',
                '*.caps.*.source.*.url' => ['required', 'regex:/^https?:\/\/[^\s$.?#].[^\s]*$/i']
            ]);

            foreach ($data as $animeData) {
                // Convertir `name` en un string concatenado
                $animeTitle = implode(" ", $animeData['name']);

                // Verificar si el anime ya existe
                $existingAnime = Anime::where('slug', $animeData['slug'])->first();

                if ($existingAnime) {
                    Log::info("Anime already exists with slug: {$animeData['slug']}");
                    $anime = $existingAnime;
                    $isAnimeNew = false;
                } else {
                    // Crear el anime si no existe
                    $anime = Anime::create([
                        'slug' => $animeData['slug'],
                        'title' => $animeTitle,
                        'description' => $animeData['description'],
                        'image' => $animeData['image']
                    ]);
                    $isAnimeNew = true;
                }

                if (str_contains($animeData['image'], 'covers') || empty($anime->image)) {
                    $anime->update(['image' => $animeData['image']]);
                }

                Log::info("isAnimeNew value: " . ($isAnimeNew ? 'true' : 'false'));

                foreach ($animeData['caps'] as $episodeData) {
                    // Solo crear si no existe
                    $episode = Episode::firstOrCreate(
                        [
                            'anime_id' => $anime->id,
                            'number' => $episodeData['number']
                        ],
                        [
                            'title' => $episodeData['title'],
                            'link' => $episodeData['link']
                        ]
                    );

                    $publishedAt = $isAnimeNew ? now()->subWeek() : now();
                    $episode->update([
                        'published_at' => $publishedAt
                    ]);

                    Log::info("is new? {$isAnimeNew} Published at {$publishedAt}");

                    foreach ($episodeData['source'] as $sourceData) {
                        // Solo crear si no existe
                        EpisodeSource::firstOrCreate(
                            [
                                'episode_id' => $episode->id,
                                'name' => $sourceData['name']
                            ],
                            [
                                'url' => $sourceData['url']
                            ]
                        );
                    }
                }
            }

            return response()->json(['message' => 'Anime y episodios guardados exitosamente'], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error processing webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
