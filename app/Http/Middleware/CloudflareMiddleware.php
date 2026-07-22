<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CloudflareMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $proxySecret = config('services.cloudflare.proxy_secret', '');

        if (!empty($proxySecret)) {
            $header = $request->header('X-Proxy-Secret');
            if ($header !== $proxySecret) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        return $next($request);
    }
}
