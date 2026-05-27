<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API rate limits (requests per minute, per IP)
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
        'products_per_minute' => (int) env('API_PRODUCTS_RATE_LIMIT_PER_MINUTE', 30),
        'search_per_minute' => (int) env('API_SEARCH_RATE_LIMIT_PER_MINUTE', 15),
    ],

];
