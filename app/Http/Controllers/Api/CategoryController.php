<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $cached = Cache::remember(
            'api:categories:index:v2',
            now()->addMinutes(10),
            function () {
                return Category::query()
                    ->select([
                        'id',
                        'title',
                        'products_count',
                    ])
                    ->orderBy('title')
                    ->get()
                    ->map(fn (Category $category) => $category->getAttributes())
                    ->all();
            }
        );

        return CategoryResource::collection(
            Category::hydrate($cached)
        );
    }
}