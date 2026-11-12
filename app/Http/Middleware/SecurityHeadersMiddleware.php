<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Apply security headers to protect against clickjacking, MIME-sniffing, XSS, and information disclosure.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Clickjacking protection: only allow same origin to frame
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent browser from MIME-sniffing away from declared content-type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Legacy XSS filter for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Restrict referrer information passed to third-party domains
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict access to sensitive browser features/hardware
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Hide server details
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}

