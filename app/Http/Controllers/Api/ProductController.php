<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request)
    {
        $data = $request->validated();

        $perPage = $data['per_page'] ?? 50;

        $priceMin = isset($data['price_min'])
            ? (int) round($data['price_min'] * 100)
            : null;

        $priceMax = isset($data['price_max'])
            ? (int) round($data['price_max'] * 100)
            : null;

        $includeCategories = str_contains($data['include'] ?? '', 'categories');

        $query = Product::query()
            ->select([
                'id',
                'price',
                'title',
                'image',
                'is_active',
                'created_at',
            ])
            ->where('is_active', true)
            ->when(
                $includeCategories,
                fn ($query) => $query->with('categories:id,title,products_count')
            )
            ->when(
                $priceMin !== null,
                fn ($query) => $query->where('price', '>=', $priceMin)
            )
            ->when(
                $priceMax !== null,
                fn ($query) => $query->where('price', '<=', $priceMax)
            )
            ->when(
                !empty($data['q']),
                fn ($query) => $query->whereFullText(['title', 'content'], $data['q'])
            )
            ->when(
                !empty($data['category_ids']),
                fn ($query) => $query->whereIn('id', function ($subquery) use ($data) {
                    $subquery
                        ->select('product_id')
                        ->from('category_product')
                        ->whereIn('category_id', $data['category_ids']);
                })
            )
            ->orderBy('id');

        return ProductListResource::collection(
            $query->cursorPaginate($perPage)->withQueryString()
        );
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load('categories:id,title,products_count');

        return new ProductResource($product);
    }
}