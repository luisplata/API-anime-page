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
        ->orderBy('created_at', 'desc')
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
        // Find the anime by slug
        $anime = Anime::where('slug', $anime_slug)->firstOrFail();

        // Find the episode by anime_id and episode number
        $episode = Episode::where('anime_id', $anime->id)
            ->where('number', $number)
            ->with('sources')
            ->firstOrFail();

        return response()->json($episode);
    }

}
