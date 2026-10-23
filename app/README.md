# HRD ITB PalComTech

Aplikasi HRD (Laravel 9): data pegawai, jenjang dosen, cuti & izin.
Semua data master **terpusat di API eUniversity**; HRD tidak terhubung ke database aplikasi lain.

## Akun Admin HRD

Admin lokal (cadangan saat eUniversity tidak bisa diakses) dibuat dari terminal:

```
php artisan hrd:admin "Nama Admin" email@palcomtech.ac.id
```

Password ditanya saat perintah dijalankan. Setelah itu kelola lewat **Pengaturan → Admin Lokal**.
Kredensial jangan ditulis di repo; simpan di `KREDENSIAL.local.md` (diabaikan git).

## Instalasi

```
composer install
cp .env.example .env        # isi DB_* dan PCT_API_KEY
php artisan key:generate
php artisan migrate
php artisan hrd:admin "Admin HRD" admin@contoh.ac.id
```

## Menu

- **Laporan**: Rekap Cuti, Daftar Pegawai, Daftar Dosen, Riwayat Pendidikan. Semua dengan filter & **Unduh Excel**.
- **Pengaturan**: Jenis & Jatah Cuti, Admin Lokal, Sinkron eUniversity, Integrasi API (cek koneksi).
  Daftar menu keduanya ada di `app/Support/Menu.php`.

Admin lokal adalah cadangan saat API down; login di form dengan **email**.

## Login eUniversity (API)

Dosen, staf, dan admin login dengan **username eUniversity** lewat API Akademik
(`PCT_API_URL`, `PCT_API_KEY` di `.env`). Peran HRD diambil dari role API
(diatur di `config/services.php` → `pct.peran`):

| Role API | Peran HRD |
|---|---|
| `superadmin`, `admin` | Admin HRD |
| `kaprodi`, `dosen` | Dosen |
| `staff`, `baak` | Staf |
| lainnya (`mahasiswa`, `svc:*`) | ditolak |

Saat login, profil & **scope** API disimpan di `hrd_akuns` (tanpa password) dan ditautkan lewat **username**
ke `hrd_staffs` (`staff_id`) dan `hrd_dosens` (`dosen_id`). Dosen/staf yang sudah dihapus dari
master API ditolak masuk.

**Tidak ada akun layanan.** Token API milik user yang login disimpan di session server dan dipakai untuk
semua aksi ke eUniversity; token dicabut saat logout. Tombol hanya aktif bila akun punya scope-nya:

| Aksi | Scope |
|---|---|
| Sinkron | `staff.read` + `dosen.read` |
| Tambah / Ubah / Hapus dosen | `dosen.create` / `dosen.update` / `dosen.delete` |
| Tambah / Ubah / Hapus pegawai | `staff.create` / `staff.update` / `staff.delete` |

`*` = semua. Admin lokal tidak punya token, jadi semua aksi eUniversity terkunci baginya.
Server juga menolak (403) bila tombol diakali. Gate: `Gate::allows('api', 'dosen.create')`.

Catatan: server API wajib diberi header `User-Agent` (tanpa itu 403), dan koneksinya
kadang timeout, jadi klien melakukan retry.

## Data

| Tabel | Sumber | Isian khusus HRD (tidak ditimpa sinkron) |
|---|---|---|
| `hrd_staffs` = **pegawai** | API `/staff` | atasan, status kepegawaian, pendidikan |
| `hrd_dosens` | API `/dosen` | prodi, status dosen, jabfung, angka kredit, serdos, aktif |
| `hrd_cuti`, `hrd_jenis_cuti` | HRD | — |
| `hrd_akuns`, `hrd_users` | login API / admin lokal | — |

- **Atasan** tidak ada di API, jadi diisi HRD di form pegawai. Atasan inilah yang menyetujui cuti bawahannya.
- Tambah/ubah/hapus pegawai & dosen dikirim ke API dulu, baru disimpan di HRD.

Semua tabel memakai prefix **`hrd_`**, diatur lewat `prefix` koneksi (`DB_PREFIX`, default `hrd_`).
Di kode cukup tulis nama tanpa prefix.

## Sinkron data dari eUniversity

```
php artisan hrd:sync
```

Atau tombol **Sinkron eUniversity** (Data Pegawai / Data Dosen / Pengaturan). Memakai akun eUniversity yang login;
dari terminal, perintah di atas menanyakan username & password eUniversity Anda.

- **Pegawai** (`/staff`) → `hrd_staffs`, kunci `api_id`. Pegawai yang dihapus di eUniversity ikut terhapus.
- **Dosen** (`/dosen`) → `hrd_dosens`, kunci `api_id`.
- Dosen ditautkan ke pegawai lewat nama (akun dosen & staff di eUniversity beda username).
  Nama kembar (beda orang) tidak ditautkan otomatis; tautkan manual di form dosen.

Sinkron bisa beberapa menit bila jaringan ke server eUniversity lambat. Karena tidak ada akun layanan,
sinkron **tidak bisa dijadwalkan otomatis**: jalankan manual oleh admin yang punya izin.

## Menjalankan

```
php artisan migrate
php artisan serve --port=8001
```

Setelah mengubah `.env`, jalankan `php artisan config:clear`.
