<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Sinkron memakai token aplikasi HRD (POST /auth/token, cukup X-API-Key): bisa jalan terjadwal tanpa password orang.
Artisan::command('hrd:sync', function () {
    $api = app(App\Services\PctApi::class);
    $token = $api->tokenAplikasi();
    try {
        app()->instance(App\Services\PctApi::class, $api->pakaiToken($token));
        $this->info('Menarik data staff & dosen dari API eUniversity...');
        foreach (app(App\Services\Sinkron::class)->jalankan() as $k => $v) {
            $this->line(str_pad($k, 24) . $v);
        }
    } finally {
        $api->logout($token);
    }
})->purpose('Sinkron master staff & dosen dari API eUniversity (token aplikasi HRD)');
