<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_serves_the_vue_shell(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="aag-app"', escape: false)
            ->assertSee(config('company.website'));
    }

    public function test_inertia_requests_to_home_trigger_full_navigation(): void
    {
        $this->get(route('home'), ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('home'));
    }

    public function test_missing_public_pages_serve_the_vue_shell_with_a_404(): void
    {
        $this->get('/missing/page')
            ->assertNotFound()
            ->assertSee('id="aag-app"', escape: false);
    }

    public function test_missing_api_and_asset_paths_do_not_serve_the_vue_shell(): void
    {
        $this->getJson('/api/missing')->assertNotFound()->assertJsonStructure(['message']);
        $this->get('/assets/missing.png')->assertNotFound()->assertDontSee('id="aag-app"', escape: false);
    }
}
