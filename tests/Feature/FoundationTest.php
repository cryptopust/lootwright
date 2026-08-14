<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_homepage_renders_the_lootwright_shell(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Lootwright')
            ->assertInertia(fn (Assert $page): Assert => $page->component('Welcome'));
    }

    public function test_public_liveness_endpoint_discloses_no_dependencies(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText('OK')
            ->assertDontSee('database')
            ->assertDontSee('redis')
            ->assertDontSee('exception');
    }

    public function test_readiness_endpoint_is_hidden_without_its_token(): void
    {
        config(['services.readiness.token' => 'test-readiness-token']);

        $this->getJson(route('readiness'))->assertNotFound();
    }

    public function test_horizon_is_denied_outside_local_development(): void
    {
        $this->app->instance('env', 'production');

        $this->get('/horizon')->assertForbidden();
    }
}
