<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;
use Tests\TestCase;

final class CustomerCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Category $food;

    private Category $home;

    protected function setUp(): void
    {
        parent::setUp();

        $this->food = Category::create(['name' => 'Oziq-ovqat', 'slug' => 'oziq-ovqat']);
        $this->home = Category::create(['name' => 'Uy', 'slug' => 'uy']);
    }

    private function product(array $attributes = []): Product
    {
        $name = $attributes['name'] ?? 'Olma sharbati';

        return Product::create(array_merge([
            'name' => $name,
            'slug' => str($name)->slug().'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => 'Mahsulot haqida batafsil tavsif',
            'price' => 25000,
            'stock' => 10,
            'rating' => 4.5,
            'reviews_count' => 3,
            'status' => 'active',
            'images' => ['https://example.test/product.jpg'],
            'category_id' => $this->food->id,
        ], $attributes));
    }

    public function test_catalog_only_returns_active_products_with_customer_fields(): void
    {
        $active = $this->product();
        $this->product(['name' => 'Yashirin mahsulot', 'status' => 'inactive']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonPath('data.0.category_id', $this->food->id)
            ->assertJsonPath('data.0.category.name', 'Oziq-ovqat')
            ->assertJsonPath('data.0.rating', 4.5)
            ->assertJsonPath('data.0.average_rating', 4.5)
            ->assertJsonPath('data.0.reviews_count', 3);
    }

    public function test_search_finds_full_and_partial_product_names(): void
    {
        $this->product(['name' => 'Tabiiy Olma Sharbati']);
        $this->product(['name' => 'Kir yuvish kukuni', 'category_id' => $this->home->id]);

        $this->getJson('/api/products?filter[name]=Tabiiy%20Olma%20Sharbati')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/products?filter[name]=Olma')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tabiiy Olma Sharbati');
    }

    public function test_category_price_and_sort_filters_work_together(): void
    {
        $this->product(['name' => 'Arzon', 'price' => 15000, 'rating' => 5]);
        $middle = $this->product(['name' => 'Mos mahsulot', 'price' => 30000, 'rating' => 4]);
        $this->product(['name' => 'Qimmat', 'price' => 80000, 'rating' => 3]);
        $this->product([
            'name' => 'Boshqa kategoriya',
            'price' => 28000,
            'rating' => 5,
            'category_id' => $this->home->id,
        ]);

        $url = "/api/products?filter[category_id]={$this->food->id}"
            .'&filter[min_price]=20000&filter[max_price]=50000&sort=-rating';

        $this->getJson($url)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $middle->id);
    }

    public function test_zero_stock_product_remains_visible_but_has_zero_stock(): void
    {
        $product = $this->product(['stock' => 0]);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.stock', 0)
            ->assertJsonPath('data.description', 'Mahsulot haqida batafsil tavsif')
            ->assertJsonPath('data.images.0', 'https://example.test/product.jpg');
    }

    public function test_catalog_page_size_is_safely_bounded(): void
    {
        $this->product();

        $this->getJson('/api/products?per_page=0')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/products?per_page=100000')->assertOk()->assertJsonPath('meta.per_page', 100);
    }
}
