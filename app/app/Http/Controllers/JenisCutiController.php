<?php

namespace App\Http\Controllers;

use App\Models\JenisCuti;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Konfigurasi jenis cuti & jatah per tahun (admin). Tidak ada hapus: jenis yang sudah dipakai
// cukup dinonaktifkan supaya riwayat cuti tetap utuh.
class JenisCutiController extends Controller
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
        return view('cuti.jenis', ['jenis' => JenisCuti::withCount('cuti')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        JenisCuti::create($this->validasi($request));
        return back()->with('ok', 'Jenis cuti ditambahkan.');
    }

    public function update(Request $request, JenisCuti $jenisCuti)
    {
        $jenisCuti->update($this->validasi($request, $jenisCuti));
        return back()->with('ok', "{$jenisCuti->nama} disimpan.");
    }

    private function validasi(Request $request, ?JenisCuti $j = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('jenis_cuti')->ignore($j)],
            'kuota' => 'nullable|integer|min:1|max:366',
            'keterangan' => 'nullable|string|max:255',
        ]) + ['aktif' => $request->boolean('aktif')];
    }
}
