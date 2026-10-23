<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Staff;
use App\Services\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

// Pengguna menghubungkan chat ID Telegram-nya sendiri (untuk login NIP/NIDN + OTP).
// Kepemilikan dibuktikan dengan kode yang dikirim ke chat itu, jadi tidak bisa mendaftarkan chat orang lain.
// Tanpa webhook: aman dipakai bersama bot BAAK.
class TelegramSayaController extends Controller
{
    const BERLAKU_MENIT = 10;

    public function index(Telegram $tg)
    {
        [$staff, $dosen] = $this->milikSaya();
        return view('telegram-saya', [
            'terhubung' => $staff->telegram ?? null ?: $dosen->telegram ?? null,
            'bisa' => (bool) ($staff || $dosen),
            'bot' => $tg->username(),
            'menunggu' => session('tautan_telegram'),
        ]);
    }

    // Langkah 1: kirim kode verifikasi ke chat ID yang dimasukkan
    public function kirim(Request $r, Telegram $tg)
    {
        [$staff, $dosen] = $this->milikSaya();
        abort_unless($staff || $dosen, 403, 'Akun Anda belum tertaut ke data pegawai/dosen.');
        $chat = trim($r->validate(['chat_id' => 'required|regex:/^\d{5,15}$/'], [
            'chat_id.regex' => 'Chat ID berupa 5–15 angka (lihat dari @userinfobot).',
        ])['chat_id']);

        $kunci = 'tautan-telegram:' . auth()->id();
        if (RateLimiter::tooManyAttempts($kunci, 3)) {
            return back()->withErrors('Terlalu sering mengirim kode. Coba lagi dalam ' . RateLimiter::availableIn($kunci) . ' detik.');
        }
        RateLimiter::hit($kunci, 60);

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if ($galat = $tg->kirim($chat, "🔗 *Hubungkan Telegram ke HRD*\n\nKode verifikasi: *{$kode}*\n\n_Masukkan kode ini di halaman Telegram Saya. Abaikan bila Anda tidak memintanya._")) {
            return back()->withInput()->withErrors($galat);
        }
        session(['tautan_telegram' => [
            'chat_id' => $chat, 'hash' => Hash::make($kode), 'salah' => 0,
            'sampai' => now()->addMinutes(self::BERLAKU_MENIT)->timestamp,
        ]]);
        return back()->with('ok', 'Kode verifikasi dikirim ke Telegram Anda. Masukkan kodenya di bawah.');
    }

    // Langkah 2: cocokkan kode → simpan chat ID
    public function verifikasi(Request $r)
    {
        $kode = $r->validate(['kode' => 'required|digits:6'], ['kode.digits' => 'Kode terdiri dari 6 angka.'])['kode'];
        $t = session('tautan_telegram');
        if (!$t || now()->timestamp > $t['sampai']) {
            session()->forget('tautan_telegram');
            return back()->withErrors('Kode sudah kedaluwarsa. Kirim kode baru.');
        }
        if (!Hash::check($kode, $t['hash'])) {
            $t['salah']++;
            if ($t['salah'] >= 5) {
                session()->forget('tautan_telegram');
                return back()->withErrors('Kode salah 5 kali. Silakan kirim kode baru.');
            }
            session(['tautan_telegram' => $t]);
            return back()->withErrors('Kode salah. Sisa percobaan: ' . (5 - $t['salah']) . '.');
        }

        $this->simpan($t['chat_id']);
        session()->forget('tautan_telegram');
        return back()->with('ok', 'Telegram berhasil dihubungkan. Sekarang Anda bisa login dengan NIP/NIDN + kode OTP Telegram.');
    }

    public function putus()
    {
        $this->simpan(null);
        session()->forget('tautan_telegram');
        return back()->with('ok', 'Telegram diputuskan dari akun HRD Anda.');
    }

    public function batal()
    {
        session()->forget('tautan_telegram');
        return back();
    }

    // Data pegawai/dosen milik akun yang login
    private function milikSaya(): array
    {
        $akun = auth('pegawai')->user();
        $staff = $akun->staff_id ? Staff::find($akun->staff_id) : null;
        $dosen = $akun->dosen_id ? Dosen::find($akun->dosen_id) : ($staff ? Dosen::where('staff_id', $staff->id)->first() : null);
        return [$staff, $dosen];
    }

    // Simpan ke semua data orang ini: akun staff ber-NIP sama + data dosennya
    private function simpan(?string $chat): void
    {
        [$staff, $dosen] = $this->milikSaya();
        if ($staff) {
            Staff::where('id', $staff->id)->when($staff->nip, fn ($q) => $q->orWhere('nip', $staff->nip))->update(['telegram' => $chat]);
        }
        $dosen?->update(['telegram' => $chat]);
    }
}
