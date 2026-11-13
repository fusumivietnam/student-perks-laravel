<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustCodespacesForwarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $url = (string) config('app.url');
        $expectedHost = parse_url($url, PHP_URL_HOST);

        // The private HTTPS forwarding host is configured by preview.sh. Only
        // accept its exact value; do not trust arbitrary forwarded host headers.
        if (config('app.env') === 'local'
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && is_string($expectedHost)
            && str_ends_with($expectedHost, '.app.github.dev')
            && $request->headers->get('X-Forwarded-Host') === $expectedHost
            && $request->headers->get('X-Forwarded-Proto') === 'https') {
            $request::setTrustedProxies(
                [$request->server->get('REMOTE_ADDR')],
                Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_PORT,
            );
        }

        return $next($request);
    }
}
