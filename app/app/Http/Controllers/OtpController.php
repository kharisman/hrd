<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Dosen;
use App\Models\Otp;
use App\Models\Pengaturan;
use App\Models\Staff;
use App\Services\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

// Login dengan NIP (pegawai) / NIDN (dosen) + kode OTP via Telegram. Meniru alur BAAK:
// 1) masukkan NIP/NIDN → kode 6 digit dikirim ke Telegram, 2) masukkan kode → masuk.
// Kode disimpan ter-hash, berlaku 5 menit, sekali pakai, maks 5 kali salah. Tanpa token eUniversity:
// aksi yang menulis ke eUniversity tetap terkunci untuk sesi ini.
class OtpController extends Controller
{
    const BERLAKU_MENIT = 5;
    const MAKS_SALAH = 5;

    public function kirim(Request $r, Telegram $telegram)
    {
        abort_unless(Pengaturan::nilai('otp_aktif', '1') === '1', 404);
        $id = trim($r->validate(['identitas' => 'required|string|max:30'], [], ['identitas' => 'NIP / NIDN'])['identitas']);

        // Batasi pengiriman: 3 kali per menit per NIP/NIDN + IP
        $kunci = 'otp-kirim:' . strtolower($id) . '|' . $r->ip();
        if (RateLimiter::tooManyAttempts($kunci, 3)) {
            return back()->withInput()->with('error', 'Terlalu sering meminta kode. Coba lagi dalam ' . RateLimiter::availableIn($kunci) . ' detik.')->with('tab', 'otp');
        }
        RateLimiter::hit($kunci, 60);

        [$staff, $dosen] = $this->cari($id);
        if (!$staff && !$dosen) {
            return back()->withInput()->with('error', 'NIP/NIDN tidak ditemukan atau tidak aktif.')->with('tab', 'otp');
        }
        $chat = $staff->telegram ?? null ?: $dosen->telegram ?? null;
        if (!$chat) {
            return back()->withInput()->with('error', 'Akun Telegram Anda belum terdaftar di HRD. Hubungi bagian SDM.')->with('tab', 'otp');
        }

        // Kode lama yang belum dipakai dibatalkan; hanya kode terbaru yang berlaku
        Otp::where('staff_id', $staff?->id)->where('dosen_id', $dosen?->id)->whereNull('dipakai_at')->update(['dipakai_at' => now()]);
        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Otp::create([
            'staff_id' => $staff?->id, 'dosen_id' => $dosen?->id,
            'kode_hash' => Hash::make($kode), 'kedaluwarsa_at' => now()->addMinutes(self::BERLAKU_MENIT),
        ]);

        $nama = $staff->nama ?? $dosen->nama;
        if ($galat = $telegram->kirim($chat, "🔐 *Kode OTP Login HRD*\n\nHalo *{$nama}*,\n\nKode OTP Anda: *{$kode}*\n\n_Kode berlaku " . self::BERLAKU_MENIT . " menit. Jangan bagikan ke siapa pun._")) {
            return back()->withInput()->with('error', $galat)->with('tab', 'otp');
        }

        session(['otp_target' => ['staff_id' => $staff?->id, 'dosen_id' => $dosen?->id, 'nama' => $nama]]);
        return redirect()->route('login.otp')->with('ok', 'Kode OTP telah dikirim ke Telegram Anda.');
    }

    public function formKode()
    {
        if (!session('otp_target')) {
            return redirect()->route('login')->with('error', 'Sesi kedaluwarsa. Silakan masukkan NIP/NIDN lagi.')->with('tab', 'otp');
        }
        return view('login-otp', ['nama' => session('otp_target.nama'), 'menit' => self::BERLAKU_MENIT]);
    }

    public function verifikasi(Request $r)
    {
        $kode = $r->validate(['kode' => 'required|digits:6'], ['kode.digits' => 'Kode OTP terdiri dari 6 angka.'])['kode'];
        $target = session('otp_target');
        if (!$target) {
            return redirect()->route('login')->with('error', 'Sesi kedaluwarsa. Silakan masukkan NIP/NIDN lagi.')->with('tab', 'otp');
        }

        $otp = Otp::where('staff_id', $target['staff_id'])->where('dosen_id', $target['dosen_id'])
            ->whereNull('dipakai_at')->where('kedaluwarsa_at', '>', now())->latest('id')->first();
        if (!$otp) {
            return back()->with('error', 'Kode OTP sudah kedaluwarsa. Minta kode baru.');
        }
        if (!Hash::check($kode, $otp->kode_hash)) {
            $otp->increment('percobaan');
            if ($otp->percobaan >= self::MAKS_SALAH) {
                $otp->update(['dipakai_at' => now()]);   // hangus: cegah tebak-tebakan
                return back()->with('error', 'Kode salah ' . self::MAKS_SALAH . ' kali. Kode dibatalkan, silakan minta kode baru.');
            }
            return back()->with('error', 'Kode OTP salah. Sisa percobaan: ' . (self::MAKS_SALAH - $otp->percobaan) . '.');
        }
        $otp->update(['dipakai_at' => now()]);

        $akun = $this->akun(Staff::find($target['staff_id']), Dosen::find($target['dosen_id']));
        session()->forget('otp_target');
        auth('pegawai')->login($akun);
        $r->session()->regenerate();
        return redirect()->route('dashboard')->with('ok', "Selamat datang, {$akun->name}!");
    }

    // NIP → pegawai aktif (satu NIP bisa beberapa akun staff: ambil yang aktif dulu), NIDN → dosen aktif
    private function cari(string $id): array
    {
        $staff = Staff::where('nip', $id)->where('aktif', true)->orderBy('id')->first();
        $dosen = Dosen::where('nidn', $id)->where('aktif', true)->first()
            ?? ($staff ? Dosen::where('staff_id', $staff->id)->where('aktif', true)->first() : null);
        $staff ??= $dosen?->staff_id ? Staff::find($dosen->staff_id) : null;
        return [$staff, $dosen];
    }

    // Pakai akun yang sudah ada (username sama; peran tetap) atau buat akun baru tanpa id eUniversity
    private function akun(?Staff $staff, ?Dosen $dosen): Akun
    {
        $username = $staff->username ?? $dosen->username ?? 'nip:' . ($staff->nip ?? $dosen->nidn);
        $akun = Akun::firstOrNew(['username' => $username]);
        $akun->fill([
            'name' => $staff->nama ?? $dosen->nama,
            'roles' => $akun->roles ?? [],
            'scopes' => $akun->exists ? $akun->scopes : [],
            'peran' => $akun->peran ?? ($dosen ? 'dosen' : 'staff'),
            'nip' => $staff?->nip,
            'staff_id' => $staff?->id,
            'nidn' => $dosen?->nidn,
            'dosen_id' => $dosen?->id,
            'divisi' => $staff?->divisi,
            'level' => $staff?->level,
            'last_login_at' => now(),
        ])->save();
        return $akun;
    }
}
