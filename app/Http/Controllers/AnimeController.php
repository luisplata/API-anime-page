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

        // Limpiamos el slug recibido
        $cleanSlug = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $decodedSlug);
        $cleanSlug = trim(preg_replace('/\s+/', ' ', $cleanSlug)); // Eliminamos espacios duplicados

        // Obtenemos todos los animes
        $animes = Anime::all();

        $bestMatch = null;

        foreach ($animes as $anime) {
            // Limpiamos el slug de la base de datos
            $dbCleanSlug = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $anime->slug);
            $dbCleanSlug = trim(preg_replace('/\s+/', ' ', $dbCleanSlug));

            if (strtolower($cleanSlug) === strtolower($dbCleanSlug)) {
                $bestMatch = $anime;
                break;
            }
        }

        if (!$bestMatch) {
            return response()->json($this->emptyAnimeResponse(), 404);
        }

        return $bestMatch->load(['alterNames', 'genres', 'episodes.sources']);
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

        // Filtrar los animes que contienen al menos una coincidencia en el slug o el título
        $filteredAnimes = Anime::with(['alterNames', 'genres'])
            ->whereRaw('LOWER(slug) LIKE ?', ['%' . $firstWord . '%'])
            ->orWhereRaw('LOWER(slug) LIKE ?', ['%' . $lastWord . '%'])
            ->orWhereRaw('LOWER(title) LIKE ?', ['%' . $firstWord . '%'])
            ->orWhereRaw('LOWER(title) LIKE ?', ['%' . $lastWord . '%'])
            ->get();

        // Array para almacenar los resultados con su puntaje
        $rankedResults = [];

        foreach ($filteredAnimes as $anime) {
            // Limpiar también el slug y título en la base de datos antes de comparar
            $cleanAnimeSlug = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $anime->slug);
            $animeSlugWords = array_map('mb_strtolower', array_filter(explode(' ', trim($cleanAnimeSlug))));

            $cleanAnimeTitle = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $anime->title);
            $animeTitleWords = array_map('mb_strtolower', array_filter(explode(' ', trim($cleanAnimeTitle))));

            // Contar coincidencias en slug y título
            $slugMatches = count(array_intersect($queryWords, $animeSlugWords));
            $titleMatches = count(array_intersect($queryWords, $animeTitleWords));

            // Calcular el total de palabras consideradas para el porcentaje
            $totalWords = max(count($queryWords), count($animeSlugWords), count($animeTitleWords));

            // Calcular el porcentaje de coincidencia basado en slug y título
            $matchPercentage = $totalWords > 0 ? (($slugMatches + $titleMatches) / $totalWords) : 0;

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
}