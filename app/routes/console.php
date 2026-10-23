<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

// php artisan hrd:admin "Nama" email@palcomtech.ac.id
Artisan::command('hrd:admin {name} {email}', function ($name, $email) {
    $password = $this->secret('Password');
    User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make($password)]);
    $this->info("Admin HRD $email siap.");
})->purpose('Buat / reset akun admin HRD');

// Sinkron dari terminal memakai akun eUniversity orang yang menjalankan (tidak ada akun layanan).
// Akun perlu izin staff.read & dosen.read. Token dicabut setelah selesai.
Artisan::command('hrd:sync {username?}', function (?string $username = null) {
    $api = app(App\Services\PctApi::class);
    $profil = $api->login($username ?? $this->ask('Username eUniversity'), $this->secret('Password'));
    if (!$profil) {
        return $this->error('Username atau password salah.');
    }
    try {
        $akun = new App\Models\Akun(['scopes' => $profil['scopes']]);
        if (!$akun->bisa(['staff.read', 'dosen.read'])) {
            return $this->error('Akun ini tidak punya izin staff.read & dosen.read.');
        }
        app()->instance(App\Services\PctApi::class, $api->pakaiToken($profil['token']));
        $this->info('Menarik data staff & dosen dari API eUniversity...');
        foreach (app(App\Services\Sinkron::class)->jalankan() as $k => $v) {
            $this->line(str_pad($k, 24) . $v);
        }
    } finally {
        $api->logout($profil['token']);
    }
})->purpose('Sinkron master staff & dosen dari API eUniversity (pakai akun eUniversity Anda)');