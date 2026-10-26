# Temuan Integrasi API eUniversity (beta1)

**Dari:** Tim pengembang aplikasi HRD ITB PalComTech
**Tanggal:** 23 Oktober 2026
**Server:** `https://beta1.palcomtech.org/api/v1`
**Akun yang dipakai:** `kharisma_elan` (role admin)

Aplikasi HRD memakai API eUniversity untuk login pegawai, dosen, dan staf, serta untuk menarik
dan mengubah master dosen/staf. Saat pengujian kami menemukan beberapa hal berikut.

---

## 1. `DELETE /dosen/{id}` tidak menghapus akun login — **prioritas tinggi**

**Langkah uji:**
1. `POST /dosen` membuat dosen `test_hrd_hapus` → sukses, `idDosen` = 243.
2. `POST /auth/login` dengan akun tersebut → sukses (role `dosen`).
3. `DELETE /dosen/243` → sukses, "Dosen dihapus". Dosen tidak lagi muncul di `GET /dosen`.
4. `POST /auth/login` dengan akun yang sama → **masih berhasil**.

**Dampak:** orang yang sudah dihapus dari master dosen (misalnya karena resign) masih bisa login ke
semua aplikasi yang memakai API ini, dengan role `dosen`. Kemungkinan `DELETE /staff/{id}` juga
begitu (belum kami uji).

**Usulan:** `DELETE /dosen/{id}` dan `DELETE /staff/{id}` ikut menonaktifkan atau menghapus login di
`data_username` / `api_users`, dan mencabut token aktifnya. Bisa juga disediakan endpoint terpisah
untuk menonaktifkan akun login.

**Tindak lanjut:** akun login **`test_hrd_hapus`** di beta1 adalah data uji dan **mohon dihapus**.
Kami tidak bisa menghapusnya sendiri karena tidak ada endpoint untuk itu.

> Sementara ini aplikasi HRD menolak login dosen/staf yang datanya tidak ada lagi di
> `GET /dosen` atau `GET /staff`.

## 2. NIDN `-` dianggap duplikat saat `POST /dosen`

**Langkah uji:** `POST /dosen` dengan `"nidn": "-"` → `409 NIDN sudah digunakan`.

**Dampak:** banyak data dosen lama (terutama dosen LB) memakai NIDN `-`. Dosen baru yang belum
punya NIDN tidak bisa ditambahkan, karena `nidn` wajib diisi dan `-` ditolak.

**Usulan (pilih salah satu):**
- `nidn` dibuat opsional (`null` diterima), atau
- nilai `-` / kosong dikecualikan dari cek unik.

Kemungkinan hal yang sama berlaku untuk `PATCH /dosen/{id}` pada dosen yang NIDN-nya `-`.

## 3. Request tanpa `User-Agent` mendapat 403

Request dari server ke server (Guzzle / cURL / Python) tanpa header `User-Agent` seperti browser
ditolak dengan `403 Forbidden` berupa halaman HTML, bukan JSON. Kemungkinan dari WAF atau firewall.

**Usulan:** cantumkan di dokumentasi, atau izinkan klien yang membawa `X-API-Key` yang valid.

## 4. Koneksi ke beta1 lambat dan sering timeout

Dari jaringan kampus, membuka koneksi TCP ke port 443 saja butuh 6–15 detik, dan cukup sering
gagal (`cURL error 28`). Sinkron ±540 data (6 halaman) bisa memakan beberapa menit dan perlu
retry berulang. Mohon dicek apakah ini masalah server beta1 atau jalur jaringannya.

## 5. Akun dosen dan staf berbeda username untuk orang yang sama

Contoh: satu orang punya akun staf `adelin` dan `alin_lppm` (NIP sama `13.PCT.07`, beda divisi),
sementara akun dosennya terpisah. Tidak ada kolom penghubung antara `/dosen` dan `/staff`, sehingga
HRD harus mencocokkan lewat nama.

**Usulan:** tambahkan kolom penghubung, misalnya `nip` di `/dosen` atau `idDosen` di `/staff`.

## 6. Tidak ada status aktif di `GET /dosen`

`GET /dosen` mengembalikan 242 dosen tanpa penanda aktif/tidak aktif, sehingga dosen lama dan
dosen aktif tidak bisa dibedakan.

**Usulan:** tambahkan kolom status aktif, atau filter `aktif` seperti di `/divisi` dan `/prodi`.

---

Terima kasih. Kami siap menguji ulang setelah ada perbaikan.

---

## Status tindak lanjut (balasan tim API)

| # | Temuan | Status | Tindakan HRD |
|---|---|---|---|
| 1 | Hapus dosen masih bisa login | ✅ **Lolos uji ulang 26/10** | Login & token dicabut setelah hapus. `test_hrd_hapus` belum terhapus |
| 2 | NIDN `-` duplikat | ✅ **Lolos uji ulang 26/10** | NIDN `-` & kosong diterima |
| 3 | 403 tanpa User-Agent | ➡️ Pengelola server | HRD tetap mengirim User-Agent |
| 4 | Koneksi lambat | ➡️ Pengelola server/jaringan | HRD tetap memakai retry |
| 5 | Penghubung dosen–staf | ✅ Ditangani di HRD, **tidak perlu perubahan API** | Tautan disimpan di HRD (`staff_id`): otomatis lewat nama, bisa dikoreksi manual di form dosen |
| 6 | Status aktif dosen | ✅ **Lolos uji ulang 26/10** | Sinkron HRD sudah membaca `StatusLogin` (aktif/tidak) |

Pengaman sementara di HRD (login ditolak bila tidak ada di master `/dosen` atau `/staff`) tetap dipertahankan.
