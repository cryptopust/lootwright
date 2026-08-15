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
            ->assertInertia(fn (Assert $page): Assert => $page->component('Landing'));
    }

    public function test_product_pages_render_without_protected_publisher_assets(): void
    {
        $pages = [
            '/analyses/new' => 'Analysis/New',
            '/analyses/demo/import' => 'Analysis/ImportReview',
            '/analyses/demo/overview' => 'Analysis/Workspace',
            '/analyses/demo/findings' => 'Analysis/Workspace',
            '/analyses/demo/upgrades' => 'Analysis/Workspace',
            '/analyses/demo/trade' => 'Analysis/Workspace',
            '/analyses/demo/provenance' => 'Analysis/Workspace',
            '/analyses/demo/states' => 'Analysis/Workspace',
            '/privacy' => 'Information',
            '/data-deletion' => 'Information',
            '/methodology' => 'Information',
            '/limitations' => 'Information',
            '/non-affiliation' => 'Information',
            '/usage' => 'Usage',
            '/funding' => 'Funding',
        ];

        foreach ($pages as $path => $component) {
            $this->get($path)
                ->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page->component($component));
        }
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
