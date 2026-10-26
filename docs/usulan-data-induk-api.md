# Usulan Pemusatan Data Induk SDM di API eUniversity

**Dari:** Tim pengembang aplikasi HRD ITB PalComTech
**Tanggal:** 27 Oktober 2026
**Server:** `https://beta1.palcomtech.org/api/v1`

## Latar belakang

HRD saat ini menyimpan sendiri beberapa data kepegawaian yang **tidak ada di eUniversity**. Datanya diimpor dari spreadsheet
"Dashboard HRD" dan "Dashboard Dosen", lalu dirawat lewat form HRD. Sebagian data ini juga dibutuhkan unit lain,
misalnya BAAK (beban & penugasan dosen), LPPM (publikasi, HKI), PDDikti, dan borang akreditasi. Akibatnya data yang
sama diisi berkali-kali di tempat berbeda dan mudah tidak sinkron.

Kami mengusulkan agar **data induk** berikut disimpan di eUniversity. HRD akan membacanya lewat API, sama seperti
biodata `/staff` dan `/dosen` sekarang.

Data **proses & penilaian SDM** tetap di HRD karena bersifat internal dan sensitif (lihat bagian akhir), yaitu pengajuan,
cuti, peta karakter DISC, penilaian kompetensi, dan kehadiran.

Semua usulan bersifat **menambah kolom/endpoint**. Tidak ada perubahan pada endpoint yang sudah dipakai aplikasi lain.

---

## A. Kolom tambahan pada data dosen (`/dosen`) — prioritas tinggi

| Field (usulan) | Tipe | Isi | Catatan |
|---|---|---|---|
| `idPStudiHomebase` | int, nullable | prodi homebase (FK `data_pstudi`) | Saat ini **tidak ada** di `/dosen`; HRD terpaksa memakai spreadsheet |
| `JabatanFungsional` | enum: `Tenaga Pengajar`, `Asisten Ahli`, `Lektor`, `Lektor Kepala`, `Profesor` | jafung saat ini | |
| `AngkaKredit` | smallint, nullable | mis. 150, 200, 300, 400 | |
| `TmtJabfung` | date, nullable | TMT jafung saat ini | |
| `TmtDosen` | date, nullable | TMT sebagai dosen (SK) | dasar perhitungan masa kerja dosen |
| `Serdos` | enum `ya`/`tidak` | sudah sertifikasi dosen | |
| `TglSerdos` | date, nullable | tanggal lulus serdos | |
| `StatusDosen` | enum: `Tetap`, `Tidak Tetap`, `Luar Biasa` | | |

- **Baca:** ikut di `GET /dosen` dan `GET /dosen/{id}`, plus filter `?prodi=` dan `?jafung=`.
- **Ubah:** ikut di `PATCH /dosen/{id}`, dengan scope yang sudah ada (`dosen.update`).
- **Data awal:** HRD bisa menyerahkan berkas impor untuk 24 dosen aktif (hasil rekap HRD).

## B. Kolom tambahan pada data staf (`/staff`) — prioritas tinggi

| Field (usulan) | Tipe | Isi |
|---|---|---|
| `idAtasan` | int, nullable | atasan langsung (FK `idStaff`), dipakai alur persetujuan |
| `StatusKepegawaian` | enum: `Tetap`, `Kontrak`, `Honorer` | |
| `PendidikanTerakhir` | enum: `SMA`, `D3`, `S1`, `S2`, `S3` | |
| `TglKeluar` | date, nullable | tanggal berhenti, untuk turn over; saat ini akun nonaktif tidak punya tanggal keluar |
| `KontrakBerakhir` | date, nullable | akhir kontrak / SK |

- **Baca:** ikut di `GET /staff` dan `GET /staff/{id}`, plus filter `?atasan=`.
- **Ubah:** ikut di `PATCH /staff/{id}` (`staff.update`).
- Catatan: satu orang bisa punya beberapa akun staf (NIP sama, beda divisi). Mohon kolom di atas **per orang (NIP)**, atau beri
  penanda akun utama, supaya tidak perlu diisi di setiap akun.

## C. Tabel baru: riwayat pendidikan pegawai & dosen — prioritas sedang

`/riwayat-pendidikan` (CRUD), satu baris per jenjang:

| Field | Tipe |
|---|---|
| `idRiwayat` | int, PK |
| `idStaff` / `idDosen` | int, nullable (salah satu wajib) |
| `Jenjang` | enum: `SMA`, `D3`, `S1`, `Profesi`, `S2`, `S3` |
| `Institusi`, `ProgramStudi` | varchar |
| `TahunMasuk`, `TahunLulus` | year, nullable |
| `Status` | enum: `Lulus`, `Sedang Studi Lanjut`, `Tidak Selesai` |
| `Pembiayaan` | enum: `Mandiri`, `Beasiswa Institusi`, `Beasiswa Luar`, nullable |
| `Berkas` | file (ijazah), nullable |

- **Endpoint:** `GET /riwayat-pendidikan?staff=&dosen=&status=`, `POST`, `PATCH /{id}`, `DELETE /{id}`.
- **Scope:** `riwayat-pendidikan.read` / `.create` / `.update` / `.delete`.
- **Dipakai:** studi lanjut (HRD), kualifikasi dosen (akreditasi), PDDikti.

## D. Tabel baru: karya & sertifikat dosen — prioritas sedang

`/dosen/{id}/karya` (CRUD), satu tabel dengan kolom `jenis`:

| Field | Tipe | Isi |
|---|---|---|
| `idKarya` | int, PK | |
| `idDosen` | int, FK | |
| `Jenis` | enum: `publikasi`, `hki`, `buku`, `hibah`, `penghargaan`, `sertifikat`, `rekognisi` | |
| `Judul` | varchar(500) | |
| `Tahun` | varchar(40) | tanggal atau tahun apa adanya |
| `Penyelenggara` | varchar, nullable | penerbit / penyelenggara / pemberi |
| `Peringkat` | varchar, nullable | mis. Sinta 2, Scopus Q3; atau jenis HKI |
| `Kompetensi` | enum `ya`/`tidak` | sertifikat kompetensi BNSP/LSP |
| `Tautan` | varchar(500), nullable | link bukti |

- **Scope:** `karya-dosen.read` / `.create` / `.update` / `.delete`.
- **Dipakai:** rapor dosen (HRD), LPPM, borang akreditasi, kenaikan jafung.
- **Data awal:** HRD memiliki 686 baris hasil impor dari spreadsheet (sertifikat 199, prestasi/HKI/buku 123, publikasi 121,
  rekognisi 83) dan bisa diserahkan sebagai berkas impor.

## E. (Opsional) Rekap absensi dari mesin — prioritas rendah

Bila data mesin absensi sudah masuk eUniversity, mohon disediakan `GET /absensi/rekap?bulan=&tahun=` berisi menit
terlambat dan hari tidak hadir per pegawai. HRD saat ini mengisinya manual tiap bulan.

---

## Ringkasan scope untuk API client "hrd"

| Kebutuhan | Scope |
|---|---|
| Baca & ubah kolom tambahan dosen/staf | `dosen.read`, `dosen.update`, `staff.read`, `staff.update` (sudah ada) |
| Riwayat pendidikan | `riwayat-pendidikan.read`, `.create`, `.update`, `.delete` |
| Karya & sertifikat dosen | `karya-dosen.read`, `.create`, `.update`, `.delete` |
| Rekap absensi (opsional) | `absensi.read` |

## Tetap di HRD (tidak diusulkan ke eUniversity)

Data berikut adalah proses & penilaian internal SDM, bersifat sensitif, dan sering berubah sesuai kebijakan HRD. Bila suatu
saat aplikasi lain membutuhkannya, HRD akan menyediakan API sendiri.

- Pengajuan, cuti & jatah cuti, alur persetujuan, peran pejabat (Warek, Keuangan, BAAK di alur HRD)
- Dokumen PDF pengajuan, nomor surat, QR tanda tangan
- Peta karakter DISC
- Penilaian kompetensi dosen & roadmap karier
- Matriks penguasaan mata kuliah inti (penilaian HRD/prodi; HRD sudah memakai data "pernah mengampu" dari `/krs/{id}/grup`)
- Rekap kehadiran (sampai tersedia butir E)

## Rencana di sisi HRD setelah endpoint tersedia

1. HRD menyerahkan berkas impor data awal (butir A–D) dari data yang sekarang tersimpan di HRD.
2. `hrd:sync` membaca kolom/tabel baru dari API. Form HRD menyimpan perubahan ke API (`PATCH`/`POST`), seperti biodata sekarang.
3. Kolom salinan di HRD tetap ada sebagai cermin yang hanya diisi oleh sinkron (pola yang sama dengan biodata pegawai).

Terima kasih. Kami siap berdiskusi soal penamaan field dan format impor.
Tim HRD ITB PalComTech
