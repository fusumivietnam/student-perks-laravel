<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Response;

class RejectUntrustedHost
{
    public function handle(Request $request, Closure $next): Response
    {
        // Laravel TrustHosts configures the trusted host patterns, but host
        // validation is lazy until getHost() is invoked. Ensure even routes
        // that never generate absolute URLs reject an unapproved Host.
        if (config('security.trusted_hosts', []) !== []) {
            try {
                $request->getHost();
            } catch (SuspiciousOperationException) {
                return response('Bad Request', 400);
            }
        }

        return $next($request);
    }
}
