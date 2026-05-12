<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_request_id_header(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
        $response->assertHeader('X-Request-Id');
    }

    public function test_health_endpoint_returns_checks(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['checks' => ['db', 'cache']],
                'errors',
                'meta',
            ]);
    }
}
