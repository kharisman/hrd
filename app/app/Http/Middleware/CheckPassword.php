<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // if (auth()->check()) {
        //     return $next($request);
        // }

        // Cek apakah password sudah ada di session dan valid
        if (session()->has('otp_verified') && session('otp_verified') === true) {
            return $next($request);
        }

        // Jika tidak ada password dalam session atau password tidak valid
        // Redirect untuk memasukkan password
        if ($request->is('pub/ppepp*') or $request->is('pub/dashboard*')) {
            return redirect()->route('password.form');
        }

        return $next($request);
    }
}
