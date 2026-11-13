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
        // Codespaces forwards public HTTPS through an internal HTTP endpoint.
        // Trust proxy headers only in the explicitly configured private preview,
        // so Filament's session "intended" redirects use the public origin too.
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
                '^'.preg_quote($host, '/').'
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
,
                '^localhost
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
,
                '^127\\.0\\.0\\.1
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
,
            ], subdomains: false);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
