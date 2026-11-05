<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Cegah clickjacking & MIME sniffing, batasi referrer; HSTS hanya bila HTTPS
class HeaderKeamanan
{
    public function handle(Request $request, Closure $next)
    {
        $res = $next($request);
        $res->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $res->headers->set('X-Content-Type-Options', 'nosniff');
        $res->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        if ($request->isSecure()) {
            $res->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        return $res;
    }
}
