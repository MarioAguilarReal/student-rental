<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Iteración 8 security review: headers every page gets. The pages are never framed by other
 * sites (clickjacking), the browser never guesses content types, full URLs never leak to
 * other sites, and only the camera (inspection photos) and location may be requested.
 * HSTS is left to the HTTPS terminator of the future infrastructure (D-44).
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=(), payment=()');

        return $response;
    }
}
