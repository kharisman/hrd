<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Impor chat ID Telegram dari database spmi/BAAK (survei.nips.telegram) ke pegawai HRD, dicocokkan lewat NIP.
// Hanya mengisi yang masih kosong. php artisan hrd:impor-telegram [--db=survei]
Artisan::command('hrd:impor-telegram {--db=survei}', function () {
    $db = $this->option('db');
    $sumber = DB::table(DB::raw("`{$db}`.`nips`"))->where('telegram', '<>', '')->whereNotNull('telegram')->pluck('telegram', 'nip');
    $n = 0;
    foreach (App\Models\Staff::whereNull('telegram')->whereNotNull('nip')->get() as $s) {
        if ($chat = $sumber[$s->nip] ?? null) {
            $s->update(['telegram' => trim($chat)]);
            $n++;
        }
    }
    // Dosen yang tertaut ke pegawai ikut memakai chat ID pegawainya
    $d = App\Models\Dosen::whereNull('telegram')->whereNotNull('staff_id')->get()
        ->each(fn ($x) => ($chat = App\Models\Staff::whereKey($x->staff_id)->value('telegram')) && $x->update(['telegram' => $chat]))->count();
    $this->info("Chat ID diisi untuk {$n} akun pegawai; dosen tertaut diperiksa: {$d}. Sumber: {$db}.nips ({$sumber->count()} chat ID).");
})->purpose('Impor chat ID Telegram dari survei.nips (BAAK) ke pegawai HRD lewat NIP');

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