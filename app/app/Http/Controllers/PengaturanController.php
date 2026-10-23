<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Dosen;
use App\Models\Staff;
use App\Services\PctApi;
use App\Support\Menu;
use Illuminate\Support\Carbon;

class PengaturanController extends Controller
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
        return view('menu-induk', ['judul' => 'Pengaturan', 'item' => Menu::pengaturan()]);
    }

    // ---- Sinkron eUniversity ----

    public function sinkron()
    {
        $waktu = fn ($v) => $v ? Carbon::parse($v) : null;
        return view('pengaturan.sinkron', [
            'info' => [
                ['Pegawai (staff)', Staff::count(), $waktu(Staff::max('disinkron_at'))],
                ['Dosen', Dosen::count(), $waktu(Dosen::max('disinkron_at'))],
            ],
        ]);
    }

    // ---- Integrasi API ----

    public function api()
    {
        // Izin akun yang login untuk tiap aksi HRD → eUniversity
        $izin = [
            'Sinkron data' => ['staff.read', 'dosen.read'],
            'Tambah dosen' => ['dosen.create'], 'Ubah dosen' => ['dosen.update'], 'Hapus dosen' => ['dosen.delete'],
            'Tambah pegawai' => ['staff.create'], 'Ubah pegawai' => ['staff.update'], 'Hapus pegawai' => ['staff.delete'],
        ];
        return view('pengaturan.api', [
            'url' => config('services.pct.url'),
            'adaKey' => filled(config('services.pct.key')),
            'akun' => auth('pegawai')->user(),
            'adaToken' => session()->has('pct_token'),
            'izin' => collect($izin)->map(fn ($s) => [$s, \Illuminate\Support\Facades\Gate::allows('api', $s)]),
            'peran' => config('services.pct.peran'),
            'akunLogin' => Akun::orderByDesc('last_login_at')->limit(10)->get(),
        ]);
    }

    // Uji koneksi dengan token akun yang login: ambil 1 data staff
    public function cekApi(PctApi $api)
    {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('api', 'staff.read'), 403, 'Cek koneksi butuh login eUniversity dengan izin staff.read.');
        $mulai = microtime(true);
        try {
            $staff = $api->ambilSatuHalaman('/staff');
            $dtk = round(microtime(true) - $mulai, 1);
            return back()->with('ok', "Koneksi berhasil dalam {$dtk} detik. Token akun Anda valid, total staff di API: {$staff}.");
        } catch (\App\Services\SesiApiHabis $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors('Koneksi gagal: ' . \Illuminate\Support\Str::limit($e->getMessage(), 200));
        }
    }
}
