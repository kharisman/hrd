<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Kirim pesan lewat bot Telegram (dipakai OTP login, pesan uji). Meniru BAAK (sendMessage, Markdown).
// Token disimpan terenkripsi di hrd_pengaturan (Pengaturan → Bot Telegram); cadangan: KEY_BOT di .env.
// TELEGRAM_PURA=true di .env: pesan hanya ditulis ke log (pengembangan lokal), tidak dikirim.
class Telegram
{
    public static function token(): ?string
    {
        $enkripsi = Pengaturan::nilai('telegram_token');
        return $enkripsi ? rescue(fn () => Crypt::decryptString($enkripsi), null, false) : env('KEY_BOT');
    }

    public static function simpanToken(string $token): void
    {
        Pengaturan::simpan(['telegram_token' => Crypt::encryptString(trim($token))]);
        cache()->forget('telegram_bot_username');
    }

    // Username bot untuk tautan t.me (di-cache 1 hari supaya halaman tidak menunggu Telegram)
    public function username(): ?string
    {
        $u = cache()->remember('telegram_bot_username', now()->addDay(), fn () => $this->info()['username'] ?? '');
        return $u ?: null;
    }

    // Info bot (getMe). null = token kosong/salah/tidak terhubung
    public function info(): ?array
    {
        if (!$token = self::token()) {
            return null;
        }
        $res = rescue(fn () => Http::timeout(20)->get("https://api.telegram.org/bot{$token}/getMe"), null, false);
        return $res?->json('ok') ? $res->json('result') : null;
    }

    /**
     * Kirim pesan. Return null bila terkirim, atau pesan galat (untuk ditampilkan ke pengguna).
     */
    public function kirim(string $chatId, string $teks): ?string
    {
        if (env('TELEGRAM_PURA')) {
            Log::info("[Telegram PURA] ke {$chatId}: {$teks}");
            return null;
        }
        if (!$token = self::token()) {
            return 'Bot Telegram belum diatur. Hubungi bagian SDM.';
        }
        try {
            $res = Http::timeout(20)->asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => $teks, 'parse_mode' => 'Markdown',
            ]);
            if ($res->json('ok')) {
                return null;
            }
            // 400 chat not found / 403 bot diblokir: pengguna belum /start bot atau chat ID salah
            Log::warning('Telegram gagal', ['chat_id' => $chatId, 'status' => $res->status(), 'desc' => $res->json('description')]);
            return in_array($res->status(), [400, 403])
                ? 'Pesan Telegram tidak bisa dikirim ke akun Anda. Pastikan sudah membuka bot dan menekan Start, atau hubungi bagian SDM.'
                : 'Server Telegram menolak pengiriman. Coba lagi sebentar.';
        } catch (\Throwable $e) {
            report($e);
            return 'Tidak bisa terhubung ke Telegram. Coba lagi sebentar.';
        }
    }
}
