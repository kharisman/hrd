# Panduan Grafik Dashboard (referensi Excel)

Sumber: `dashboard-dosen.xlsx` (dari "Dashboard Dosen (2).xlsx") & `dashboard-hrd.xlsx` (dari "Dashboard HRD (3).xlsx").
Setiap grafik di-capture ke `grafik/`. Grafik dashboard aplikasi harus mengikuti jenis, judul, kategori & seri di bawah.
Batang = clustered column, Pie = pie chart.

## Dashboard Dosen

### Jafung & Serdos — semua prodi
| Grafik | Jenis | Seri | Capture |
|---|---|---|---|
| Jafung Semua Prodi | Batang | Jumlah | ![](grafik/dashboard-dosen_Jafung___Serdos_all_prodi_1.png) |
| Jafung SI | Batang | Jumlah | ![](grafik/dashboard-dosen_Jafung___Serdos_all_prodi_2.png) |
| Jafung BD | Batang | Jumlah | ![](grafik/dashboard-dosen_Jafung___Serdos_all_prodi_3.png) |
| Jafung IF | Batang | Jumlah | ![](grafik/dashboard-dosen_Jafung___Serdos_all_prodi_4.png) |
| Sertifikasi Dosen Semua Prodi | Batang | Jumlah | ![](grafik/dashboard-dosen_Jafung___Serdos_all_prodi_5.png) |
| Sertifikasi Dosen Prodi SI | Batang | Jumlah | ![](grafik/dashboard-dosen_Jafung___Serdos_all_prodi_6.png) |
| Sertifikasi Dosen Prodi BD | Batang | Jumlah | ![](grafik/dashboard-dosen_Jafung___Serdos_all_prodi_7.png) |
| Sertifikasi Dosen IF | Batang | Jumlah | ![](grafik/dashboard-dosen_Jafung___Serdos_all_prodi_8.png) |

### Jafung & Serdos — per prodi (SI / IF / BD, pola sama)
4 grafik per prodi: Jabatan Fungsional Dosen (Batang), Status Jafung Dosen (Pie), Sertifikasi Dosen (Batang), Status Sertifikasi Dosen (Pie).

| Prodi | 1 Jafung | 2 Status Jafung | 3 Serdos | 4 Status Serdos |
|---|---|---|---|---|
| SI | ![](grafik/dashboard-dosen_Jafung___Serdos_SI_1.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_SI_2.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_SI_3.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_SI_4.png) |
| IF | ![](grafik/dashboard-dosen_Jafung___Serdos_IF_1.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_IF_2.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_IF_3.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_IF_4.png) |
| BD | ![](grafik/dashboard-dosen_Jafung___Serdos_BD_1.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_BD_2.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_BD_3.png) | ![](grafik/dashboard-dosen_Jafung___Serdos_BD_4.png) |

### Pendidikan Dosen (Pie, seri Jumlah)
| Semua Prodi | SI | IF | BD |
|---|---|---|---|
| ![](grafik/dashboard-dosen_Pend_All_1.png) | ![](grafik/dashboard-dosen_Pend_SI_1.png) | ![](grafik/dashboard-dosen_Pend_IF_1.png) | ![](grafik/dashboard-dosen_Pend_BD_1.png) |

### Dosen per Prodi & Kampus (Pie)
| Sistem Informasi | Informatika | Bisnis Digital |
|---|---|---|
| ![](grafik/dashboard-dosen_Dosen_Prodi_dan_Kampus_1.png) | ![](grafik/dashboard-dosen_Dosen_Prodi_dan_Kampus_2.png) | ![](grafik/dashboard-dosen_Dosen_Prodi_dan_Kampus_3.png) |

### Rasio Dosen & Mahasiswa (Batang, kategori = prodi)
| Grafik | Seri | Capture |
|---|---|---|
| Rasio Dosen & Mahasiswa | Jumlah Dosen, Jumlah Mahasiswa, Rasio | ![](grafik/dashboard-dosen_Rasio_1.png) |
| Rasio Genap 2025/2026 | Jumlah Dosen, Jumlah Mahasiswa, Rasio | ![](grafik/dashboard-dosen_Rasio_2.png) |
| Rasio + target | Jumlah Dosen, Jumlah Mahasiswa, Rasio, Target, Kekurangan | ![](grafik/dashboard-dosen_Rasio_3.png) |

### Kompetensi Dosen per prodi & tahun (SI / IF / BD × 2025 / 2026)
Pola per sheet: (1) Nilai Kompetensi Dosen — Batang, seri Personal vs Jabatan; (2) Jumlah Dosen Yang Mempunyai Sertifikat Kompetensi — Pie; (3) Jumlah Dosen Yang Menguasai MK Prodi — Batang, seri Actual. SI juga punya (4) Rasio Dosen — Batang.

| Sheet | 1 Nilai | 2 Sertifikat | 3 Penguasaan MK | 4 Ekstra |
|---|---|---|---|---|
| SI 2025 | ![](grafik/dashboard-dosen_Kompetensi_SI_2025_1.png) | ![](grafik/dashboard-dosen_Kompetensi_SI_2025_2.png) | ![](grafik/dashboard-dosen_Kompetensi_SI_2025_3.png) | ![](grafik/dashboard-dosen_Kompetensi_SI_2025_4.png) |
| SI 2026 | ![](grafik/dashboard-dosen_Kompetensi_SI_2026_1.png) | ![](grafik/dashboard-dosen_Kompetensi_SI_2026_2.png) | ![](grafik/dashboard-dosen_Kompetensi_SI_2026_3.png) | ![](grafik/dashboard-dosen_Kompetensi_SI_2026_4.png) |
| IF 2025 | ![](grafik/dashboard-dosen_Kompetensi_IF_2025_1.png) | ![](grafik/dashboard-dosen_Kompetensi_IF_2025_2.png) | ![](grafik/dashboard-dosen_Kompetensi_IF_2025_3.png) | |
| IF 2026 | ![](grafik/dashboard-dosen_Kompetensi_IF_2026_1.png) | ![](grafik/dashboard-dosen_Kompetensi_IF_2026_2.png) | ![](grafik/dashboard-dosen_Kompetensi_IF_2026_3.png) | ![](grafik/dashboard-dosen_Kompetensi_IF_2026_4.png) ![](grafik/dashboard-dosen_Kompetensi_IF_2026_5.png) |
| BD 2025 | ![](grafik/dashboard-dosen_Kompetensi_BD_2025_1.png) | ![](grafik/dashboard-dosen_Kompetensi_BD_2025_2.png) | ![](grafik/dashboard-dosen_Kompetensi_BD_2025_3.png) | |
| BD 2026 | ![](grafik/dashboard-dosen_Kompetensi_BD_2026_1.png) | ![](grafik/dashboard-dosen_Kompetensi_BD_2026_2.png) | ![](grafik/dashboard-dosen_Kompetensi_BD_2026_3.png) | |

### Turn Over, Keterlambatan, Absensi (Batang)
| Grafik | Seri | Capture |
|---|---|---|
| Turn Over Dosen | 4 seri | ![](grafik/dashboard-dosen_Turn_over_1.png) |
| Keterlambatan | Rata-rata (Menit) Jan–Jun | ![](grafik/dashboard-dosen_Keterlambatan_1.png) |
| Absensi 1–6 | Absensi (Hari) Jan–Jun | ![](grafik/dashboard-dosen_Absensi_1.png) ![](grafik/dashboard-dosen_Absensi_2.png) ![](grafik/dashboard-dosen_Absensi_3.png) ![](grafik/dashboard-dosen_Absensi_4.png) ![](grafik/dashboard-dosen_Absensi_5.png) ![](grafik/dashboard-dosen_Absensi_6.png) |

Sheet tanpa grafik (data/tabel): Penguasaan MK *, Rencana Sertifikasi Dosen, SUMMARY, Note standar dosen/kinerja/mutu, Rapot (cth), KPI, sheet per dosen (rapor individu), Resume BD.

## Dashboard HRD

### Peta DISC — "Profil Kompetensi dan Komitmen" (Pie per unit)
| Prodi SI | Prodi IF | Prodi BD |
|---|---|---|
| ![](grafik/dashboard-hrd_DISC_Prodi_SI_1.png) | ![](grafik/dashboard-hrd_DISC_Prodi_IF_1.png) | ![](grafik/dashboard-hrd_DISC_Prodi_BD_1.png) |

| SarPras | Perpustakaan | Sales & Marketing |
|---|---|---|
| ![](grafik/dashboard-hrd_DISC_SarPras_1.png) | ![](grafik/dashboard-hrd_DISC_Perpustakaan_1.png) | ![](grafik/dashboard-hrd_DISC_Sales___Marketing_1.png) |

Sheet tanpa grafik: DISC Tim warek2 (tabel), Data Keterlambatan, Data Kehadiran, Data Karyawan All, Helper, db.
