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
        $incomingHost = $request->getHost();

        // Only a private Codespaces preview may normalize the internal HTTP
        // request to its configured public HTTPS origin. Ignore arbitrary
        // forwarded host values and never apply this to Production.
        if (config('app.env') === 'local'
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && is_string($expectedHost)
            && str_ends_with($expectedHost, '.app.github.dev')
            && (in_array($incomingHost, [$expectedHost, 'localhost', '127.0.0.1'], true)
                || $request->headers->get('X-Forwarded-Host') === $expectedHost)) {
            // Redirector::guest() stores Request::fullUrl() in the session.
            // URL::useOrigin() fixes generated links, but not that intended URL.
            // This also covers Codespaces forwards without X-Forwarded-* headers.
            $request->headers->set('host', $expectedHost);
            $request->server->set('HTTP_HOST', $expectedHost);
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', '443');
        }

        return $next($request);
    }
}
