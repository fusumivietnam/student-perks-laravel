<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Forwarded HTTPS information is trusted only for this private preview.
        // Unlike URL::useOrigin(), this also corrects session intended URLs.
        $appUrl = (string) config('app.url');
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (config('app.env') === 'local'
            && parse_url($appUrl, PHP_URL_SCHEME) === 'https'
            && is_string($host)
            && str_ends_with($host, '.app.github.dev')) {
            $middleware->trustProxies(
                at: '*',
                headers: Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_PORT,
            );
            $middleware->trustHosts(at: [
                '^'.preg_quote($host, '/').'\z',
                '^localhost\z',
                '^127\.0\.0\.1\z',
            ], subdomains: false);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
