<?php

use App\Http\Middleware\CloudflareMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'cloudflare' => CloudflareMiddleware::class,
        ]);
        $middleware->api(prepend: [
            SecurityHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Log all API exceptions with request context
        $exceptions->reportable(function (Throwable $e) {
            try {
                $req = request();
                if ($req && $req->is('api/*')) {
                    $context = [
                        'url' => $req->fullUrl(),
                        'method' => $req->method(),
                        'user_id' => $req->user()?->id,
                        'ip' => $req->ip(),
                    ];
                    logger()->error($e->getMessage(), $context);
                } else {
                    logger()->error($e->getMessage());
                }
            } catch (Throwable) {
                // Fallback: log without context
                logger()->error($e->getMessage());
            }
        });
    })->create();
