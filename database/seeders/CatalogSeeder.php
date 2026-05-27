<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $categories = collect([
                'Electronics',
                'Books',
                'Home & Garden',
                'Fashion',
                'Sports',
                'Toys',
                'Beauty',
                'Automotive',
                'Food',
                'Office',
            ])->map(function (string $title) {
                return Category::create([
                    'title' => $title,
                    'products_count' => 0,
                ]);
            });

            Product::factory()
                ->count(100)
                ->create()
                ->each(function (Product $product) use ($categories) {
                    $categoryIds = $categories
                        ->random(rand(1, 3))
                        ->pluck('id')
                        ->values()
                        ->all();

                    $product->categories()->attach($categoryIds);
                });

            $this->recalculateCategoryProductCounts();
        });

        Product::makeAllSearchable();
    }

    private function recalculateCategoryProductCounts(): void
    {
        Category::query()->update([
            'products_count' => 0,
        ]);

        Category::query()
            ->select('id')
            ->each(function (Category $category) {
                $category->update([
                    'products_count' => $category->products()->count(),
                ]);
            });
    }
}
