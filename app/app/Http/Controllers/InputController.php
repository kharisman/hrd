<?php

namespace App\Http\Controllers;

use App\Models\Disc;
use App\Models\Kehadiran;
use App\Models\Staff;
use App\Support\Menu;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Menu "Input Data" milik HRD: dashboard hanya menampilkan, isian datanya di sini.
// Simpan memakai route lama (dashboard.pegawai.simpan / .disc) supaya tidak ada logika ganda.
class InputController extends Controller
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
        return view('menu-induk', ['judul' => 'Input Data', 'item' => Menu::input()]);
    }

    public function kehadiran(Request $r)
    {
        $tahun = $r->integer('tahun') ?: now()->year;
        $bulan = $r->integer('bulan') ?: now()->month;
        return view('input.kehadiran', [
            'tahun' => $tahun, 'bulan' => $bulan,
            'baris' => Staff::perOrang(Staff::aktif()),
            'isian' => Kehadiran::where(['tahun' => $tahun, 'bulan' => $bulan])->get()->keyBy('staff_id'),
        ]);
    }

    // Impor Excel rekap satu bulan: kolom NIP | Terlambat (menit) | Tidak hadir (hari), baris 1 = judul.
    // ponytail: susunan kolom tetap (template); kalau ekspor mesin absen beda, susun ulang kolomnya di Excel dulu
    public function impor(Request $r)
    {
        $d = $r->validate(['tahun' => 'required|integer|min:2000|max:2100', 'bulan' => 'required|integer|min:1|max:12',
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        try {
            $rows = IOFactory::load($r->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, false);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception) {
            return back()->withErrors('File tidak bisa dibaca. Pastikan formatnya .xlsx, .xls, atau .csv.');
        }
        $n = 0;
        $gagal = [];
        foreach (array_slice($rows, 1) as $i => $row) {
            [$nip, $telat, $absen] = array_pad($row, 3, null);
            $nip = trim((string) $nip);
            if ($nip === '') {
                continue;
            }
            $isi = array_filter(['terlambat_menit' => is_numeric($telat) ? max(0, (int) $telat) : null,
                'tidak_hadir_hari' => is_numeric($absen) ? min(31, max(0, (float) $absen)) : null], fn ($v) => $v !== null);
            $staff = Staff::utama($nip);
            if (!$staff || !$isi) {
                $gagal[] = 'baris ' . ($i + 2) . " ($nip)";
                continue;
            }
            Kehadiran::updateOrCreate(['staff_id' => $staff->id, 'tahun' => $d['tahun'], 'bulan' => $d['bulan']], $isi);
            $n++;
        }
        $hasil = back()->with('ok', 'Impor ' . Kehadiran::BULAN[$d['bulan']] . " {$d['tahun']}: {$n} orang tersimpan.");
        return $gagal ? $hasil->withErrors('Dilewati (NIP tidak dikenal / isian kosong): ' . implode(', ', array_slice($gagal, 0, 20))) : $hasil;
    }

    public function template()
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Kehadiran');
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@'); // NIP sebagai teks
        $sheet->fromArray([['NIP', 'Terlambat (menit)', 'Tidak hadir (hari)'], ['13.PCT.07', 45, 1]]);
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), 'template-kehadiran.xlsx');
    }

    public function disc(Request $r)
    {
        return view('input.disc', [
            'edit' => $r->staff ? Disc::with('staff')->firstOrNew(['staff_id' => $r->integer('staff')]) : null,
            'pilihanStaff' => Staff::perOrang(Staff::aktif()),
            'daftarTim' => Disc::pluck('tim')->filter()->unique()->sort()->values(),
            'sudah' => Disc::with('staff')->get()->sortBy(fn ($d) => $d->staff->nama ?? ''),
        ]);
    }
}
