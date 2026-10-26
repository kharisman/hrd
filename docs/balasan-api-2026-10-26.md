Perihal: Re: Login OTP Telegram terpusat & token aplikasi — hasil uji ulang HRD

Halo tim eUniversity,

Terima kasih atas fiturnya. Berikut hasil uji kami di beta1 pada 26 Oktober 2026.

## 1. API client "hrd" (permintaan Anda)

- **Nama aplikasi:** HRD ITB PalComTech
- **Scope yang dibutuhkan token aplikasi (`POST /auth/token`):** `staff.read`, `dosen.read`, `users.read`

Token aplikasi hanya kami pakai untuk sinkron harian (membaca `/staff`, `/dosen`, `/users?tipe_user=staff`).
Tambah, ubah, dan hapus data dosen/staf tetap memakai token akun admin yang login, sehingga mengikuti scope akun itu.

**Catatan keamanan:** API key yang sekarang kami pakai menghasilkan token aplikasi dengan scope `*`. Isinya
termasuk `admin.manage`, `students.write`, dan `mahasiswa.delete`. Uji `DELETE /dosen` kami bahkan berhasil memakai
token aplikasi ini. Mohon scope-nya dibatasi menjadi tiga scope di atas saja.

## 2. Login OTP Telegram

Sudah kami pasang di HRD. Login NIP/NIDN sekarang sepenuhnya memakai `/auth/otp/*`, dan chat ID serta OTP lokal di HRD
sudah kami hapus.

| Uji | Hasil |
|---|---|
| NIP tidak terdaftar | ✅ `404 not_found` |
| NIP dipakai 2 akun (`22.PCT.13`) | ✅ `409 ambigu` + `kandidat`, HRD menampilkan pilihan username |
| Verifikasi untuk identitas tidak terdaftar | ✅ `404 not_found` |
| Kirim kode sungguhan & verifikasi kode benar | ⏳ Belum. Kami uji dengan akun sendiri supaya tidak mengirim pesan ke pegawai lain |

Pertanyaan: apakah balasan `/auth/otp/verifikasi` bisa menyertakan `user.id` seperti `/auth/login`? Saat ini
HRD mengambilnya lewat `GET /me` setelah verifikasi.

## 3. Uji ulang temuan sebelumnya

| # | Temuan | Hasil |
|---|---|---|
| 1 | Hapus dosen masih bisa login | ✅ Beres. Setelah `DELETE /dosen/{id}`, login ditolak (422) dan token lama ditolak (`/me` → 401) |
| 2 | NIDN `-` dianggap duplikat | ✅ Beres. Dua dosen ber-NIDN `-` dan satu tanpa NIDN berhasil dibuat (201) |
| 6 | Status aktif dosen | ✅ Beres. `StatusLogin` tersedia, `?aktif=aktif` → 26 dari 242 dosen |
| — | Token aplikasi untuk sinkron | ✅ `hrd:sync` berhasil tanpa password (299 staf, 242 dosen) |

Data uji (`test_hrd_n1`, `test_hrd_n2`, `test_hrd_n3`) sudah kami hapus lagi lewat `DELETE /dosen/{id}`.
Akun lama `test_hrd_hapus` masih bisa dicoba login (balasan "Kredensial salah", bukan "akun tidak ditemukan"),
jadi sepertinya belum terhapus.

Terima kasih.
Tim HRD ITB PalComTech
