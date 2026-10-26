# Usulan Fitur API eUniversity untuk Login OTP & Integrasi Antar-Aplikasi

**Dari:** Tim pengembang aplikasi HRD ITB PalComTech
**Tanggal:** 26 Oktober 2026
**Server:** `https://beta1.palcomtech.org/api/v1`

## Latar belakang

HRD sekarang punya dua cara login:

1. **Akun eUniversity** (`POST /auth/login`). Login ini memberi token Bearer berisi scope.
2. **NIP/NIDN + OTP Telegram.** OTP diperiksa sendiri oleh HRD dari data lokal hasil sinkron, jadi tidak ada token eUniversity.

Akibatnya:

- **Fitur yang memanggil API tidak bisa dipakai pengguna OTP.** Contohnya simpan data pegawai, sinkron, dan cek akses.
- **Sinkron butuh username dan password manusia.** `hrd:sync` meminta kredensial akun admin, sehingga tidak bisa dijadwalkan otomatis.
- **Data Telegram dikelola terpisah.** BAAK dan HRD masing-masing menyimpan chat ID sendiri, sehingga pegawai harus mendaftar dua kali.

Usulan di bawah diurutkan dari yang paling berdampak.

---

## 1. Token aplikasi (service account / client credentials) — **prioritas tinggi**

**Usulan:** endpoint untuk aplikasi, bukan untuk manusia:

```
POST /auth/token
{ "client_id": "hrd", "client_secret": "…", "scope": ["staff.read", "dosen.read", "users.read"] }
→ { "access_token": "…", "expires_in": 3600, "scope": [...] }
```

- Scope dibatasi per aplikasi, misalnya HRD hanya `staff.*`, `dosen.*`, `users.read`.
- Secret bisa diputar (rotate) dan dicabut oleh admin eUniversity.

**Manfaat:** sinkron bisa berjalan terjadwal (cron) tanpa password orang, dan tidak terganggu saat admin ganti password atau keluar.

## 2. Login tanpa password: tukar identitas terverifikasi → token pengguna — **prioritas tinggi**

Supaya pengguna OTP tetap mendapat token dengan scope miliknya sendiri, ada dua pilihan.

**Pilihan A — OTP disediakan oleh eUniversity** (disarankan, dipakai bersama semua aplikasi):

```
POST /auth/otp/kirim      { "identitas": "<NIP/NIDN/username>" }   → kode dikirim ke Telegram terdaftar
POST /auth/otp/verifikasi { "identitas": "…", "kode": "123456" }    → { "token": "…", "user": {...} }
```

- Batas percobaan, masa berlaku kode, dan rate limit ditangani di server.
- Ini menjadi satu pintu yang bisa dipakai HRD, BAAK, dan aplikasi lain.

**Pilihan B — token exchange oleh aplikasi tepercaya:**

```
POST /auth/token/pengguna   (pakai token aplikasi dari usulan 1)
{ "username": "…" }  → token pengguna, scope = irisan(scope aplikasi, scope role pengguna)
```

- Hanya untuk client yang diizinkan, dan semua pemakaiannya dicatat di log audit.

## 3. Chat ID Telegram sebagai data akun

**Usulan:**

- Field `telegram_chat_id` di akun pengguna (`data_username` / `api_users`).
- `GET /users/{id}` mengembalikan status *terhubung ya/tidak*. Chat ID mentahnya cukup untuk scope khusus.
- Endpoint hubungkan dan putuskan, dengan verifikasi kode seperti halaman *Telegram Saya* di HRD:

```
POST   /me/telegram            { "chat_id": "…" }  → kirim kode verifikasi
POST   /me/telegram/verifikasi { "kode": "…" }
DELETE /me/telegram
```

**Manfaat:** pegawai cukup menghubungkan Telegram sekali untuk semua aplikasi.

## 4. Lain-lain (pelengkap)

- **Perubahan data sejak waktu tertentu**, misalnya `GET /staff?updated_since=…` dan `GET /dosen?updated_since=…`.
  - Sinkron cukup menarik data yang berubah, tidak perlu menarik semuanya.
  - Alternatifnya webhook saat staf atau dosen ditambah, diubah, atau dinonaktifkan.
- **Status aktif konsisten.** Saat staf atau dosen dinonaktifkan, login dan token aktifnya ikut dicabut (lihat `temuan-api-eUniversity.md` poin 1).
- **`GET /auth/me`** yang mengembalikan role dan scope token saat ini, supaya aplikasi tidak perlu menebak hak akses.

---

## Ringkasan prioritas

| No | Fitur | Untuk | Prioritas |
|----|-------|-------|-----------|
| 1 | Token aplikasi (client credentials) | Sinkron otomatis tanpa password orang | Tinggi |
| 2 | OTP di eUniversity / token exchange | Pengguna OTP tetap punya token & scope | Tinggi |
| 3 | Chat ID Telegram di akun | Satu kali daftar Telegram untuk semua aplikasi | Sedang |
| 4 | `updated_since`/webhook, `/auth/me` | Sinkron ringan, cek hak akses | Rendah |

Sementara fitur ini belum ada, HRD tetap berjalan seperti sekarang:

- Pegawai login lewat OTP dengan data lokal.
- Fitur yang butuh API hanya untuk admin yang login lewat eUniversity.
- Sinkron dijalankan manual oleh admin.
