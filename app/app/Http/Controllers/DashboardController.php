<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use App\Models\Dosen;
use App\Models\JenisCuti;
use App\Models\RiwayatPendidikan;
use App\Models\Staff;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return $this->isAdmin() ? $this->admin() : $this->pegawai();
    }

    private function admin()
    {
        $hariIni = now()->toDateString();
        $dosenAktif = Dosen::where('aktif', true);
        $terakhirSinkron = max(Staff::max('disinkron_at'), Dosen::max('disinkron_at'));

        // Kelompok → jumlah, terbesar dulu (untuk grafik batang)
        $hitung = fn ($q, $kolom, $kosong) => $q->selectRaw("coalesce($kolom, '$kosong') k, count(*) c")
            ->groupBy('k')->orderByDesc('c')->toBase()->pluck('c', 'k');

        return view('dashboard.admin', [
            'kartu' => [
                ['Pegawai aktif', Staff::aktif()->count(), 'people', route('staff.index'), 'dari ' . Staff::count() . ' akun staff'],
                ['Dosen aktif', (clone $dosenAktif)->count(), 'mortarboard', route('dosen.index'), 'dari ' . Dosen::count() . ' dosen terdaftar'],
                ['Cuti menunggu', Cuti::where('status', 'Menunggu')->count(), 'hourglass-split', route('cuti.index', ['status' => 'Menunggu']), 'perlu persetujuan'],
                ['Sedang cuti', Cuti::where('status', 'Disetujui')->whereDate('tgl_mulai', '<=', $hariIni)->whereDate('tgl_selesai', '>=', $hariIni)->count(), 'calendar-x', route('cuti.index', ['status' => 'Disetujui']), 'hari ini'],
                ['Studi lanjut', RiwayatPendidikan::where('status', 'Sedang Studi Lanjut')->count(), 'journal-bookmark', route('riwayat.index'), 'sedang berjalan'],
            ],
            'tindakan' => [
                ['Cuti menunggu persetujuan', Cuti::where('status', 'Menunggu')->count(), route('cuti.index', ['status' => 'Menunggu'])],
                ['Pengajuan menunggu HRD', \App\Models\Pengajuan::where('status', \App\Models\Pengajuan::MENUNGGU_HRD)->count(), route('pengajuan.index', ['tab' => 'semua', 'status' => \App\Models\Pengajuan::MENUNGGU_HRD])],
                ['Pegawai aktif belum punya atasan', Staff::aktif()->whereNull('atasan_id')->count(), route('staff.atasan')],
                ['Dosen aktif tanpa jabatan fungsional', (clone $dosenAktif)->whereNull('jabatan_fungsional')->count(), route('dosen.index')],
                ['Studi lanjut lewat target lulus', RiwayatPendidikan::where('status', 'Sedang Studi Lanjut')->where('tahun_lulus', '<', now()->year)->count(), route('riwayat.index')],
            ],
            'terakhirSinkron' => $terakhirSinkron ? \Illuminate\Support\Carbon::parse($terakhirSinkron) : null,
            'cutiMenunggu' => Cuti::with('staff', 'jenisCuti')->where('status', 'Menunggu')->orderBy('tgl_mulai')->limit(6)->get(),
            'akanCuti' => Cuti::with('staff', 'jenisCuti')->where('status', 'Disetujui')
                ->whereDate('tgl_selesai', '>=', $hariIni)->whereDate('tgl_mulai', '<=', now()->addDays(7))
                ->orderBy('tgl_mulai')->limit(8)->get(),
            'perDivisi' => $hitung(Staff::aktif(), 'divisi', 'Tanpa divisi'),
            'jabfung' => $hitung(clone $dosenAktif, 'jabatan_fungsional', 'Belum diisi'),
            'pendidikan' => $hitung(clone $dosenAktif, 'pendidikan_terakhir', 'Belum diisi'),
        ]);
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
            'jatah' => $me ? JenisCuti::rekap($me) : collect(),
            'cutiSaya' => $me ? Cuti::with('jenisCuti')->where('staff_id', $me)->latest()->limit(5)->get() : collect(),
            'cutiBawahan' => Cuti::with('staff', 'jenisCuti')->whereIn('staff_id', $bawahan)->where('status', 'Menunggu')->orderBy('tgl_mulai')->get(),
            'pengajuanBawahan' => \App\Models\Pengajuan::whereIn('staff_id', $bawahan)->where('anonim', false)->where('status', \App\Models\Pengajuan::MENUNGGU_ATASAN)->count(),
            'jumlahBawahan' => $bawahan->count(),
        ]);
    }
}
