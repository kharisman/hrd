<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Dosen;
use App\Models\Staff;
use App\Services\PctApi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request, PctApi $api)
    {
        $cred = $request->validate(['username' => 'required|string', 'password' => 'required|string']);

        // Satu-satunya cara masuk: akun eUniversity via API
        try {
            $profil = $api->login($cred['username'], $cred['password']);
        } catch (ConnectionException|RequestException $e) {
            report($e);
            return back()->withInput()->with('error', 'Server eUniversity tidak bisa dihubungi. Coba lagi beberapa saat.');
        }

        if (!$profil) {
            return back()->withInput()->with('error', 'Username atau password salah');
        }

        // Username login = Username di master staff/dosen (salinan sinkron)
        $staff = Staff::where('username', $profil['username'])->first();
        $dosen = Dosen::where('username', $profil['username'])->first();

        [$peran, $tolak] = $this->tentukanPeran($profil, $staff, $dosen);
        if ($tolak) {
            $api->logout($profil['token']);
            return back()->withInput()->with('error', $tolak);
        }

        $akun = Akun::updateOrCreate(['api_id' => $profil['api_id']], [
            'username' => $profil['username'],
            'name' => $profil['name'],
            'roles' => $profil['roles'],
            'scopes' => $profil['scopes'],
            'peran' => $peran,
            'nip' => $profil['nip'],
            'staff_id' => $staff?->id,
            'nidn' => $profil['nidn'],
            'dosen_id' => $dosen?->id,
            'divisi' => $profil['divisi'],
            'level' => $profil['level'],
            'last_login_at' => now(),
        ]);

        auth('pegawai')->login($akun);
        $request->session()->regenerate();
        // Token eUniversity disimpan di session (server), dipakai untuk semua aksi ke API atas nama akun ini
        session(['pct_token' => $profil['token']]);
        return redirect()->route('dashboard');
    }

    /**
     * [peran, pesan tolak]. Role-map eUniversity hanya memberi role ke sebagian kecil divisi/level,
     * jadi kebanyakan staf login tanpa role. Karena itu bila role kosong, peran ditentukan dari
     * data pegawai/dosen aktif hasil sinkron (username sama).
     */
    private function tentukanPeran(array $profil, ?Staff $staff, ?Dosen $dosen): array
    {
        $peran = PctApi::peran($profil['roles']);
        if ($peran === 'admin') {
            return ['admin', null];
        }

        $staffAktif = $staff?->aktif;
        $dosenAktif = $dosen?->aktif;
        $peran ??= match (true) {
            (bool) $dosenAktif => 'dosen',
            (bool) $staffAktif => 'staff',
            default => null,
        };

        if (!$peran) {
            return [null, ($staff || $dosen)
                ? 'Akun Anda tercatat tidak aktif di eUniversity, sehingga tidak bisa masuk HRD. Hubungi bagian SDM.'
                : 'Akun Anda tidak terdaftar sebagai pegawai atau dosen, sehingga tidak bisa masuk HRD.'];
        }
        // Akun yang sudah dihapus dari master (API & salinan HRD) tidak boleh masuk walau masih punya role
        if (!$profil['terdaftar'] && !$staffAktif && !$dosenAktif) {
            return [null, 'Data pegawai/dosen Anda tidak ditemukan di eUniversity. Hubungi bagian SDM.'];
        }
        return [$peran, null];
    }

    public function logout(Request $request, PctApi $api)
    {
        $api->logout(session('pct_token')); // cabut token di eUniversity
        auth('pegawai')->logout();
        $request->session()->invalidate();
        return redirect()->route('login');
    }
}
