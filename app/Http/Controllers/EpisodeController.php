<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Episode;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{

    public function recent()
    {
        return Episode::with(['sources', 'anime:id,slug,title,image']) // Load extra anime details
        ->orderBy('published_at', 'desc')
            ->paginate(20) // Paginate results (10 per page)
            ->through(function ($episode) {
                return [
                    'id' => $episode->id,
                    'number' => $episode->number,
                    'anime' => [
                        'id' => $episode->anime->id,
                        'slug' => $episode->anime->slug,
                        'title' => $episode->anime->title,
                        'image' => $episode->anime->image,
                    ],
                    'created_at' => $episode->created_at,
                ];
            });
    }


    public function show(Request $request, $anime_slug, $number)
    {
        // Decode the slug
        $decodedSlug = urldecode($anime_slug);

        // Replace spaces with hyphens and convert to lowercase
        $normalizedSlug = strtolower(str_replace(' ', '-', $decodedSlug));

        // Split the slug into words
        $slugWords = explode('-', $normalizedSlug);

        // Build the query to find the anime whose slug contains all the words
        $animeQuery = Anime::query();
        foreach ($slugWords as $word) {
            $animeQuery->where('slug', 'LIKE', '%' . $word . '%');
        }

        // Find the anime
        $anime = $animeQuery->firstOrFail();

        // Find the episode by anime_id and episode number
        $episode = Episode::where('anime_id', $anime->id)
            ->where('number', $number)
            ->with('sources')
            ->firstOrFail();

        $response = $episode->toArray();
        $response['slug'] = $anime_slug;

        return response()->json($response);
    }

}
