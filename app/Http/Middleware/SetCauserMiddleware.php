<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCauserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        ActivityLogContext::setCauser($request->user());

        return $next($request);
    }
}
