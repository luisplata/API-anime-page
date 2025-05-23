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

        return null;
    }

    private function validateRequest(Request $request)
    {
        return $request->validate([
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
    }

    private function saveAnime(array $data, bool $isAnimeNew)
    {
        Log::info('Guardando animes', ['data' => $data]);
        foreach ($data as $animeData) {
            $animeTitle = implode(" ", $animeData['name']);
            $slug = $animeData['slug'];

            // Buscar por slug o por título
            $anime = Anime::whereRaw('LOWER(slug) = ?', [mb_strtolower($slug)])
                ->orWhereRaw('LOWER(title) = ?', [mb_strtolower($animeTitle)])
                ->first();

            if ($anime) {
                // Si existe, lo actualizamos
                $anime->update([
                    'slug' => $slug, // Por si cambió el slug
                    'title' => $animeTitle,
                    'description' => $animeData['description'],
                    'image' => $animeData['image']
                ]);
            } else {
                // Si no existe, lo creamos
                $anime = Anime::create([
                    'slug' => $slug,
                    'title' => $animeTitle,
                    'description' => $animeData['description'],
                    'image' => $animeData['image']
                ]);
            }

            // Si la imagen actual es vacía o viene de 'covers', la actualizamos
            if (str_contains($animeData['image'], 'covers') || empty($anime->image)) {
                $anime->update(['image' => $animeData['image']]);
            }

            foreach ($animeData['caps'] as $episodeData) {
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

                if ($episode->wasRecentlyCreated) {
                    $publishedAt = $isAnimeNew ? now()->subWeek() : now();
                    $episode->update(['published_at' => $publishedAt]);
                }

                foreach ($episodeData['source'] as $sourceData) {
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
    }

    public function sendAnimeToday(Request $request)
    {
        $this->checkToken($request);

        try {
            $data = $this->validateRequest($request);
            $this->saveAnime($data, false);

            return response()->json(['message' => 'Animes del día guardados correctamente'], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error processing sendAnimeToday: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function sendAnimeFull(Request $request)
    {
        $this->checkToken($request);

        try {
            $data = $this->validateRequest($request);
            $this->saveAnime($data, true);

            return response()->json(['message' => 'Anime completo guardado correctamente'], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation Error', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error processing sendAnimeFull: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    private function checkToken(Request $request)
    {
        $secret = env('WEBHOOK_SECRET');

        if ($request->header('X-Webhook-Token') !== $secret) {
            abort(401, 'Unauthorized');
        }
    }
}
