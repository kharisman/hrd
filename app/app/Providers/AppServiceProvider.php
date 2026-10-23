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
        \Illuminate\Support\Carbon::setLocale('id'); // nama bulan & "x hari yang lalu" dalam Bahasa Indonesia
    }
}
