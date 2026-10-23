<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\RiwayatPendidikan;
use App\Services\ImporRiwayat;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// Rekam jejak pendidikan & studi lanjut. Diisi admin; pemilik bisa melihat & mengunduh berkasnya.
class RiwayatController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->isAdmin(), 403);
            return $next($request);
        })->except('berkas');
    }

    // Rekap studi lanjut + komposisi pendidikan dosen aktif
    public function index(Request $request)
    {
        $studi = RiwayatPendidikan::with('staff', 'dosen')
            ->where('status', $request->input('status', 'Sedang Studi Lanjut'))
            ->when($request->jenjang, fn ($q, $v) => $q->where('jenjang', $v))
            ->when($request->pembiayaan, fn ($q, $v) => $q->where('pembiayaan', $v))
            ->orderBy('tahun_lulus')
            ->paginate(50)->withQueryString();

        $komposisi = Dosen::where('aktif', true)
            ->selectRaw("coalesce(pendidikan_terakhir, 'Belum diisi') j, count(*) c")
            ->groupBy('j')->pluck('c', 'j');

        return view('riwayat.index', compact('studi', 'komposisi'));
    }

    public function imporForm()
    {
        return view('riwayat.impor');
    }

    public function impor(Request $request, ImporRiwayat $impor)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        $simpan = $request->input('mode') === 'simpan';
        try {
            $hasil = $impor->jalankan($request->file('file')->getRealPath(), $simpan);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors($e->getMessage());
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            return back()->withErrors('File tidak bisa dibaca. Pastikan formatnya .xlsx, .xls, atau .csv.');
        }
        return view('riwayat.impor', $hasil + ['simpan' => $simpan]);
    }

    // Template Excel: judul kolom + contoh baris + sheet petunjuk pilihan isian
    public function template()
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Riwayat');
        $sheet->fromArray([ImporRiwayat::KOLOM, ['13.PCT.07', '', '', 'S2', 'Universitas Sriwijaya', 'Teknik Informatika', 2015, 2017, 'Lulus', 'Mandiri', '']]);
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A:C')->getNumberFormat()->setFormatCode('@'); // NIP/NIDN sebagai teks (nol di depan tidak hilang)
        foreach (range('A', 'K') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $info = $book->createSheet()->setTitle('Petunjuk');
        $info->fromArray([
            ['Kolom', 'Isian'],
            ['nip / nidn / username', 'Isi salah satu untuk menentukan orangnya (NIP pegawai, NIDN dosen, atau username eUniversity)'],
            ['jenjang', implode(', ', RiwayatPendidikan::JENJANG) . ' (wajib)'],
            ['institusi', 'Nama perguruan tinggi / sekolah (wajib)'],
            ['tahun_lulus', 'Untuk studi lanjut: tahun target lulus'],
            ['status', implode(', ', RiwayatPendidikan::STATUS) . ' (kosong = Lulus)'],
            ['pembiayaan', implode(', ', RiwayatPendidikan::PEMBIAYAAN)],
            ['', 'Baris yang sama (orang + jenjang + institusi) yang sudah ada akan dilewati.'],
        ]);
        $info->getColumnDimension('A')->setAutoSize(true);
        $info->getColumnDimension('B')->setAutoSize(true);
        $book->setActiveSheetIndex(0);

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), 'template-riwayat-pendidikan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function create(Request $request)
    {
        $riwayat = new RiwayatPendidikan(['staff_id' => $request->staff_id, 'dosen_id' => $request->dosen_id, 'status' => 'Lulus']);
        return view('riwayat.form', compact('riwayat'));
    }

    public function store(Request $request)
    {
        $riwayat = RiwayatPendidikan::create($this->validasi($request));
        $this->simpanBerkas($request, $riwayat);
        $this->perbaruiPendidikanTerakhir($riwayat);
        return redirect($this->kembali($riwayat))->with('ok', 'Riwayat pendidikan ditambahkan.');
    }

    public function edit(RiwayatPendidikan $riwayat)
    {
        return view('riwayat.form', compact('riwayat'));
    }

    public function update(Request $request, RiwayatPendidikan $riwayat)
    {
        $riwayat->update($this->validasi($request));
        $this->simpanBerkas($request, $riwayat);
        $this->perbaruiPendidikanTerakhir($riwayat);
        return redirect($this->kembali($riwayat))->with('ok', 'Riwayat pendidikan disimpan.');
    }

    public function destroy(RiwayatPendidikan $riwayat)
    {
        $kembali = $this->kembali($riwayat);
        Storage::delete($riwayat->berkas ?? '');
        $riwayat->delete();
        $this->perbaruiPendidikanTerakhir($riwayat);
        return redirect($kembali)->with('ok', 'Riwayat pendidikan dihapus.');
    }

    // Berkas ijazah/SK disimpan privat; hanya admin atau pemiliknya yang boleh membuka
    public function berkas(RiwayatPendidikan $riwayat)
    {
        $akun = auth('pegawai')->user();
        $pemilik = $akun && (($riwayat->staff_id && $riwayat->staff_id == $akun->staff_id)
            || ($riwayat->dosen_id && $riwayat->dosen_id == $akun->dosen_id));
        abort_unless($this->isAdmin() || $pemilik, 403);
        abort_unless($riwayat->berkas && Storage::exists($riwayat->berkas), 404);
        return Storage::response($riwayat->berkas);
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'staff_id' => ['nullable', 'required_without:dosen_id', Rule::exists('staffs', 'id')],
            'dosen_id' => ['nullable', Rule::exists('dosens', 'id')],
            'jenjang' => ['required', Rule::in(RiwayatPendidikan::JENJANG)],
            'institusi' => 'required|string|max:200',
            'program_studi' => 'nullable|string|max:150',
            'tahun_masuk' => 'nullable|integer|min:1950|max:2100',
            'tahun_lulus' => 'nullable|integer|min:1950|max:2100|gte:tahun_masuk',
            'status' => ['required', Rule::in(RiwayatPendidikan::STATUS)],
            'pembiayaan' => ['nullable', Rule::in(RiwayatPendidikan::PEMBIAYAAN)],
            'keterangan' => 'nullable|string',
            'berkas' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], ['tahun_lulus.gte' => 'Tahun lulus tidak boleh sebelum tahun masuk.']);

        // Dosen yang juga pegawai: simpan kedua tautan supaya riwayatnya satu
        if (empty($data['staff_id']) && !empty($data['dosen_id'])) {
            $data['staff_id'] = Dosen::whereKey($data['dosen_id'])->value('staff_id');
        }
        if (empty($data['dosen_id']) && !empty($data['staff_id'])) {
            $data['dosen_id'] = Dosen::where('staff_id', $data['staff_id'])->value('id');
        }
        unset($data['berkas']);
        return $data;
    }

    private function simpanBerkas(Request $request, RiwayatPendidikan $riwayat): void
    {
        if ($request->hasFile('berkas')) {
            Storage::delete($riwayat->berkas ?? '');
            $riwayat->update(['berkas' => $request->file('berkas')->store('riwayat')]);
        }
    }

    private function perbaruiPendidikanTerakhir(RiwayatPendidikan $r): void
    {
        RiwayatPendidikan::perbaruiPendidikanTerakhir($r->staff_id, $r->dosen_id);
    }

    private function kembali(RiwayatPendidikan $r): string
    {
        return $r->staff_id ? route('staff.show', $r->staff_id) : route('dosen.show', $r->dosen_id);
    }
}
