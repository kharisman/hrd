<?php

namespace App\Http\Controllers;

use App\Models\Disc;
use App\Models\Dosen;
use App\Models\Kehadiran;
use App\Models\Pejabat;
use App\Models\Staff;
use Illuminate\Http\Request;

// Dashboard SDM (pengganti spreadsheet "Data Keterlambatan", "Data Kehadiran", "Turn over"):
// rekap bulanan keterlambatan & ketidakhadiran per orang (NIP), turn over per tahun, dan input rekap bulanan.
// Kelompok: prodi untuk dosen, "Tenaga Kependidikan" untuk lainnya (seperti spreadsheet).
class DashboardPegawaiController extends Controller
{
    const TENDIK = 'Tenaga Kependidikan';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->isAdmin() || Pejabat::peranDari($this->myStaffIds())->contains('warek2'), 403);
            return $next($request);
        });
    }

    public function __invoke(Request $r)
    {
        $tab = in_array($r->tab, ['ringkasan', 'kehadiran', 'turnover', 'disc', 'input']) ? $r->tab : 'ringkasan';
        $tahun = $r->integer('tahun') ?: (int) (Kehadiran::max('tahun') ?? now()->year);
        $orang = $this->orang(Staff::aktif());
        $kelompok = $r->kelompok && $orang->contains('kelompok', $r->kelompok) ? $r->kelompok : null;

        return view('dashboard.pegawai-sdm', [
            'tab' => $tab,
            'tahun' => $tahun,
            'kelompok' => $kelompok,
            'daftarKelompok' => $orang->pluck('kelompok')->unique()->sort(fn ($a, $b) => ($a === self::TENDIK) <=> ($b === self::TENDIK) ?: strcmp($a, $b))->values(),
            'daftarTahun' => Kehadiran::distinct()->orderByDesc('tahun')->pluck('tahun')->push(now()->year)->unique()->sortDesc()->values(),
        ] + match ($tab) {
            'kehadiran' => $this->kehadiran($orang, $tahun, $kelompok),
            'ringkasan' => app(DashboardController::class)->ringkasan(),
            'turnover' => $this->turnover(),
            'disc' => $this->disc($r),
            'input' => $this->formInput($orang, $tahun, $r->integer('bulan') ?: now()->month),
        });
    }

    // Simpan rekap satu bulan untuk banyak orang sekaligus (kosong = tidak diubah)
    public function simpan(Request $r)
    {
        abort_unless($this->isAdmin(), 403);
        $d = $r->validate([
            'tahun' => 'required|integer|min:2000|max:2100',
            'bulan' => 'required|integer|min:1|max:12',
            'terlambat.*' => 'nullable|integer|min:0|max:100000',
            'absen.*' => 'nullable|numeric|min:0|max:31',
        ], [], ['terlambat.*' => 'menit terlambat', 'absen.*' => 'hari tidak hadir']);
        $n = 0;
        // Kunci isian = id akun staff utama tiap orang
        $terlambat = (array) $r->input('terlambat', []);
        $absen = (array) $r->input('absen', []);
        $sah = Staff::whereIn('id', array_keys($terlambat + $absen))->pluck('id')->flip();
        foreach (array_keys($terlambat + $absen) as $id) {
            $isi = array_filter(['terlambat_menit' => $terlambat[$id] ?? null, 'tidak_hadir_hari' => $absen[$id] ?? null], fn ($v) => $v !== null && $v !== '');
            if ($isi && isset($sah[$id])) {
                Kehadiran::updateOrCreate(['staff_id' => $id, 'tahun' => $d['tahun'], 'bulan' => $d['bulan']], $isi);
                $n++;
            }
        }
        return back()->with('ok', "Rekap " . Kehadiran::BULAN[$d['bulan']] . " {$d['tahun']} disimpan untuk {$n} orang.");
    }

    // Satu baris per orang = akun staff utama (Staff::perOrang); kelompok = prodi dosen aktifnya, atau Tenaga Kependidikan
    private function orang($query)
    {
        $staff = Staff::perOrang($query);
        $nipPerId = Staff::whereIn('nip', $staff->pluck('nip'))->pluck('nip', 'id');
        $prodi = Dosen::whereIn('staff_id', $nipPerId->keys())->whereNotNull('program_studi')
            ->orderBy('aktif')->get()->mapWithKeys(fn ($d) => [$nipPerId[$d->staff_id] => $d->program_studi]); // aktif menimpa nonaktif
        return $staff->map(fn ($s) => (object) ['id' => $s->id, 'nip' => $s->nip, 'nama' => $s->nama, 'divisi' => $s->divisi, 'model' => $s,
            'kelompok' => $prodi[$s->nip] ?? self::TENDIK])->values();
    }

    private function kehadiran($orang, int $tahun, ?string $kelompok): array
    {
        $data = Kehadiran::where('tahun', $tahun)->get()->groupBy('staff_id');
        $baris = $orang->when($kelompok, fn ($c) => $c->where('kelompok', $kelompok))->map(function ($o) use ($data) {
            $bulan = ($data[$o->id] ?? collect())->keyBy('bulan');
            $o->terlambat = collect(Kehadiran::BULAN)->map(fn ($b, $i) => $bulan[$i]->terlambat_menit ?? null);
            $o->absen = collect(Kehadiran::BULAN)->map(fn ($b, $i) => $bulan[$i]->tidak_hadir_hari ?? null);
            $o->totalTerlambat = $o->terlambat->sum();
            $o->totalAbsen = $o->absen->sum();
            $o->rataTerlambat = $o->terlambat->filter(fn ($v) => $v !== null)->avg();
            return $o;
        })->values();

        // Rata-rata per kelompok per bulan (yang ada datanya saja)
        $rata = fn ($kolom) => $orang->groupBy('kelompok')->map(fn ($g) => collect(Kehadiran::BULAN)->map(function ($b, $i) use ($g, $data, $kolom) {
            $v = $g->map(fn ($o) => ($data[$o->id] ?? collect())->firstWhere('bulan', $i)?->$kolom)->filter(fn ($v) => $v !== null);
            return $v->isEmpty() ? null : round($v->avg(), 1);
        }));

        return [
            'baris' => $baris,
            'rataTerlambat' => $rata('terlambat_menit'),
            'rataAbsen' => $rata('tidak_hadir_hari'),
            'terlambatTeratas' => $baris->sortByDesc('totalTerlambat')->filter(fn ($o) => $o->totalTerlambat > 0)->take(10),
            'tanpaData' => $baris->filter(fn ($o) => $o->terlambat->filter(fn ($v) => $v !== null)->isEmpty())->count(),
        ];
    }

    /**
     * Turn over per tahun & kelompok = keluar di tahun itu / jumlah orang per 1 Januari.
     * ponytail: hanya orang yang tanggal keluarnya tercatat; pegawai nonaktif tanpa tanggal keluar tidak terhitung
     */
    private function turnover(): array
    {
        // Nonaktif tanpa tanggal keluar tidak diketahui kapan keluarnya → tidak dihitung sama sekali (pembilang maupun penyebut)
        $semua = $this->orang(Staff::query())->filter(fn ($o) => Staff::where('nip', $o->nip)->where(fn ($q) => $q->where('aktif', true)->orWhereNotNull('tanggal_keluar'))->exists());
        $tahunList = range(max(now()->year - 5, (int) ($semua->min(fn ($o) => $o->model->tanggal_keluar?->year) ?? now()->year)), now()->year);
        $tabel = $semua->groupBy('kelompok')->map(fn ($g) => collect($tahunList)->mapWithKeys(function ($t) use ($g) {
            $awal = $g->filter(fn ($o) => (!$o->model->mulai_kerja || $o->model->mulai_kerja->year < $t) && (!$o->model->tanggal_keluar || $o->model->tanggal_keluar->year >= $t))->count();
            $keluar = $g->filter(fn ($o) => $o->model->tanggal_keluar?->year === $t);
            return [$t => ['awal' => $awal, 'keluar' => $keluar->count(), 'nama' => $keluar->pluck('nama'), 'rasio' => $awal ? $keluar->count() / $awal : null]];
        }));
        return [
            'tahunList' => $tahunList,
            'turnover' => $tabel,
            'tanpaTanggal' => Staff::where('aktif', false)->whereNull('tanggal_keluar')->whereNotNull('nip')->distinct()->count('nip'),
        ];
    }

    // Peta karakter: satu baris per orang yang sudah dipetakan, kelompok = tim pemetaan
    private function disc(Request $r): array
    {
        $semua = Disc::with('staff')->orderBy('tim')->get();
        $tim = $semua->pluck('tim')->filter()->unique()->values();
        $pilih = $tim->contains($r->tim) ? $r->tim : null;
        $baris = $semua->when($pilih, fn ($c) => $c->where('tim', $pilih))->map(fn ($d) => (object) [
            'disc' => $d, 'staff' => $d->staff,
            'katKomitmen' => Disc::kategori($d->komitmen), 'katKompetensi' => Disc::kategori($d->kompetensi),
        ])->sortBy(fn ($b) => $b->staff->nama ?? '')->values();

        return [
            'daftarTim' => $tim, 'tim' => $pilih, 'baris' => $baris,
            // Matriks komitmen x kompetensi (kepemimpinan situasional)
            'matriks' => collect(Disc::GAYA)->keys()->mapWithKeys(fn ($km) => [$km => collect(Disc::GAYA)->keys()
                ->mapWithKeys(fn ($kp) => [$kp => $baris->where('katKomitmen', $km)->where('katKompetensi', $kp)->count()])]),
            // Gaya dominan = huruf pertama profil (D/I/S/C)
            'dominan' => $baris->countBy(fn ($b) => strtoupper(substr(trim((string) $b->disc->profil), 0, 1)) ?: '?')->sortKeys(),
            'edit' => $this->isAdmin() && $r->staff ? Disc::with('staff')->firstOrNew(['staff_id' => $r->integer('staff')]) : null,
            'pilihanStaff' => $this->isAdmin() ? Staff::perOrang(Staff::aktif()) : collect(),
        ];
    }

    public function simpanDisc(Request $r)
    {
        abort_unless($this->isAdmin(), 403);
        $skor = array_keys(Disc::KOMITMEN + Disc::KOMPETENSI);
        $d = $r->validate(['staff_id' => 'required|exists:staffs,id', 'tim' => 'nullable|string|max:255', 'profil' => 'nullable|string|max:20', 'dinilai_at' => 'nullable|date']
            + array_fill_keys($skor, 'nullable|integer|min:1|max:4'));
        // Selalu di akun utama orang itu
        $d['staff_id'] = Staff::utama(Staff::whereKey($d['staff_id'])->value('nip'))?->id ?? $d['staff_id'];
        Disc::updateOrCreate(['staff_id' => $d['staff_id']], $d);
        return redirect()->route('dashboard.pegawai', ['tab' => 'disc', 'tim' => $d['tim']])->with('ok', 'Peta karakter disimpan.');
    }

    private function formInput($orang, int $tahun, int $bulan): array
    {
        return ['bulan' => $bulan, 'baris' => $orang, 'isian' => Kehadiran::where(['tahun' => $tahun, 'bulan' => $bulan])->get()->keyBy('staff_id')];
    }
}
