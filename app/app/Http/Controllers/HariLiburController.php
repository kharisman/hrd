<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    // Hapus semua libur satu tahun (mis. sebelum impor ulang)
    public function reset(int $tahun)
    {
        $n = HariLibur::whereYear('tanggal', $tahun)->delete();
        return redirect()->route('hari-libur.index', ['tahun' => $tahun])->with('ok', "Kalender {$tahun} direset ({$n} tanggal dihapus).");
    }

    // Impor Excel: Tanggal | Sampai (opsional) | Keterangan | Cuti bersama (Ya/kosong); baris 1 = judul
    public function impor(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120']);
        try {
            $rows = IOFactory::load($r->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, false);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception) {
            return back()->withErrors('File tidak bisa dibaca. Pastikan formatnya .xlsx, .xls, atau .csv.');
        }
        $tgl = fn ($v) => blank($v) ? null : rescue(fn () => is_numeric($v) ? Carbon::instance(Date::excelToDateTimeObject($v)) : Carbon::parse($v), null, false);
        $n = 0;
        $gagal = [];
        foreach (array_slice($rows, 1) as $i => $row) {
            [$dari, $sampai, $nama, $cb] = array_pad($row, 4, null);
            if (blank($dari) && blank($nama)) {
                continue;
            }
            $dari = $tgl($dari);
            $sampai = $tgl($sampai) ?? $dari;
            if (!$dari || !$sampai || $sampai->lt($dari) || blank($nama)) {
                $gagal[] = 'baris ' . ($i + 2);
                continue;
            }
            foreach (\Carbon\CarbonPeriod::create($dari, $sampai) as $t) {
                HariLibur::updateOrCreate(['tanggal' => $t->toDateString()],
                    ['nama' => trim($nama), 'cuti_bersama' => in_array(strtolower(trim((string) $cb)), ['ya', 'y', '1', 'true', 'cuti bersama'])]);
                $n++;
            }
        }
        $hasil = back()->with('ok', "Impor: {$n} tanggal tersimpan.");
        return $gagal ? $hasil->withErrors('Dilewati (tanggal/keterangan tidak valid): ' . implode(', ', array_slice($gagal, 0, 20))) : $hasil;
    }

    // Template berisi contoh libur nasional & cuti bersama 2026 — cocokkan dulu dengan SKB 3 Menteri / SK Pimpinan
    public function template()
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Hari Libur');
        $sheet->fromArray([['Tanggal', 'Sampai', 'Keterangan', 'Cuti bersama'],
            ['2026-01-01', null, 'Tahun Baru 2026 Masehi', null],
            ['2026-01-16', null, "Isra Mi'raj Nabi Muhammad SAW", null],
            ['2026-02-16', null, 'Cuti bersama Tahun Baru Imlek', 'Ya'],
            ['2026-02-17', null, 'Tahun Baru Imlek 2577 Kongzili', null],
            ['2026-03-18', null, 'Cuti bersama Hari Suci Nyepi', 'Ya'],
            ['2026-03-19', null, 'Hari Suci Nyepi Tahun Baru Saka 1948', null],
            ['2026-03-20', '2026-03-21', 'Idul Fitri 1447 H', null],
            ['2026-03-23', '2026-03-24', 'Cuti bersama Idul Fitri 1447 H', 'Ya'],
            ['2026-04-03', null, 'Wafat Yesus Kristus', null],
            ['2026-04-05', null, 'Kebangkitan Yesus Kristus (Paskah)', null],
            ['2026-05-01', null, 'Hari Buruh Internasional', null],
            ['2026-05-14', null, 'Kenaikan Yesus Kristus', null],
            ['2026-05-15', null, 'Cuti bersama Kenaikan Yesus Kristus', 'Ya'],
            ['2026-05-27', null, 'Idul Adha 1447 H', null],
            ['2026-05-28', null, 'Cuti bersama Idul Adha 1447 H', 'Ya'],
            ['2026-05-31', null, 'Hari Raya Waisak 2570 BE', null],
            ['2026-06-01', null, 'Hari Lahir Pancasila', null],
            ['2026-06-16', null, 'Tahun Baru Islam 1448 H', null],
            ['2026-08-17', null, 'Hari Kemerdekaan RI', null],
            ['2026-08-25', null, 'Maulid Nabi Muhammad SAW', null],
            ['2026-12-24', null, 'Cuti bersama Hari Raya Natal', 'Ya'],
            ['2026-12-25', null, 'Hari Raya Natal', null],
        ]);
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        foreach (['A' => 12, 'B' => 12, 'C' => 40, 'D' => 14] as $k => $w) {
            $sheet->getColumnDimension($k)->setWidth($w);
        }
        $sheet->setCellValue('F1', 'Format tanggal YYYY-MM-DD. Sampai = untuk rentang beberapa hari. Cuti bersama: isi "Ya". Data contoh, cocokkan dengan SKB terbaru.');
        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), 'template-hari-libur.xlsx');
    }
}
