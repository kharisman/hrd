<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Dosen;
use App\Models\Staff;
use App\Models\User;
use App\Services\PctApi;
use App\Support\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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

    // ---- Admin lokal (tabel users): cadangan saat API eUniversity tidak bisa diakses ----

    public function admin()
    {
        return view('pengaturan.admin', ['admins' => User::orderBy('name')->get()]);
    }

    public function adminStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        User::create(['password' => Hash::make($data['password'])] + $data);
        return back()->with('ok', "Admin {$data['email']} ditambahkan.");
    }

    // Ganti nama/email, dan password bila diisi
    public function adminUpdate(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $user->update(collect($data)->except('password')->all() + ($data['password'] ? ['password' => Hash::make($data['password'])] : []));
        return back()->with('ok', "Admin {$user->email} disimpan" . ($data['password'] ? ' (password diganti).' : '.'));
    }

    public function adminDestroy(User $user)
    {
        // Jangan sampai tidak ada admin lokal sama sekali / menghapus diri sendiri
        abort_if(User::count() <= 1, 422, 'Minimal harus ada satu admin lokal.');
        abort_if(auth('web')->id() === $user->id, 422, 'Tidak bisa menghapus akun yang sedang dipakai.');
        $user->delete();
        return back()->with('ok', "Admin {$user->email} dihapus.");
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
