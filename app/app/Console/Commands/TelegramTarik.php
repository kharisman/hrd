<?php

namespace App\Console\Commands;

use App\Models\Akun;
use App\Services\Telegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

// Ambil pesan baru ke bot (long polling, tanpa webhook → jalan juga di server lokal) dan tautkan akun dari "/start <kode>"
class TelegramTarik extends Command
{
    protected $signature = 'telegram:tarik';
    protected $description = 'Tautkan akun HRD dengan chat Telegram dari pesan /start ke bot';

    public function handle(): int
    {
        if (!Telegram::aktif()) {
            $this->warn('TELEGRAM_BOT_TOKEN belum diisi di .env');
            return self::SUCCESS;
        }
        $res = Telegram::api('getUpdates', ['offset' => Cache::get('telegram_offset', 0), 'timeout' => 0]);
        $n = 0;
        foreach ($res['result'] ?? [] as $u) {
            Cache::forever('telegram_offset', $u['update_id'] + 1);
            $teks = trim($u['message']['text'] ?? '');
            $chat = $u['message']['chat']['id'] ?? null;
            if (!$chat || !str_starts_with($teks, '/start')) {
                continue;
            }
            $akun = ($kode = trim(substr($teks, 6))) ? Akun::where('telegram_kode', $kode)->first() : null;
            if (!$akun) {
                Telegram::api('sendMessage', ['chat_id' => $chat, 'text' => 'Kode tidak dikenal. Buka aplikasi HRD → Pengajuan → "Hubungkan Telegram".']);
                continue;
            }
            $akun->update(['telegram_chat_id' => (string) $chat, 'telegram_kode' => null]);
            Telegram::kirim($akun, "✅ Terhubung sebagai <b>" . e($akun->name) . "</b>. Notifikasi pengajuan HRD akan dikirim ke sini.");
            $n++;
        }
        $this->info("{$n} akun ditautkan.");
        return self::SUCCESS;
    }
}
