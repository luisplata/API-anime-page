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
        return Anime::orderBy('created_at', 'desc')->paginate(20);
    }

    public function show($anime_slug)
    {
        // Decodificar el slug y limpiar caracteres especiales (reemplazándolos por espacios)
        $decodedSlug = urldecode($anime_slug);
        $cleanSlug = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $decodedSlug);
        $slugWords = array_filter(explode(' ', trim($cleanSlug)));

        if (empty($slugWords)) {
            return response()->json($this->emptyAnimeResponse(), 200);
        }

        // Tomar la primera y última palabra del slug para hacer una consulta más eficiente
        $firstWord = $slugWords[0];
        $lastWord = end($slugWords);

        // Filtrar primero por la primera o última palabra en el slug
        $filteredAnimes = Anime::where('slug', 'LIKE', "%{$firstWord}%")
            ->orWhere('slug', 'LIKE', "%{$lastWord}%")
            ->get();

        $bestMatch = null;
        $bestScore = 0;
        $threshold = 0.7; // 70% de coincidencia

        Log::info("firstWord::" . $firstWord);
        Log::info("lastWord::" . $lastWord);

        foreach ($filteredAnimes as $anime) {
            // Limpiar también el slug en la base de datos antes de comparar (reemplazando caracteres especiales por espacios)
            $cleanAnimeSlug = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $anime->slug);
            $animeWords = array_filter(explode(' ', trim($cleanAnimeSlug)));

            // Contar palabras coincidentes
            $matches = count(array_intersect($slugWords, $animeWords));
            $totalWords = count($slugWords);

            // Calcular el porcentaje de coincidencia
            $matchPercentage = $totalWords > 0 ? ($matches / $totalWords) : 0;

            // Verificar si supera el umbral y es la mejor coincidencia hasta ahora
            if ($matchPercentage >= $threshold && $matchPercentage > $bestScore) {
                $bestScore = $matchPercentage;
                $bestMatch = $anime;
            }
        }

        if (!$bestMatch) {
            return response()->json($this->emptyAnimeResponse(), 404);
        }

        // Cargar los episodios y sus fuentes
        return $bestMatch->load('episodes.sources');
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

        // Obtener la primera y última palabra para filtrar inicialmente
        $firstWord = $queryWords[0];
        $lastWord = end($queryWords);

        // Filtrar los animes que contienen al menos una coincidencia en el slug o el título
        $filteredAnimes = Anime::where('slug', 'LIKE', "%{$firstWord}%")
            ->orWhere('slug', 'LIKE', "%{$lastWord}%")
            ->orWhere('title', 'LIKE', "%{$firstWord}%")
            ->orWhere('title', 'LIKE', "%{$lastWord}%")
            ->get();

        // Array para almacenar los resultados con su puntaje
        $rankedResults = [];

        foreach ($filteredAnimes as $anime) {
            // Limpiar también el slug y título en la base de datos antes de comparar
            $cleanAnimeSlug = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $anime->slug);
            $animeSlugWords = array_filter(explode(' ', trim($cleanAnimeSlug)));

            $cleanAnimeTitle = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ]+/u', ' ', $anime->title);
            $animeTitleWords = array_filter(explode(' ', trim($cleanAnimeTitle)));

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



}
