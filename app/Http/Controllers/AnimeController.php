<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class AnimeController extends Controller
{
    public function index()
    {
        return Anime::with(['alterNames', 'genres'])->orderBy('created_at', 'desc')->paginate(20);
    }

    public function show($anime_slug)
    {
        $decodedSlug = urldecode($anime_slug);

        // 1. Buscar por slug exacto (sin limpiar)
        $anime = Anime::with(['alterNames', 'genres', 'episodes.sources'])
            ->where('slug', $decodedSlug)
            ->first();

        if ($anime) {
            return $anime;
        }

        // 2. Buscar por título exacto (sin limpiar)
        $anime = Anime::with(['alterNames', 'genres', 'episodes.sources'])
            ->where('title', $decodedSlug)
            ->first();

        if ($anime) {
            return $anime;
        }

        // 3. Buscar por alterName exacto (sin limpiar)
        $anime = Anime::with(['alterNames', 'genres', 'episodes.sources'])
            ->whereHas('alterNames', function ($q) use ($decodedSlug) {
                $q->where('name', $decodedSlug);
            })
            ->first();

        if ($anime) {
            return $anime;
        }

        // 4. Si no hay coincidencia exacta, intenta con la versión "limpia" (opcional)
        $cleanSlug = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $decodedSlug);
        $cleanSlug = trim(preg_replace('/\s+/', ' ', $cleanSlug));
        $cleanSlugLower = mb_strtolower($cleanSlug);

        $anime = Anime::with(['alterNames', 'genres', 'episodes.sources'])
            ->whereRaw("TRIM(LOWER(REPLACE(REPLACE(slug, '-', ' '), '_', ' '))) = ?", [$cleanSlugLower])
            ->orWhereRaw("TRIM(LOWER(REPLACE(REPLACE(title, '-', ' '), '_', ' '))) = ?", [$cleanSlugLower])
            ->orWhereHas('alterNames', function ($q) use ($cleanSlugLower) {
                $q->whereRaw("TRIM(LOWER(REPLACE(REPLACE(name, '-', ' '), '_', ' '))) = ?", [$cleanSlugLower]);
            })
            ->first();

        if ($anime) {
            return $anime;
        }

        // 5. Si no hay coincidencia, retorna vacío
        return response()->json($this->emptyAnimeResponse(), 404);
    }

    /**
     * Devuelve un JSON de respuesta vacía para mantener el formato esperado.
     */
    private function emptyAnimeResponse()
    {
        return [
            "id" => null,
            "title" => null,
            "slug" => null,
            "description" => null,
            "image" => null,
            "created_at" => null,
            "updated_at" => null,
            "episodes" => []
        ];
    }


    public function search(Request $request)
    {
        $query = $request->query('q');
        $perPage = $request->query('per_page', 20); // Default to 20 items per page

        if (!$query) {
            return response()->json(['error' => 'Query parameter "q" is required'], 400);
        }

        // Decodificar y limpiar la query (reemplazando caracteres especiales por espacios)
        $decodedQuery = urldecode($query);
        $cleanQuery = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $decodedQuery);
        $queryWords = array_filter(explode(' ', trim($cleanQuery)));

        if (empty($queryWords)) {
            return response()->json([], 200);
        }

        // Limpiar y pasar a minúsculas las palabras de la query SOLO UNA VEZ
        $queryWords = array_map('mb_strtolower', $queryWords);

        // Obtener la primera y última palabra para filtrar inicialmente
        $firstWord = $queryWords[0];
        $lastWord = end($queryWords);

        // Filtrar los animes que contienen al menos una coincidencia en el slug, el título o los alterNames
        $filteredAnimes = Anime::with(['alterNames', 'genres'])
            ->whereRaw('LOWER(slug) LIKE ?', ['%' . $firstWord . '%'])
            ->orWhereRaw('LOWER(slug) LIKE ?', ['%' . $lastWord . '%'])
            ->orWhereRaw('LOWER(title) LIKE ?', ['%' . $firstWord . '%'])
            ->orWhereRaw('LOWER(title) LIKE ?', ['%' . $lastWord . '%'])
            ->orWhereHas('alterNames', function ($q) use ($firstWord, $lastWord) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $firstWord . '%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%' . $lastWord . '%']);
            })
            ->get();

        // Array para almacenar los resultados con su puntaje
        $rankedResults = [];

        foreach ($filteredAnimes as $anime) {
            // Limpiar y dividir slug y título
            $cleanAnimeSlug = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $anime->slug);
            $animeSlugWords = array_map('mb_strtolower', array_filter(explode(' ', trim($cleanAnimeSlug))));

            $cleanAnimeTitle = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $anime->title);
            $animeTitleWords = array_map('mb_strtolower', array_filter(explode(' ', trim($cleanAnimeTitle))));

            // Unir todos los alterNames en un solo array de palabras
            $alterNameWords = [];
            foreach ($anime->alterNames as $alterName) {
                $cleanAlter = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $alterName->name);
                $words = array_map('mb_strtolower', array_filter(explode(' ', trim($cleanAlter))));
                $alterNameWords = array_merge($alterNameWords, $words);
            }

            // Contar coincidencias en slug, título y alterNames
            $slugMatches = count(array_intersect($queryWords, $animeSlugWords));
            $titleMatches = count(array_intersect($queryWords, $animeTitleWords));
            $alterMatches = count(array_intersect($queryWords, $alterNameWords));

            // Calcular el total de palabras consideradas para el porcentaje
            $totalWords = max(count($queryWords), count($animeSlugWords), count($animeTitleWords), count($alterNameWords));

            // Calcular el porcentaje de coincidencia basado en slug, título y alterNames
            $matchPercentage = $totalWords > 0 ? (($slugMatches + $titleMatches + $alterMatches) / $totalWords) : 0;

            // Solo agregar si tiene algún nivel de coincidencia
            if ($matchPercentage > 0) {
                $rankedResults[] = [
                    'anime' => $anime,
                    'score' => $matchPercentage,
                ];
            }
        }

        // Ordenar los resultados por el porcentaje de coincidencia (de mayor a menor)
        usort($rankedResults, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Extraer solo los objetos Anime
        $sortedAnimes = array_column($rankedResults, 'anime');

        // Paginar manualmente el resultado
        $total = count($sortedAnimes);
        $currentPage = Paginator::resolveCurrentPage();
        $paginatedResults = new LengthAwarePaginator(
            array_slice($sortedAnimes, ($currentPage - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return response()->json($paginatedResults);
    }

    public function search_specific(Request $request)
    {
        $query = $request->query('q');
        $perPage = $request->query('per_page', 20); // Default to 20 items per page

        if (!$query) {
            return response()->json(['error' => 'Query parameter "q" is required'], 400);
        }

        // Limpiar la query
        $decodedQuery = urldecode($query);
        $cleanQuery = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $decodedQuery);
        $cleanQuery = trim(preg_replace('/\s+/', ' ', $cleanQuery));
        $cleanQueryLower = mb_strtolower($cleanQuery);

        // Buscar coincidencia literal en slug, título o alterNames
        $animes = Anime::with(['alterNames', 'genres'])
            ->whereRaw("TRIM(LOWER(REPLACE(REPLACE(slug, '-', ' '), '_', ' '))) = ?", [$cleanQueryLower])
            ->orWhereRaw("TRIM(LOWER(REPLACE(REPLACE(title, '-', ' '), '_', ' '))) = ?", [$cleanQueryLower])
            ->orWhereHas('alterNames', function ($q) use ($cleanQueryLower) {
                $q->whereRaw("TRIM(LOWER(REPLACE(REPLACE(name, '-', ' '), '_', ' '))) = ?", [$cleanQueryLower]);
            })
            ->paginate($perPage);

        return response()->json($animes);
    }

    public function genre(Request $request)
    {
        $query = $request->query('q');
        $perPage = $request->query('per_page', 20); // Default to 20 items per page

        if (!$query) {
            return response()->json(['error' => 'Query parameter "q" is required'], 400);
        }

        // Limpiar y pasar a minúsculas la query
        $decodedQuery = urldecode($query);
        $cleanQuery = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $decodedQuery);
        $genreQuery = mb_strtolower(trim($cleanQuery));

        if (empty($genreQuery)) {
            return response()->json([], 200);
        }

        // Buscar los géneros que coincidan con la query
        $animeIds = \App\Models\AnimeGenre::whereRaw('LOWER(genre) LIKE ?', ['%' . $genreQuery . '%'])
            ->pluck('anime_id')
            ->unique()
            ->toArray();

        if (empty($animeIds)) {
            return response()->json([], 200);
        }

        // Obtener los animes asociados a esos géneros, con relaciones
        $animes = Anime::with(['alterNames', 'genres'])
            ->whereIn('id', $animeIds)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($animes);
    }

    public function withoutAlterNames()
    {
        $anime = Anime::with(['genres'])
            ->doesntHave('alterNames')
            ->first();

        if (!$anime) {
            return response()->json(['message' => 'No anime found without alterNames'], 404);
        }

        return response()->json($anime);
    }

    public function withoutGenres()
    {
        $anime = Anime::with(['alterNames'])
            ->doesntHave('genres')
            ->first();

        if (!$anime) {
            return response()->json(['message' => 'No anime found without genres'], 404);
        }

        return response()->json($anime);
    }

    public function random()
    {
        $anime = Anime::with(['alterNames', 'genres'])->inRandomOrder()->first();

        if (!$anime) {
            return response()->json(['message' => 'No anime found'], 404);
        }

        return response()->json($anime);
    }
}