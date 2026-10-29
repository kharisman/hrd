<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use App\Models\Bimbingan;
use App\Models\Dosen;
use App\Models\Kurikulum;
use App\Models\Mengajar;
use App\Models\MkInti;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\RasioProdi;
use App\Models\Staff;
use App\Models\Struktur;
use App\Services\PctApi;
use App\Services\Sinkron;
use App\Services\SinkronAkademik;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

// Menu Sinkron eUniversity: satu tombol per data supaya tidak berat (bisa dijalankan sendiri-sendiri).
// Memakai token aplikasi HRD (bukan akun yang login), jadi tidak bergantung izin akun admin.
class SinkronController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->isAdmin(), 403);
            return $next($request);
        });
    }

    public function index()
    {
        $langkah = ['pegawai_dosen' => ['Pegawai & Dosen', '/staff, /dosen, /users', []]] + SinkronAkademik::LANGKAH;
        return view('sinkron', [
            'langkah' => collect($langkah)->map(fn ($l, $k) => (object) [
                'kunci' => $k, 'label' => $l[0], 'endpoint' => $l[1],
                'butuh' => collect($l[2])->map(fn ($b) => $langkah[$b][0]),
                'jumlah' => $this->jumlah($k),
                'terakhir' => SinkronAkademik::terakhir($k),
            ]),
        ]);
    }

    // Satu langkah. Dipanggil tombol per baris (form) atau "Sinkron Semua" (fetch berurutan, minta JSON).
    public function jalankan(Request $r, string $langkah, PctApi $api)
    {
        abort_unless($langkah === 'pegawai_dosen' || isset(SinkronAkademik::LANGKAH[$langkah]), 404);
        set_time_limit(0); // jaringan ke beta1 lambat
        $mulai = microtime(true);
        try {
            $token = $api->tokenAplikasi();
            app()->instance(PctApi::class, $api->pakaiToken($token));
            if ($langkah === 'pegawai_dosen') {
                $s = app(Sinkron::class)->jalankan();
                SinkronAkademik::catat($langkah, $hasil = $s['staff_api'] + $s['dosen_api']);
            } else {
                $hasil = app(SinkronAkademik::class)->satu($langkah);
            }
        } catch (ConnectionException|RequestException $e) {
            report($e);
            $pesan = 'Sinkron gagal: server eUniversity tidak bisa dihubungi. Coba lagi nanti.';
            return $r->expectsJson() ? response()->json(['pesan' => $pesan], 502) : back()->withErrors($pesan);
        } finally {
            isset($token) && $api->logout($token);
        }
        $label = $langkah === 'pegawai_dosen' ? 'Pegawai & Dosen' : SinkronAkademik::LANGKAH[$langkah][0];
        $pesan = sprintf('%s disinkron: %s data dalam %.1f detik.', $label, number_format($hasil), microtime(true) - $mulai);
        return $r->expectsJson()
            ? response()->json(['pesan' => $pesan, 'jumlah' => $this->jumlah($langkah), 'waktu' => now()->translatedFormat('d M Y H:i'), 'hasil' => number_format($hasil)])
            : back()->with('ok', $pesan);
    }

    // Jumlah data lokal (salinan) per langkah
    private function jumlah(string $k): string
    {
        return match ($k) {
            'pegawai_dosen' => Staff::count() . ' pegawai, ' . Dosen::count() . ' dosen',
            'prodi' => Prodi::count() . ' prodi',
            'kurikulum' => Kurikulum::count() . ' kurikulum',
            'periode' => PeriodeKrs::count() . ' periode',
            'angkatan' => Angkatan::count() . ' angkatan',
            'struktur' => Struktur::count() . ' pasangan',
            'mahasiswa' => RasioProdi::where('tahun_ajaran', PeriodeKrs::tahunAjaran()->first())->sum('jumlah_mahasiswa') . ' mahasiswa aktif',
            'dosen_pa' => Dosen::sum('jumlah_pa') . ' mahasiswa bimbingan',
            'penguji' => Dosen::where('penguji', true)->count() . ' dosen penguji',
            'role' => Staff::whereNotNull('roles')->count() . ' pegawai ber-role',
            'mengajar' => Mengajar::count() . ' kelas',
            'bimbingan' => Bimbingan::count() . ' bimbingan',
            'tautan_mk' => MkInti::whereNotNull('kode_mk')->count() . ' / ' . MkInti::count() . ' MK tertaut',
        };
    }
}
