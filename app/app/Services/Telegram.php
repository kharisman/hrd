<?php

namespace App\Services;

use App\Models\Akun;
use Illuminate\Support\Facades\Log;

// Notifikasi Telegram lewat API eUniversity (POST /notifikasi): bot & chat ID diurus eUniversity, HRD cukup kirim username.
// Gagal kirim tidak menggagalkan proses pengajuan, cukup dicatat di log.
// ponytail: kirim sinkron saat pengajuan diproses; antrekan bila API lambat
class Telegram
{
    public static function kirim($akun, string $teks, ?array $tautan = null, ?string $kunci = null): ?array
    {
        // Terima Akun, koleksi Akun, atau daftar username
        $username = collect($akun instanceof Akun ? [$akun] : $akun)->map(fn ($a) => $a instanceof Akun ? $a->username : $a)->filter()->unique()->values();
        if ($username->isEmpty() || !config('services.pct.key')) {
            return null;
        }
        try {
            $hasil = null;
            foreach ($username->chunk(50)->values() as $i => $bagian) { // maks. 50 penerima per permintaan
                $hasil = app(PctApi::class)->notifikasi($bagian->all(), $teks, $tautan, $kunci && $i ? "{$kunci}-{$i}" : $kunci);
                if (!empty($hasil['error']) || !empty($hasil['dilewati']) || !empty($hasil['gagal'])) {
                    Log::info('Notifikasi Telegram', ['kunci' => $kunci, 'hasil' => $hasil]);
                }
            }
            return $hasil;
        } catch (\Throwable $e) {
            Log::warning('Notifikasi Telegram gagal: ' . $e->getMessage());
            return null;
        }
    }
}
