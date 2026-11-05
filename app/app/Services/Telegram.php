<?php

namespace App\Services;

use App\Models\Akun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bot Telegram untuk notifikasi. Tanpa TELEGRAM_BOT_TOKEN semua kiriman diam-diam dilewati.
// ponytail: kirim sinkron (timeout 5 dtk), pindah ke queue bila penerima banyak / bot lambat
class Telegram
{
    public static function aktif(): bool
    {
        return (bool) config('services.telegram.token');
    }

    public static function api(string $metode, array $data = []): ?array
    {
        if (!self::aktif()) {
            return null;
        }
        try {
            return Http::timeout(5)->post('https://api.telegram.org/bot' . config('services.telegram.token') . "/{$metode}", $data)->json();
        } catch (\Throwable $e) {
            Log::warning("Telegram {$metode} gagal: " . $e->getMessage());
            return null;
        }
    }

    // Kirim ke akun-akun yang sudah menautkan Telegram (teks HTML Telegram: <b>, <i>, <a>)
    public static function kirim($akun, string $teks): void
    {
        collect($akun instanceof Akun ? [$akun] : $akun)->pluck('telegram_chat_id')->filter()->unique()
            ->each(fn ($chat) => self::api('sendMessage', ['chat_id' => $chat, 'text' => $teks, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true]));
    }
}
