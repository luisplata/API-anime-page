<?php

use App\Http\Controllers\AnimeController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\LastPaginationController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\VerifyWebhookToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CapReportController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/animes', [AnimeController::class, 'index']);
Route::get('/anime/{anime}', [AnimeController::class, 'show']);
Route::get('/episodes', [EpisodeController::class, 'recent']);
Route::get('/episodes/{anime_slug}-{number}', [EpisodeController::class, 'show'])
    ->where('anime_slug', '[a-zA-Z0-9%\-]+')
    ->where('number', '[0-9]+');


Route::get('/animes/search', [AnimeController::class, 'search']);
Route::get('/animes/genre', [AnimeController::class, 'genre']);

Route::get('/animes/without-alternames', [AnimeController::class, 'withoutAlterNames']);
Route::get('/animes/without-genres', [AnimeController::class, 'withoutGenres']);
Route::get('/animes/random', [AnimeController::class, 'random']);

Route::middleware(VerifyWebhookToken::class)->prefix('webhook')->group(function () {
    Route::post('/last-pagination', [LastPaginationController::class, 'store']);
    Route::get('/last-pagination/{type}', [LastPaginationController::class, 'show']);
    Route::post('/send-animes-today', [WebhookController::class, 'sendAnimeToday']);
    Route::post('/send-anime-full', [WebhookController::class, 'sendAnimeFull']);

    Route::post('/cap-report', [CapReportController::class, 'store']);
    Route::get('/cap-reports', [CapReportController::class, 'index']);
    Route::get('/cap-reports/episode/{episode_id}', [CapReportController::class, 'byEpisode']);
    Route::patch('/cap-report/{id}/resolve', [CapReportController::class, 'resolve']);

    Route::get('/', function () {
        return response()->json(['message' => 'Token válido'], 200);
    });
});

