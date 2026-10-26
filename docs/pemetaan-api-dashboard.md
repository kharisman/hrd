# Pemetaan Data Dashboard HRD ke API eUniversity

**Tanggal cek:** 27 Oktober 2026 · **Sumber:** `https://beta1.palcomtech.org/docs/api.json` (133 endpoint)

## Hasil uji akses dengan API key HRD

Pembatasan berlaku di tingkat **aplikasi (API key)**: token admin yang login lewat HRD pun ditolak
dengan pesan *"Aplikasi tidak punya scope ..."*.

| Endpoint | Status | Scope yang dibutuhkan |
|---|---|---|
| `GET /dosen-pa` | ✅ 200 (26 dosen + jumlah mahasiswa bimbingan) | sudah ada |
| `GET /prodi`, `/institusi` | ❌ 403 | `prodi.read`, `institusi.read` |
| `GET /angkatan` | ❌ 403 | `angkatan.read` |
| `GET /mahasiswa`, `/mahasiswa/stats` | ❌ 403 | `mahasiswa.read` (scope `students.read` yang dimiliki HRD **tidak** dipakai endpoint ini) |
| `GET /kurikulum`, `/kurikulum/{id}/detail` | ❌ 403 | `kurikulum.read` |
| `GET /matakuliah` | ❌ 403 | `matakuliah.read` |
| `GET /krs`, `/krs/{id}/grup` | ❌ 403 | `krs.read` |
| `GET /jadwal-dosen/{idDosen}/{idKRS}` | ❌ 403 | `jadwal-dosen.read` |
| `GET /struktur` | ❌ 403 | `admin.manage` (terlalu luas, tidak diminta) |

## Data dashboard yang seharusnya mengacu ke API

| Data di HRD sekarang | Sumber sekarang | Endpoint API pengganti | Manfaat |
|---|---|---|---|
| Daftar prodi (`Dosen::PRODI`, tertulis di kode: SI, IF, BD) | kode | `GET /prodi` (+ jenjang, institusi) | Prodi baru/D3 otomatis ikut, nama prodi sama dengan eUniversity |
| Jumlah mahasiswa per prodi per TA (tabel `rasio_prodi`, diisi admin / impor Excel) | input manual | `GET /mahasiswa/stats` (`by_prodi`, `by_status`, `by_angkatan`) | Rasio dosen : mahasiswa otomatis & selalu terbaru |
| Angkatan | tidak ada | `GET /angkatan?prodi=` (angkatan + kurikulum yang berlaku) | Rasio & sebaran per angkatan; tahu kurikulum mana yang aktif |
| Kurikulum MK inti (`mk_inti.kurikulum`: "Lama", "2026") | impor Excel | `GET /kurikulum?prodi=` + `/kurikulum/{id}/detail` (semester, kode, nama, SKS) | Daftar MK & semester resmi, kode MK, tidak perlu input ulang |
| Nama mata kuliah inti (`mk_inti.nama`, `semester`) | impor Excel | `GET /matakuliah`, `/kurikulum/{id}/detail` | MK ditautkan lewat **kode MK**, bukan teks nama |
| Penguasaan MK (✓ dosen menguasai MK) | impor Excel / centang admin | tetap di HRD — **ditambah** bukti dari `GET /krs/{id}/grup?dosen=` (pernah mengampu) | Kolom "pernah mengampu" otomatis di samping penilaian "menguasai" |
| MK yang diampu dosen (rapor) | belum ada | `GET /jadwal-dosen/{idDosen}/{idKRS}` | Rapor menampilkan beban mengajar per periode |
| Mahasiswa bimbingan akademik per dosen | belum ada | `GET /dosen-pa` ✅ (bisa sekarang) | Beban PA di rapor & dashboard dosen |
| Dosen pembimbing/penguji PKL & skripsi | belum ada | `GET /pkl-skripsi/{jenis}/{idKRS}`, `/pkl-skripsi/penguji` (scope belum dicek) | Kinerja bimbingan di rapor (indikator standar kinerja dosen) |

**Tetap dikelola di HRD** (tidak ada padanannya di API): jafung & TMT, serdos, kompetensi dosen,
peta karakter DISC, roadmap karier, sertifikat/prestasi/publikasi/rekognisi, kehadiran & keterlambatan,
turn over, alur & pejabat pengajuan.

## Scope yang perlu diminta untuk API client "hrd" (semuanya baca saja)

`prodi.read`, `angkatan.read`, `mahasiswa.read`, `kurikulum.read`, `matakuliah.read`, `krs.read`, `jadwal-dosen.read`

Catatan: `mahasiswa.read` cukup untuk `/mahasiswa/stats` (agregat). HRD tidak membutuhkan data pribadi mahasiswa,
jadi bila memungkinkan cukup diberi akses ke `/mahasiswa/stats` saja.

## Status (27 Oktober 2026): scope diberikan & sudah dipakai

`hrd:sync` sekarang juga menjalankan `SinkronAkademik`:

| Data | Hasil sinkron pertama | Catatan |
|---|---|---|
| Prodi (`/prodi`) | 6 prodi, 3 S1 aktif dipakai dashboard | daftar prodi tidak lagi ditulis di kode (`Dosen::prodi()`) |
| Mahasiswa aktif (`/mahasiswa?status=aktif`) | 0 di beta1 | beta1 belum punya mahasiswa aktif, jadi angka rasio manual tidak ditimpa; di produksi otomatis terisi (ikon awan di tabel rasio) |
| Bimbingan PA (`/dosen-pa`) | 26 dosen | tampil di rapor |
| Kelas diampu (`/krs/{id}/grup`, 6 periode reguler terakhir) | 523 kelas | tanda "pernah mengampu" di matriks MK + daftar MK diampu di rapor |
| MK inti → kurikulum API (`/kurikulum/{id}/detail`) | 163 dari 220 MK tertaut (kode, SKS, semester) | sisanya beda nama (mis. "UI/UX" vs "Desain Antarmuka dan Pengalaman Pengguna"), ditautkan manual di mode Ubah Matriks |

### Tambahan (angkatan, periode, struktur, bimbingan)

| Data | Endpoint | Dipakai di |
|---|---|---|
| Periode kuliah (41) | `/krs` | tahun ajaran rasio (pilihan isian), riwayat mengajar, bimbingan |
| Angkatan (72) + kurikulumnya | `/angkatan` | kurikulum berlaku di matriks MK ("dipakai N angkatan aktif"), label rasio per angkatan |
| Mahasiswa aktif per angkatan | `/mahasiswa?status=aktif&fields=idMhs,idPStudi,Angkatan` | rincian rasio per angkatan (tanpa data pribadi) |
| Struktur divisi × level (47) | `/struktur` | pilihan & validasi divisi/level di form pegawai; kolom `staffs.jabatan` (Excel) dihapus karena sama dengan level API |
| Pembimbing PKL / proposal / skripsi (1.172, 100% tercocok ke dosen) | `/pkl-skripsi/{jenis}/{idKRS}` | rapor dosen (judul & prodi, tanpa nama mahasiswa) |
| Dosen penguji (26) | `/pkl-skripsi/penguji` | rapor dosen |
| Rincian bimbingan PA | `/dosen-pa/{id}` (langsung saat rapor dibuka) | rapor dosen: jumlah per status & angkatan |
| MK belum tertaut | `/kurikulum/{id}/detail` | mode Ubah Matriks menyarankan MK paling mirip (dipilih admin) |

## Relasi database ke data API (audit 27 Oktober 2026)

Semua rujukan sekarang lewat kunci + foreign key, tidak lagi lewat teks:

| Tabel | Dulu | Sekarang |
|---|---|---|
| `disc`, `kehadiran` | `nip` (teks) | `staff_id` → `staffs` (punya `api_id`); satu orang = akun staff utama (`Staff::utama`: aktif dulu, id terkecil) |
| `dosens`, `mk_inti`, `rasio_prodi`, `bimbingan` | `program_studi` (nama) | + `prodi_id` → `prodi` (`api_id`), diisi otomatis dari nama saat disimpan ("D3 Akuntansi" → Akuntansi D3) |
| `mk_inti`, `angkatan` | `id_kurikulum_api` tanpa tabel | → master `kurikulum` (dari `/kurikulum`) |
| `mengajar` | `dosen_api_id`, `id_krs` tanpa FK | → `dosens.api_id`, `periode_krs.api_id` |
| `bimbingan` | `id_krs` tanpa FK | → `periode_krs.api_id` |
| `angkatan`, `kurikulum` | `prodi_api_id` tanpa FK | → `prodi.api_id` |
| `staffs.atasan_id`, `dosens.staff_id`, `akuns`, `riwayat_pendidikan`, `pengajuan` | tanpa FK | FK ke `staffs`/`dosens`; pegawai yang punya pengajuan tidak dihapus sinkron, hanya dinonaktifkan |

Audit baris yatim: 0 di semua tabel. Sisa tanpa kunci API: 57 MK inti tanpa `kode_mk` (nama beda, tautkan di Ubah Matriks).

## Rencana awal (sudah dikerjakan)

1. `Sinkron` menarik `/prodi` → tabel prodi; daftar prodi di HRD tidak lagi ditulis di kode.
2. Rasio dosen : mahasiswa memakai `/mahasiswa/stats` (status aktif) per prodi; input manual dihapus.
3. MK inti ditautkan ke kurikulum & mata kuliah API (kode MK); matriks penguasaan tetap di HRD.
4. Rapor dosen: MK yang diampu per periode (`/jadwal-dosen`) dan jumlah mahasiswa bimbingan PA (`/dosen-pa`).
