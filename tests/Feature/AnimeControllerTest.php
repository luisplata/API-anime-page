<?php

namespace Tests\Feature;

use App\Models\Anime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_anime_list()
    {
        Anime::factory()->count(30)->create();

        $response = $this->getJson('/api/animes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data', 'links', 'per_page'
            ])
            ->assertJsonCount(20, 'data');
    }

    public function test_it_returns_anime_by_slug()
    {
        $anime = Anime::factory()->create(['slug' => 'one-piece']);

        $response = $this->getJson('/api/anime/one-piece');

        $response->assertStatus(200)
            ->assertJsonFragment(['slug' => 'one-piece']);
    }

    public function test_it_returns_empty_response_for_invalid_slug()
    {
        $response = $this->getJson('/api/anime/non-existent-anime');

        $response->assertStatus(404)
            ->assertJsonFragment(['id' => null]);
    }

    public function test_it_returns_search_results_sorted_by_relevance()
    {
        $anime1 = Anime::factory()->create(['title' => 'Attack on Titan']);
        $anime2 = Anime::factory()->create(['title' => 'Titan boku no Quest']);

        $response = $this->getJson('/api/animes/search?q=Titan');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.0.title', 'Attack on Titan');
    }
}
