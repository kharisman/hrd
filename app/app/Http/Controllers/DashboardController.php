<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\JenisCuti;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use App\Models\RiwayatPendidikan;
use App\Models\Staff;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Admin: ringkasan SDM ada di tab pertama Dashboard Pegawai
        return $this->isAdmin() ? redirect()->route('dashboard.pegawai') : $this->pegawai();
    }

    // Data tab Ringkasan di Dashboard Pegawai
    public function ringkasan(): array
    {
        $hariIni = now()->toDateString();
        $cutiMenunggu = Pengajuan::cuti()->menunggu();
        $daftarCuti = fn ($status = null) => route('pengajuan.index', ['tab' => 'semua', 'jenis' => JenisPengajuan::where('kode', 'cuti')->value('id'), 'status' => $status]);
        $dosenAktif = Dosen::where('aktif', true);
        $terakhirSinkron = max(Staff::max('disinkron_at'), Dosen::max('disinkron_at'));

        // Kelompok → jumlah, terbesar dulu (untuk grafik batang)
        $hitung = fn ($q, $kolom, $kosong) => $q->selectRaw("coalesce($kolom, '$kosong') k, count(*) c")
            ->groupBy('k')->orderByDesc('c')->toBase()->pluck('c', 'k');

        return [
            'kartu' => [
                ['Pegawai aktif', Staff::aktif()->count(), 'people', route('staff.index'), 'dari ' . Staff::count() . ' akun staff'],
                ['Dosen aktif', (clone $dosenAktif)->count(), 'mortarboard', route('dosen.index'), 'dari ' . Dosen::count() . ' dosen terdaftar'],
                ['Cuti menunggu', (clone $cutiMenunggu)->count(), 'hourglass-split', $daftarCuti(), 'atasan / HRD'],
                ['Sedang cuti', Pengajuan::cuti()->where('status', 'Disetujui')->whereDate('tgl_mulai', '<=', $hariIni)->whereDate('tgl_selesai', '>=', $hariIni)->count(), 'calendar-x', $daftarCuti('Disetujui'), 'hari ini'],
                ['Studi lanjut', RiwayatPendidikan::where('status', 'Sedang Studi Lanjut')->count(), 'journal-bookmark', route('riwayat.index'), 'sedang berjalan'],
            ],
            'tindakan' => [
                ['Pengajuan (termasuk cuti) menunggu HRD', Pengajuan::tugas($this->myStaffIds(), true)->count(), route('pengajuan.index', ['tab' => 'tugas'])],
                ['Pegawai aktif belum punya atasan', Staff::aktif()->whereNull('atasan_id')->count(), route('staff.atasan')],
                ['Dosen aktif tanpa jabatan fungsional', (clone $dosenAktif)->whereNull('jabatan_fungsional')->count(), route('dosen.index')],
                ['Studi lanjut lewat target lulus', RiwayatPendidikan::where('status', 'Sedang Studi Lanjut')->where('tahun_lulus', '<', now()->year)->count(), route('riwayat.index')],
            ],
            'terakhirSinkron' => $terakhirSinkron ? \Illuminate\Support\Carbon::parse($terakhirSinkron) : null,
            'cutiMenunggu' => (clone $cutiMenunggu)->with('staff', 'jenisCuti')->orderBy('tgl_mulai')->limit(6)->get(),
            'semuaCutiMenunggu' => $daftarCuti(),
            'akanCuti' => Pengajuan::cuti()->with('staff', 'jenisCuti')->where('status', 'Disetujui')
                ->whereDate('tgl_selesai', '>=', $hariIni)->whereDate('tgl_mulai', '<=', now()->addDays(7))
                ->orderBy('tgl_mulai')->limit(8)->get(),
            'perDivisi' => $hitung(Staff::aktif(), 'divisi', 'Tanpa divisi'),
            'jabfung' => $hitung(clone $dosenAktif, 'jabatan_fungsional', 'Belum diisi'),
            'pendidikan' => $hitung(clone $dosenAktif, 'pendidikan_terakhir', 'Belum diisi'),
        ];
    }

    // Dashboard pribadi untuk admin/pejabat (yang juga pegawai): jatah cuti, cuti saya, persetujuan
    public function saya()
    {
        return $this->pegawai();
    }

    private function pegawai()
    {
        $akun = auth('pegawai')->user();
        $staff = $akun->staff()->with('atasan')->first();
        $me = $staff?->id;
        $bawahan = Staff::whereIn('atasan_id', $this->myStaffIds())->pluck('id');

        return view('dashboard.pegawai', [
            'akun' => $akun,
            'staff' => $staff,
            'jatah' => $me ? JenisCuti::rekap($this->myStaffIds()->all()) : collect(),
            'cutiSaya' => Pengajuan::cuti()->with('jenisCuti')->whereIn('staff_id', $this->myStaffIds())->latest()->limit(5)->get(),
            'pengajuanBawahan' => Pengajuan::tugas($this->myStaffIds(), false)->count(),
            'jenisCuti' => JenisPengajuan::where('kode', 'cuti')->value('id'),
            'jumlahBawahan' => $bawahan->count(),
        ]);
    }
}
