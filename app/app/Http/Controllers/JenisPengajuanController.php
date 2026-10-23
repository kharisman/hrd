<?php

namespace App\Http\Controllers;

use App\Models\JenisPengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Konfigurasi jenis form pengajuan (admin). Tidak ada hapus: jenis yang sudah dipakai cukup dinonaktifkan.
class JenisPengajuanController extends Controller
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
        return view('jenis-pengajuan.index', ['jenis' => JenisPengajuan::withCount('pengajuan')->orderBy('urutan')->get()->groupBy('kategori')]);
    }

    public function create()
    {
        return view('jenis-pengajuan.form', ['j' => new JenisPengajuan(['alur' => 'atasan_hrd', 'aktif' => true, 'isian' => []])]);
    }

    public function store(Request $r)
    {
        $j = JenisPengajuan::create($this->validasi($r) + ['urutan' => (int) JenisPengajuan::max('urutan') + 1]);
        return redirect()->route('jenis-pengajuan.index')->with('ok', "Jenis \"{$j->nama}\" ditambahkan.");
    }

    public function edit(JenisPengajuan $jenisPengajuan)
    {
        return view('jenis-pengajuan.form', ['j' => $jenisPengajuan]);
    }

    public function update(Request $r, JenisPengajuan $jenisPengajuan)
    {
        $jenisPengajuan->update($this->validasi($r, $jenisPengajuan));
        return redirect()->route('jenis-pengajuan.index')->with('ok', "Jenis \"{$jenisPengajuan->nama}\" disimpan.");
    }

    private function validasi(Request $r, ?JenisPengajuan $j = null): array
    {
        $d = $r->validate([
            'nama' => ['required', 'string', 'max:150', Rule::unique('jenis_pengajuan')->ignore($j)],
            'kategori' => 'required|string|max:100',
            'keterangan' => 'nullable|string|max:255',
            'alur' => ['required', Rule::in(array_keys(JenisPengajuan::ALUR))],
            'isian' => 'required|array|min:1',
            'isian.*.key' => 'nullable|string|max:60',
            'isian.*.label' => 'required|string|max:150',
            'isian.*.tipe' => ['required', Rule::in(array_keys(JenisPengajuan::TIPE))],
            'isian.*.opsi' => 'nullable|string|max:1000',
            'isian.*.wajib' => 'nullable',
        ], ['isian.required' => 'Minimal satu isian.'], ['isian.*.label' => 'label isian']);

        // key dipertahankan untuk isian lama (data pengajuan lama tetap terbaca); isian baru dibuat dari label
        $dipakai = [];
        $isian = [];
        foreach (array_values($d['isian']) as $i => $f) {
            $key = ($f['key'] ?? null) ?: (Str::slug($f['label'], '_') ?: "isian_$i");
            while (in_array($key, $dipakai)) {
                $key .= '_' . $i;
            }
            $dipakai[] = $key;
            $opsi = $f['tipe'] === 'select' ? array_values(array_filter(array_map('trim', explode(',', $f['opsi'] ?? '')))) : [];
            if ($f['tipe'] === 'select' && !$opsi) {
                throw \Illuminate\Validation\ValidationException::withMessages(["isian.$i.opsi" => "Isian \"{$f['label']}\" bertipe Pilihan butuh daftar opsi (pisahkan dengan koma)."]);
            }
            $isian[] = ['key' => $key, 'label' => $f['label'], 'tipe' => $f['tipe'], 'wajib' => !empty($f['wajib']), 'opsi' => $opsi];
        }

        return collect($d)->only(['nama', 'kategori', 'keterangan', 'alur'])->all() + [
            'isian' => $isian,
            'boleh_anonim' => $r->boolean('boleh_anonim'),
            'aktif' => $r->boolean('aktif'),
        ];
    }
}
