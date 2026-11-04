# Kecocokan Dashboard Aplikasi vs Excel (cek 2026-11-04)

Status: ✅ ada & setara · 🟡 data ada, bentuk beda (tabel/batang horizontal, bukan grafik Excel) · ❌ belum ada.
Catatan teknis: aplikasi tidak memakai library chart — semua "grafik" adalah batang horizontal CSS (`.batang`).

## Dashboard Dosen (`resources/views/dashboard/dosen*.blade.php`)
| Grafik Excel | Jenis Excel | Di aplikasi | Status |
|---|---|---|---|
| Jafung Semua Prodi / per prodi | Batang | Kartu "Jabatan Fungsional" (batang horizontal), filter prodi | ✅ |
| Status Jafung Dosen (per prodi) | Pie | Pie "Status Jafung Dosen" (Tepat waktu / Tidak tepat) | ✅ |
| Sertifikasi Dosen semua / per prodi | Batang | Kartu "Sertifikasi Dosen" (Sudah/Eligible/Belum eligible) | ✅ |
| Status Sertifikasi Dosen | Pie | Pie "Status Sertifikasi Dosen" | ✅ |
| Pendidikan Dosen semua / per prodi | Pie | Kartu "Pendidikan Terakhir" batang S2/S3 | 🟡 |
| Dosen per Prodi & Kampus (SI/IF/BD) | Pie | Pie "Prodi & Kampus" (ditebak dari divisi/level data pegawai) | ✅ |
| Rasio Dosen & Mahasiswa (+Target, Kekurangan) | Batang berkelompok | Tabel "Rasio Dosen : Mahasiswa" (ada kolom Kurang) | 🟡 |
| Nilai Kompetensi Dosen (Personal vs Jabatan) | Batang berkelompok | Tabel per dosen + batang skor & garis target | 🟡 |
| Jumlah Dosen Punya Sertifikat Kompetensi | Pie | Tab Kompetensi: pie Kompeten / Belum (BNSP/LSP dari Rapor) | ✅ |
| Jumlah Dosen Menguasai MK Prodi (Actual) | Batang | Tab Penguasaan MK: tabel matriks + "Kekurangan Dosen per MK" | 🟡 |
| Rasio Dosen (sheet Kompetensi SI) | Batang | Sama dengan tabel rasio | 🟡 |
| Turn Over Dosen | Batang berkelompok | Dashboard Pegawai › Turn Over: tabel % per tahun | 🟡 |
| Keterlambatan Jan–Jun | Batang berkelompok per bulan | Tabel rata-rata per kelompok (warna panas) + Top 10 terlambat | 🟡 |
| Absensi Jan–Jun (6 grafik) | Batang berkelompok per bulan | Tabel rata-rata tidak hadir per kelompok | 🟡 |

## Dashboard HRD (`pegawai-sdm.blade.php`, tab DISC)
| Grafik Excel | Jenis Excel | Di aplikasi | Status |
|---|---|---|---|
| Profil Kompetensi & Komitmen per unit (Prodi SI/IF/BD, SarPras, Perpustakaan, Sales & Marketing) | Pie per unit | Matriks Komitmen × Kompetensi + batang gaya DISC, filter tim | 🟡 |

## Ringkasan
- Datanya hampir semua sudah ada. Selisih utama: **bentuk** (Excel pakai pie & batang vertikal berkelompok; aplikasi pakai tabel & batang horizontal).
- 4 pie yang belum ada sudah ditambahkan (partial `dashboard/_pie.blade.php`, CSS conic-gradient).
