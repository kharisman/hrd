Perihal: Usulan endpoint kirim notifikasi Telegram terpusat
Kepada: Tim Pengembang API eUniversity
Dari: Tim HRD ITB PalComTech
Tanggal: 5 November 2026

## Latar belakang

Aplikasi HRD sekarang mengirim notifikasi pengajuan (cuti, izin, dan lainnya) ke Telegram:

- pemutus tahap berikutnya (atasan, HRD, Rektor/Warek) diberi tahu ada pengajuan yang menunggu;
- pengaju diberi tahu hasilnya (disetujui/ditolak, nomor surat, catatan).

Chat ID penerima sudah kami ambil dari `GET /telegram`. Masalahnya, **pengirimannya masih butuh token bot di
setiap aplikasi**. Akibatnya:

1. Token bot OTP harus disalin ke HRD, BAAK, dan aplikasi lain. Kalau token bocor dari satu aplikasi, semua ikut kena.
2. Kalau tiap aplikasi memakai bot sendiri, pegawai harus menekan *Start* di banyak bot. Bot baru tidak bisa
   mengirim pesan ke orang yang belum pernah menekan *Start*.
3. Batas kirim Telegram (±30 pesan/detik per bot) tidak terkoordinasi antaraplikasi.
4. Tidak ada log terpusat tentang pesan apa yang dikirim ke siapa.

## Usulan endpoint

### 1. Kirim notifikasi

```
POST /notifikasi
Authorization: Bearer <token aplikasi>        scope: notifikasi.kirim
X-API-Key: …
```

```json
{
  "penerima": ["eko_dosentetap", "22.PCT.13"],
  "pesan": "📝 Cuti Tahunan dari <b>Ridho</b> menunggu persetujuan Anda.",
  "format": "html",
  "tautan": { "teks": "Proses sekarang", "url": "https://hrd.palcomtech.ac.id/pengajuan/123" },
  "kunci": "hrd-pengajuan-123-tahap-2"
}
```

| Field | Keterangan |
|---|---|
| `penerima` | Username atau NIP/NIDN, maksimal 50 per permintaan. Server yang mencari chat ID-nya. |
| `pesan` | Teks maksimal 4.000 karakter. `format`: `html` (hanya tag `<b> <i> <a> <code>`) atau `teks`. |
| `tautan` | Opsional, tampil sebagai tombol *inline* di bawah pesan. |
| `kunci` | Opsional, untuk mencegah kiriman ganda. `kunci` yang sama dalam 24 jam tidak dikirim ulang. |

Balasan `202 Accepted`. Kirim ke Telegram sebaiknya diantrekan di server, supaya aplikasi tidak menunggu.

```json
{
  "success": true,
  "data": {
    "id": "ntf_01J…",
    "terkirim": ["eko_dosentetap"],
    "dilewati": [{ "penerima": "22.PCT.13", "alasan": "telegram_belum_terhubung" }]
  }
}
```

Alasan `dilewati` mengikuti kode error OTP yang sudah ada: `not_found`, `ambigu`, `telegram_belum_terhubung`,
`nonaktif`.

### 2. Status kiriman (opsional)

```
GET /notifikasi/{id}   → status per penerima: antre | terkirim | gagal (+ pesan error Telegram)
```

Endpoint ini kami pakai untuk menampilkan tanda "terkirim" di riwayat pengajuan dan untuk melacak keluhan
"tidak dapat notifikasi".

### 3. Cek keterhubungan

`GET /telegram` sekarang mengembalikan chat ID mentah ke aplikasi. Kalau endpoint kirim sudah ada, aplikasi cukup tahu
apakah penerima terhubung atau tidak. Kami usulkan:

```
GET /telegram?username=a,b,c   → [{ "username": "a", "terhubung": true }, …]
```

Chat ID mentah cukup diberikan untuk scope admin.

## Keamanan & batasan

- **Scope baru `notifikasi.kirim`**, hanya untuk token aplikasi yang diizinkan (HRD, BAAK, dst.). Token pengguna biasa
  tidak boleh memakai scope ini.
- **Penerima dibatasi pada pengguna aktif** (`StatusLogin = aktif`).
- **Rate limit per aplikasi**, misalnya 60 permintaan per menit dan 500 penerima per jam, dengan balasan `429` dan
  header `Retry-After`.
- **Nama pengirim di pesan:** server menambahkan baris kecil `— dari HRD` sesuai aplikasi pemanggil, supaya
  pegawai tahu sumbernya dan tidak mudah tertipu pesan palsu.
- **Log audit:** aplikasi, penerima, waktu, dan status. Isi pesan cukup disimpan 30 hari.
- **Berhenti berlangganan:** opsional, pengguna bisa mematikan notifikasi per aplikasi lewat perintah bot `/berhenti hrd`.

## Manfaat

- Satu bot dan satu token, tersimpan hanya di eUniversity. Pegawai cukup menekan *Start* sekali.
- Aplikasi lain (BAAK, Keuangan, LPPM) langsung bisa mengirim notifikasi tanpa mengurus bot sendiri.
- Batas kirim, pengulangan saat gagal, dan log ditangani di satu tempat.

## Dampak ke HRD

Di sisi HRD perubahannya kecil. Fungsi `Telegram::kirim()` cukup diganti dengan panggilan `POST /notifikasi`
berisi username penerima. Kolom chat ID dan token bot di HRD lalu bisa dihapus.

Sementara endpoint ini belum ada, HRD tetap mengirim langsung lewat bot. Syaratnya token bot OTP dibagikan kepada kami,
dan token itu kami simpan hanya di `.env` server.

Terima kasih.
Tim HRD ITB PalComTech
