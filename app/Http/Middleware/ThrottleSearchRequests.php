<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ThrottleSearchRequests
{
    public function __construct(
        protected ThrottleRequests $throttle,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->filled('q')) {
            return $next($request);
        }

        return $this->throttle->handle($request, $next, 'api-search');
    }
}
