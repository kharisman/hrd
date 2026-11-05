<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Kalender libur nasional & cuti bersama (admin), per tahun
class HariLiburController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->isAdmin(), 403);
            return $next($request);
        });
    }

    public function index(Request $r)
    {
        $tahun = (int) ($r->tahun ?: now()->year);
        return view('cuti.libur', [
            'tahun' => $tahun,
            'libur' => HariLibur::whereYear('tanggal', $tahun)->orderBy('tanggal')->get(),
            'potong' => HariLibur::cutiBersama($tahun),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'tanggal' => ['required', 'date', Rule::unique('hari_libur')],
            'sampai' => 'nullable|date|after_or_equal:tanggal',
            'nama' => 'required|string|max:150',
        ]);
        // Rentang (mis. libur Idul Fitri beberapa hari) → satu baris per tanggal
        foreach (\Carbon\CarbonPeriod::create($d['tanggal'], $d['sampai'] ?? $d['tanggal']) as $t) {
            HariLibur::updateOrCreate(['tanggal' => $t->toDateString()], ['nama' => $d['nama'], 'cuti_bersama' => $r->boolean('cuti_bersama')]);
        }
        return back()->with('ok', 'Hari libur disimpan.');
    }

    public function destroy(HariLibur $hariLibur)
    {
        $hariLibur->delete();
        return back()->with('ok', 'Hari libur dihapus.');
    }
}
