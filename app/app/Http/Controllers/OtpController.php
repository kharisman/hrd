<?php

namespace App\Http\Controllers;

use App\Services\PctApi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

// Login NIP / NIDN / username + kode OTP Telegram. Kode, chat ID, batas percobaan & rate limit
// semuanya diurus eUniversity (/auth/otp/*); hasilnya token eUniversity milik pegawai itu sendiri,
// jadi sesi OTP setara login password.
class OtpController extends Controller
{
    const BERLAKU_MENIT = 5;

    const PESAN = [
        'not_found' => 'NIP / NIDN / username tidak ditemukan atau akunnya tidak aktif.',
        'telegram_belum_terhubung' => 'Telegram Anda belum terdaftar di eUniversity. Hubungi admin eUniversity.',
        'terlalu_sering' => 'Terlalu sering meminta kode (maks. 3 kali per 15 menit). Coba lagi nanti.',
        'kode_salah' => 'Kode OTP salah.',
        'kode_tidak_berlaku' => 'Kode OTP sudah tidak berlaku. Minta kode baru.',
        'terkunci' => 'Kode salah 5 kali dan dikunci. Minta kode baru.',
    ];

    public function kirim(Request $r, PctApi $api)
    {
        $id = trim($r->validate(['identitas' => 'required|string|max:50'], [], ['identitas' => 'NIP / NIDN'])['identitas']);

        try {
            $galat = $api->otpKirim($id);
        } catch (ConnectionException|RequestException $e) {
            report($e);
            return $this->gagal('Server eUniversity tidak bisa dihubungi. Coba lagi beberapa saat.');
        }
        if ($galat) {
            // Satu NIP dipakai beberapa akun → pegawai memilih username-nya
            return $galat['error'] === 'ambigu'
                ? $this->gagal('NIP/NIDN ini dipakai lebih dari satu akun. Pilih username Anda.')->with('kandidat', $galat['data']['kandidat'] ?? [])
                : $this->gagal(self::PESAN[$galat['error']] ?? $galat['pesan'] ?? 'Kode OTP tidak bisa dikirim.');
        }

        session(['otp_identitas' => $id]);
        return redirect()->route('login.otp')->with('ok', 'Kode OTP telah dikirim ke Telegram Anda.');
    }

    public function formKode()
    {
        if (!session('otp_identitas')) {
            return $this->gagal('Sesi kedaluwarsa. Silakan masukkan NIP/NIDN lagi.');
        }
        return view('login-otp', ['nama' => session('otp_identitas'), 'menit' => self::BERLAKU_MENIT]);
    }

    public function verifikasi(Request $r, PctApi $api)
    {
        $kode = $r->validate(['kode' => 'required|digits:6'], ['kode.digits' => 'Kode OTP terdiri dari 6 angka.'])['kode'];
        if (!$id = session('otp_identitas')) {
            return $this->gagal('Sesi kedaluwarsa. Silakan masukkan NIP/NIDN lagi.');
        }

        try {
            [$profil, $galat] = $api->otpVerifikasi($id, $kode);
        } catch (ConnectionException|RequestException $e) {
            report($e);
            return back()->with('error', 'Server eUniversity tidak bisa dihubungi. Coba lagi.');
        }
        if ($galat) {
            $pesan = self::PESAN[$galat['error']] ?? $galat['pesan'] ?? 'Kode OTP ditolak.';
            // Kode mati → kembali ke awal untuk minta kode baru
            return in_array($galat['error'], ['kode_tidak_berlaku', 'terkunci', 'not_found'])
                ? $this->gagal($pesan)->withInput(['identitas' => $id])
                : back()->with('error', $pesan);
        }

        session()->forget('otp_identitas');
        return app(AuthController::class)->masuk($r, $api, $profil);
    }

    private function gagal(string $pesan)
    {
        return redirect()->route('login')->withInput(request()->only('identitas'))->with('error', $pesan)->with('tab', 'otp');
    }
}
