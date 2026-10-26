# Log Progres HRD ITB PalComTech

## 2026-10-23 — Login NIP/NIDN + OTP Telegram (commit `6104a2b`)

### Selesai
- **Login NIP/NIDN + OTP Telegram** (tab kedua di halaman login)
  - NIP → staf aktif, NIDN → dosen aktif; akun lokal dibuat otomatis bila belum ada.
  - OTP 6 angka (hash bcrypt), berlaku 5 menit, maks. 5 kali salah, kirim maks. 3x/menit.
  - File: `OtpController`, `Models/Otp`, `login-otp.blade.php`, migrasi `..._000012_create_otp_telegram`.
- **Bot Telegram** (menyalin bot BAAK @BaakITBPCT_bot)
  - `Services/Telegram`: token terenkripsi di `hrd_pengaturan`, cadangan `KEY_BOT` di `.env`.
  - `TELEGRAM_PURA=true` = mode uji: pesan hanya ditulis ke log.
  - Pengaturan → Bot Telegram: status bot, ganti token, kirim pesan uji, daftar staf tanpa Telegram.
- **Chat ID oleh admin**: kartu Telegram di detail pegawai & dosen.
- **Impor chat ID** dari spmi: `php artisan hrd:impor-telegram` (54 dari 57 staf aktif terisi).
- **Hubungkan Telegram mandiri** (untuk pengguna yang login pertama kali)
  - Halaman *Telegram Saya* (`/telegram`): masukkan chat ID → kode verifikasi dikirim ke chat itu → masukkan kode → tersimpan. Bisa diputuskan lagi.
  - Kode berlaku 10 menit, maks. 5 kali salah, kirim maks. 3x/menit.
  - Tombol "Telegram Saya" di sidebar; pengingat di dashboard pegawai bila belum terhubung.
  - File: `TelegramSayaController`, `telegram-saya.blade.php`.

### Sudah diuji (mode uji)
- Login OTP: NIP palsu ditolak, staf tanpa Telegram ditolak, kode salah/kedaluwarsa/dipakai ulang ditolak, kode benar berhasil login.

### Belum / sisa
- [ ] Uji alur *Telegram Saya* di browser (mode uji `TELEGRAM_PURA=true`), lalu matikan lagi.
- [ ] Uji kirim Telegram sungguhan ke akun sendiri (bukan staf lain).
- [ ] Pengingat dashboard belum muncul untuk dosen yang tidak punya data staf.
- [ ] 3 staf aktif belum punya chat ID (lihat Pengaturan → Bot Telegram).

## Sebelumnya (ringkas)
- Data dari API eUniversity (staf, dosen, users) + sinkron `php artisan hrd:sync`; semua tabel berprefix `hrd_`.
- Modul: Dashboard, Data Pegawai, Atasan & Bawahan, Data Dosen, Studi Lanjut (impor Excel), Cuti, Pengajuan (16 formulir), Laporan, Pengaturan.
- PDF pengajuan dengan tata letak surat BAAK + TTD Rektor berupa QR → halaman validasi publik.
- AJAX untuk semua form & navigasi (retry, progres, skeleton, toast).
- Staf tanpa role eUniversity tetap bisa login bila tercatat aktif.
