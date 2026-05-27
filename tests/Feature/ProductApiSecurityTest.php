<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ProductApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clearResolvedInstances();
    }

    public function test_inactive_product_returns_same_not_found_as_missing_product(): void
    {
        $active = Product::factory()->create(['is_active' => true]);
        $inactive = Product::factory()->create(['is_active' => false]);

        $this->getJson('/api/products/'.$inactive->id)
            ->assertNotFound();

        $this->getJson('/api/products/999999')
            ->assertNotFound();

        $this->getJson('/api/products/'.$active->id)
            ->assertOk();
    }

    public function test_api_rate_limit_returns_too_many_requests(): void
    {
        config([
            'api.rate_limit.per_minute' => 2,
            'api.rate_limit.products_per_minute' => 2,
            'api.rate_limit.search_per_minute' => 2,
        ]);

        $this->getJson('/api/categories')->assertOk();
        $this->getJson('/api/categories')->assertOk();
        $this->getJson('/api/categories')->assertTooManyRequests();
    }

    public function test_search_rate_limit_applies_when_query_present(): void
    {
        Product::factory()->create([
            'is_active' => true,
            'title' => 'RateLimitSearchWidget',
        ]);

        config([
            'api.rate_limit.per_minute' => 100,
            'api.rate_limit.products_per_minute' => 100,
            'api.rate_limit.search_per_minute' => 2,
        ]);

        $this->getJson('/api/products?q=RateLimitSearchWidget')->assertOk();
        $this->getJson('/api/products?q=RateLimitSearchWidget')->assertOk();
        $this->getJson('/api/products?q=RateLimitSearchWidget')->assertTooManyRequests();
    }
}
