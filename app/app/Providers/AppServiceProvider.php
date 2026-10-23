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
    }
}
