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

    // Isian data dashboard (dashboard sendiri hanya menampilkan)
    public static function input(): array
    {
        return [
            ['input.kehadiran', 'clock-history', 'Kehadiran & Keterlambatan', 'Rekap bulanan: impor Excel atau isi manual'],
            ['input.disc', 'person-bounding-box', 'Peta Karakter (DISC)', 'Hasil tes DISC, komitmen & kompetensi pegawai'],
            ['dashboard.dosen.rapor.index', 'award', 'Rapor & Capaian Dosen', 'Sertifikat, penelitian, pengabdian, publikasi'],
            ['dashboard.dosen.kompetensi', 'bar-chart-steps', 'Kompetensi Dosen', 'Skill sekarang & target (HRD atau atasan)'],
            ['dashboard.dosen.mk', 'book', 'MK Inti Prodi', 'Mata kuliah inti per kurikulum & pengampu'],
        ];
    }

    public static function pengaturan(): array
    {
        return [
            ['jenis-cuti.index', 'sliders', 'Jenis & Jatah Cuti', 'Jenis cuti dan jatah hari per tahun'],
            ['hari-libur.index', 'calendar-event', 'Kalender Libur', 'Libur nasional & cuti bersama (memotong cuti tahunan)'],
            ['jenis-pengajuan.index', 'ui-checks', 'Jenis Pengajuan', 'Form pengajuan pegawai: isian & alur persetujuan'],
            ['pengaturan.pejabat', 'person-badge', 'Peran Pejabat', 'Pegawai pemegang Warek I/II, Keuangan, BAAK untuk tahap persetujuan'],
            ['pengaturan.dokumen', 'qr-code', 'Dokumen & Penandatangan', 'Pejabat Rektor penanda tangan QR, kota, kode nomor surat'],
            ['pengaturan.api', 'plug', 'Integrasi API', 'Alamat API, akun layanan, pemetaan role, cek koneksi'],
        ];
    }
}
