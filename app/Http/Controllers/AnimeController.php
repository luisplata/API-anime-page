<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use Illuminate\Http\Request;

class AnimeController extends Controller
{
    public function index()
    {
        return Anime::orderBy('created_at', 'desc')->paginate(20);
    }

    public function show($anime_slug)
    {
        // Decodificar el slug
        $decodedSlug = urldecode($anime_slug);

        // Dividir el slug en palabras
        $slugWords = explode(' ', $decodedSlug);

        // Construir la consulta para buscar el anime cuyo slug contenga todas las palabras
        $animeQuery = Anime::query();
        foreach ($slugWords as $word) {
            $animeQuery->where('slug', 'LIKE', '%' . $word . '%');
        }

        // Encontrar el anime
        $anime = $animeQuery->firstOrFail();

        // Cargar los episodios y sus fuentes
        return $anime->load('episodes.sources');
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        $perPage = $request->query('per_page', 20); // Default to 20 items per page

        if (!$query) {
            return response()->json(['error' => 'Query parameter "q" is required'], 400);
        }

        // Split the query into words
        $keywords = explode(' ', trim($query));

        // Start the query
        $animes = Anime::query();

        foreach ($keywords as $word) {
            $animes->orWhere('title', 'LIKE', "%{$word}%")
                ->orWhere('slug', 'LIKE', "%{$word}%");
        }

        // Get paginated results
        $results = $animes->paginate($perPage);

        return response()->json($results);
    }


}
