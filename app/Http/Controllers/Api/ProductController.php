<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductListingQuery;

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

        $listing = ProductListingQuery::make(
            $data,
            $perPage,
            $priceMin,
            $priceMax,
            $includeCategories,
        );

        return ProductListResource::collection($listing->paginate());
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load('categories:id,title,products_count');

        return new ProductResource($product);
    }
}
