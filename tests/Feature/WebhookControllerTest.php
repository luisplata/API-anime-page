<?php

namespace Tests\Feature;

use App\Models\Anime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_creates_new_anime_and_episodes()
    {
        $payload = [
            [
                'name' => ['Attack on Titan'],
                'slug' => 'attack-on-titan',
                'description' => 'A story about Titans.',
                'image' => 'https://example.com/image.jpg',
                'caps' => [
                    [
                        'title' => 'Episode 1',
                        'number' => 1,
                        'link' => 'https://example.com/ep1',
                        'source' => [
                            ['name' => 'Source1', 'url' => 'https://source1.com/stream']
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook', $payload, [
            'X-Webhook-Token' => env('WEBHOOK_SECRET'),
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Anime y episodios guardados exitosamente']);

        $this->assertDatabaseHas('animes', ['slug' => 'attack-on-titan']);
        $this->assertDatabaseHas('episodes', ['number' => 1]);
        $this->assertDatabaseHas('episode_sources', ['name' => 'Source1']);
    }

    public function test_webhook_does_not_duplicate_existing_anime()
    {
        $anime = Anime::factory()->create(['slug' => 'one-piece']);

        $payload = [
            [
                'name' => ['One Piece'],
                'slug' => 'one-piece',
                'description' => 'A pirate adventure.',
                'image' => 'https://example.com/image.jpg',
                'caps' => [
                    [
                        'title' => 'Episode 1',
                        'number' => 1,
                        'link' => 'https://example.com/ep1',
                        'source' => [
                            ['name' => 'Source1', 'url' => 'https://source1.com/stream']
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook', $payload, [
            'X-Webhook-Token' => env('WEBHOOK_SECRET'),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseCount('animes', 1);
        $this->assertDatabaseHas('episodes', ['anime_id' => $anime->id]);
    }

    public function test_webhook_requires_valid_token()
    {
        $payload = [
            [
                'name' => ['Naruto'],
                'slug' => 'naruto',
                'description' => 'A ninja story.',
                'image' => 'https://example.com/image.jpg',
                'caps' => [
                    [
                        'title' => 'Episode 1',
                        'number' => 1,
                        'link' => 'https://example.com/ep1',
                        'source' => [
                            ['name' => 'Source1', 'url' => 'https://source1.com/stream']
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook', $payload, [
            'X-Webhook-Token' => 'invalid_token',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_webhook_returns_validation_error_on_invalid_payload()
    {
        $invalidPayload = [
            [
                'name' => ['One Piece'],
                'slug' => 'one-piece',
                'description' => 'A pirate adventure.',
                'caps' => [
                    [
                        'title' => 'Episode 1',
                        'number' => 'invalid_number', // Should be integer
                        'link' => 'invalid_link', // Should be a valid URL
                        'source' => [
                            ['name' => 'Source1', 'url' => 'not_a_url']
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook', $invalidPayload, [
            'X-Webhook-Token' => env('WEBHOOK_SECRET'),
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'messages']);
    }
}
