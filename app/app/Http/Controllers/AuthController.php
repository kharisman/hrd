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

        $peran = PctApi::peran($profil['roles']);
        if (!$peran || ($peran !== 'admin' && !$profil['terdaftar'])) {
            $api->logout($profil['token']);
            return back()->withInput()->with('error', 'Akun Anda tidak memiliki akses ke HRD.');
        }

        $akun = Akun::updateOrCreate(['api_id' => $profil['api_id']], [
            'username' => $profil['username'],
            'name' => $profil['name'],
            'roles' => $profil['roles'],
            'scopes' => $profil['scopes'],
            'peran' => $peran,
            'nip' => $profil['nip'],
            // Username login = Username di master staff/dosen (salinan sinkron)
            'staff_id' => Staff::where('username', $profil['username'])->value('id'),
            'nidn' => $profil['nidn'],
            'dosen_id' => Dosen::where('username', $profil['username'])->value('id'),
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

    public function logout(Request $request, PctApi $api)
    {
        $api->logout(session('pct_token')); // cabut token di eUniversity
        auth('pegawai')->logout();
        $request->session()->invalidate();
        return redirect()->route('login');
    }
}
