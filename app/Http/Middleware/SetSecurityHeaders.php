<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Legacy security policy trough XSS
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content security policy
        $csp = "default-src 'self'; " .
                "script-src 'self' 'unsafe-eval' 'unsafe-inline' http://localhost:5173; " .
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; " .
                "font-src 'self' https://fonts.bunny.net; " .
                "img-src 'self' data: https:; " .
                "connect-src 'self' ws://localhost:5173 http://localhost:5173; " .
                "frame-ancestors 'none';";

        $response->headers->set('Content-Security-Policy', $csp);
        
    return $response;
    }
}
