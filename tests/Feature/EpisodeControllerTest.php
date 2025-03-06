<?php

namespace Tests\Feature;

use App\Models\Anime;
use App\Models\Episode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpisodeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_returns_paginated_episodes()
    {
        Anime::factory()->create();
        Episode::factory()->count(25)->create();

        $response = $this->getJson('/api/episodes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data', 'links', 'per_page'
            ])
            ->assertJsonCount(20, 'data');
    }

    public function test_show_returns_correct_episode()
    {
        $anime = Anime::factory()->create([
            'title' => 'One Piece',
            'slug' => 'one-piece'
        ]);

        $episode = Episode::factory()->create([
            'anime_id' => $anime->id,
            'number' => 5
        ]);

        $response = $this->getJson('/api/anime/one-piece');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $episode->id, 'number' => 5]);
    }

    public function test_show_returns_404_for_invalid_episode()
    {
        Anime::factory()->create(['slug' => 'naruto']);

        $response = $this->getJson('/api/animes/naruto/episode/999');

        $response->assertStatus(404);
    }

    public function test_show_normalizes_slug_correctly()
    {
        $anime = Anime::factory()->create([
            'title' => 'Attack on Titan',
            'slug' => 'attack-on-titan'
        ]);

        $episode = Episode::factory()->create([
            'anime_id' => $anime->id,
            'number' => 1
        ]);

        $response = $this->getJson('/api/anime/attack-on-titan');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $episode->id]);
    }
}
