<?php

namespace App\Support;

// Daftar menu Laporan & Pengaturan: dipakai sidebar dan halaman induk /laporan, /pengaturan.
// [route, ikon bootstrap-icons, judul, keterangan]
class Menu
{
    public static function laporan(): array
    {
        return [
            ['laporan.cuti', 'calendar-range', 'Rekap Cuti', 'Pemakaian & sisa jatah cuti per pegawai per tahun'],
            ['laporan.pegawai', 'people', 'Daftar Pegawai', 'Pegawai per divisi & level, atasan, status kepegawaian'],
            ['laporan.dosen', 'mortarboard', 'Daftar Dosen', 'Dosen beserta NIDN, jabatan fungsional, pendidikan, serdos'],
            ['laporan.pendidikan', 'journal-bookmark', 'Riwayat Pendidikan', 'Seluruh riwayat pendidikan & studi lanjut'],
            ['laporan.pengajuan', 'ui-checks', 'Rekap Pengajuan', 'Jumlah pengajuan per jenis & status'],
        ];
    }

    public static function pengaturan(): array
    {
        return [
            ['jenis-cuti.index', 'sliders', 'Jenis & Jatah Cuti', 'Jenis cuti dan jatah hari per tahun'],
            ['jenis-pengajuan.index', 'ui-checks', 'Jenis Pengajuan', 'Form pengajuan pegawai: isian & alur persetujuan'],
            ['pengaturan.pejabat', 'person-badge', 'Peran Pejabat', 'Pegawai pemegang Warek I/II, Keuangan, BAAK untuk tahap persetujuan'],
            ['pengaturan.dokumen', 'qr-code', 'Dokumen & Penandatangan', 'Pejabat Rektor penanda tangan QR, kota, kode nomor surat'],
            ['pengaturan.api', 'plug', 'Integrasi API', 'Alamat API, akun layanan, pemetaan role, cek koneksi'],
        ];
    }
}
