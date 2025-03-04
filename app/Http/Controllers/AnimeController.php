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

    public function show(Anime $anime)
    {
        return $anime->load('episodes.sources');
    }

    public function search(Request $request)
    {
        $query = $request->query('q');

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

        // Get unique results
        $results = $animes->get()->unique('id')->values();

        return response()->json($results);
    }


}
