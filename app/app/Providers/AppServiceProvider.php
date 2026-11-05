<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Folder public diganti nama menjadi public_html (sesuai hosting)
        $this->app->bind('path.public', fn () => base_path('public_html'));
    }

    public function boot()
    {
        Paginator::useBootstrapFive();
        config(['dompdf.public_path' => public_path()]); // dompdf default ke folder public, di sini public_html
        \Illuminate\Support\Carbon::setLocale('id'); // nama bulan & "x hari yang lalu" dalam Bahasa Indonesia
        // Audit: login & logout (semua jalur: password eUniversity maupun OTP)
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, fn () => \App\Models\AuditLog::catat('login'));
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, fn () => \App\Models\AuditLog::catat('logout'));
    }
}
