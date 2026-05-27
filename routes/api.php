<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\ThrottleSearchRequests;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/products', [ProductController::class, 'index'])
        ->middleware(['throttle:api-products', ThrottleSearchRequests::class]);

    Route::get('/products/{product}', [ProductController::class, 'show']);
});
