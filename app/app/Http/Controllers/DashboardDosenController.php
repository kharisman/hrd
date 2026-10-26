<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Pejabat;
use App\Models\RasioProdi;
use Illuminate\Http\Request;

// Dashboard kualifikasi dosen per prodi (pengganti spreadsheet "Dashboard Dosen": Jafung & Serdos, Pendidikan, Rasio, Summary).
// Acuan "Note standar kinerja dosen": Th1 Non JFA, Th2–3 AA, Th4–8 Lektor, Th9+ Lektor Kepala; naik jenjang minimal 2 th sejak TMT jafung.
class DashboardDosenController extends Controller
{
    const JAFUNG = ['Non JFA', 'Asisten Ahli', 'Lektor', 'Lektor Kepala', 'Profesor'];
    const SINGKAT = ['Non JFA', 'AA', 'L', 'LK', 'GB'];
    // Syarat akreditasi unggul (sheet Resume BD)
    const TARGET_S3 = 30;
    const TARGET_LK = 40;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->bolehLihat(), 403);
            return $next($request);
        });
    }

    public function __invoke(Request $r)
    {
        $prodi = in_array($r->prodi, Dosen::PRODI) ? $r->prodi : null;
        $semua = Dosen::where('aktif', true)->whereIn('program_studi', Dosen::PRODI)->orderBy('nama')->get()->map(fn ($d) => $this->nilai($d));
        $dosen = $prodi ? $semua->where('prodi', $prodi)->values() : $semua;

        return view('dashboard.dosen', [
            'prodi' => $prodi,
            'dosen' => $dosen,
            'ringkas' => $this->ringkas($dosen),
            'perProdi' => collect(Dosen::PRODI)->mapWithKeys(fn ($p) => [$p => $this->ringkas($semua->where('prodi', $p))]),
            'rasio' => RasioProdi::orderBy('tahun_ajaran')->get()->groupBy('tahun_ajaran'),
            'jumlahPerProdi' => $semua->countBy('prodi'),
            'bolehUbah' => $this->isAdmin(),
        ]);
    }

    // Admin mengisi jumlah mahasiswa per prodi per TA
    public function simpanRasio(Request $r)
    {
        abort_unless($this->isAdmin(), 403);
        $d = $r->validate([
            'tahun_ajaran' => ['required', 'regex:#^\d{4}/\d{4}$#'],
            'mahasiswa' => 'required|array',
            'mahasiswa.*' => 'nullable|integer|min:0',
            'target' => 'required|integer|min:1|max:99',
        ], ['tahun_ajaran.regex' => 'Format tahun ajaran: 2026/2027.']);
        foreach ($d['mahasiswa'] as $p => $n) {
            if (in_array($p, Dosen::PRODI) && $n !== null) {
                RasioProdi::updateOrCreate(['program_studi' => $p, 'tahun_ajaran' => $d['tahun_ajaran']], ['jumlah_mahasiswa' => $n, 'target' => $d['target']]);
            }
        }
        return back()->with('ok', "Jumlah mahasiswa TA {$d['tahun_ajaran']} disimpan.");
    }

    // Admin HRD atau Warek I (akademik)
    private function bolehLihat(): bool
    {
        return $this->isAdmin() || Pejabat::peranDari($this->myStaffIds())->contains('warek1');
    }

    // Status jafung & serdos satu dosen
    private function nilai(Dosen $d): object
    {
        $jenjang = max(0, (int) array_search($d->jabatan_fungsional, self::JAFUNG));
        $mulai = $d->tmt_dosen ?? $d->mulai_kerja;
        $masa = $mulai ? $mulai->diffInYears(now()) : null;
        $seharusnya = $masa === null ? null : match (true) { $masa < 1 => 0, $masa < 3 => 1, $masa < 8 => 2, default => 3 };
        $berikut = min($jenjang + 1, 4);
        $layakNaik = $d->tmt_jabfung?->copy()->addYears(2);

        return (object) [
            'model' => $d,
            'prodi' => $d->program_studi,
            'jenjang' => $jenjang,
            'jafung' => self::SINGKAT[$jenjang] . ($d->angka_kredit ? " {$d->angka_kredit}" : ''),
            'masa' => $masa,
            'seharusnya' => $seharusnya,
            'tepat' => $seharusnya === null ? null : $jenjang >= $seharusnya,
            'berikut' => self::SINGKAT[$berikut],
            'layakNaik' => $layakNaik,
            'bisaNaik' => $layakNaik && $layakNaik->isPast(),
            'serdos' => $d->serdos,
            // Eligible serdos: minimal Asisten Ahli & 2 tahun sebagai dosen
            'eligibleSerdos' => !$d->serdos && $jenjang >= 1 && $masa >= 2,
            's3' => $d->pendidikan_terakhir === 'S3',
        ];
    }

    private function ringkas($dosen): array
    {
        $n = max(1, $dosen->count());
        return [
            'jumlah' => $dosen->count(),
            'jafung' => collect(self::SINGKAT)->map(fn ($s, $i) => $dosen->where('jenjang', $i)->count())->all(),
            'tepat' => $dosen->where('tepat', true)->count(),
            'tidakTepat' => $dosen->where('tepat', false)->count(),
            'serdos' => $dosen->where('serdos', true)->count(),
            'eligible' => $dosen->where('eligibleSerdos', true)->count(),
            'nonEligible' => $dosen->where('serdos', false)->where('eligibleSerdos', false)->count(),
            's2' => $dosen->where('s3', false)->count(),
            's3' => $dosen->where('s3', true)->count(),
            'pctSerdos' => round($dosen->where('serdos', true)->count() / $n * 100),
            'pctS3' => round($dosen->where('s3', true)->count() / $n * 100),
            'pctLK' => round($dosen->where('jenjang', '>=', 3)->count() / $n * 100),
        ];
    }
}
