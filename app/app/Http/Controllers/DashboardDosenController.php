<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use App\Models\Dosen;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\Kompetensi;
use App\Models\Mengajar;
use App\Services\SinkronAkademik;
use App\Models\MkInti;
use App\Models\Pejabat;
use App\Models\RasioProdi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $prodi = in_array($r->prodi, Dosen::daftarProdi()) ? $r->prodi : null;
        $semua = Dosen::where('aktif', true)->whereIn('program_studi', Dosen::daftarProdi())->orderBy('nama')->get()->map(fn ($d) => $this->nilaiDosen($d));
        $dosen = $prodi ? $semua->where('prodi', $prodi)->values() : $semua;

        return view('dashboard.dosen', [
            'prodi' => $prodi,
            'dosen' => $dosen,
            'ringkas' => $this->ringkas($dosen),
            'perProdi' => collect(Dosen::daftarProdi())->mapWithKeys(fn ($p) => [$p => $this->ringkas($semua->where('prodi', $p))]),
            'rasio' => RasioProdi::orderBy('tahun_ajaran')->get()->groupBy('tahun_ajaran'),
            // Label angkatan per prodi: kode angkatan → tahun ajaran masuk
            'labelAngkatan' => Prodi::where('jenjang', 'Strata Satu')->get()->mapWithKeys(fn ($p) => [$p->nama => Angkatan::where('prodi_api_id', $p->api_id)->pluck('periode', 'kode')]),
            'jumlahPerProdi' => $semua->countBy('prodi'),
        ]);
    }

    // ---- Penguasaan mata kuliah inti (matriks MK x dosen) ----

    public function mk(Request $r)
    {
        $prodi = in_array($r->prodi, Dosen::daftarProdi()) ? $r->prodi : Dosen::daftarProdi()[0];
        $daftarKurikulum = $this->kurikulum($prodi);
        $kurikulum = $daftarKurikulum->contains($r->kurikulum) ? $r->kurikulum : $daftarKurikulum->first();
        $mk = MkInti::with('dosen:id')->where(['program_studi' => $prodi, 'kurikulum' => $kurikulum])->orderBy('urutan')->orderBy('id')->get();

        // Kolom dosen: dosen aktif prodi + siapa pun yang tercentang di matriks ini
        $dosen = Dosen::where(fn ($q) => $q->where(['aktif' => true, 'program_studi' => $prodi])
            ->orWhereIn('id', $mk->flatMap->dosen->pluck('id')))->orderBy('nama')->get();

        $this->hitungKuasai($mk);
        $dosen = $dosen->merge(Dosen::whereIn('id', $mk->flatMap->kuasai)->whereNotIn('id', $dosen->pluck('id'))->get())->sortBy('nama')->values();
        $pernah = fn ($m, $d) => in_array($d->id, $m->kuasai) && !$m->dosen->contains('id', $d->id);

        // Mode ubah: daftar MK kurikulum API untuk menautkan manual MK yang namanya berbeda
        $materiApi = $r->boolean('ubah') && $this->isAdmin() && ($idKur = $mk->pluck('id_kurikulum_api')->filter()->first())
            ? rescue(fn () => collect(app(\App\Services\PctApi::class)->detail("/kurikulum/$idKur/detail")['semester'] ?? [])
                ->flatMap(fn ($s) => $s['materi'])->sortBy('namaMateri')->mapWithKeys(fn ($m) => [$m['kodeMateri'] => "{$m['namaMateri']} ({$m['kodeMateri']}, {$m['sks']} SKS)"]), collect(), false)
            : collect();

        // Saran tautan untuk MK yang belum tertaut: nama MK eUniversity paling mirip (≥ 55%), dipilih admin sebelum disimpan
        $saran = $mk->whereNull('kode_mk')->mapWithKeys(function ($m) use ($materiApi) {
            $skor = $materiApi->map(function ($label, $kode) use ($m) {
                similar_text(strtolower(preg_replace('/\(.*?\)/', '', $m->nama)), strtolower(preg_replace('/ \(.*$/', '', $label)), $p);
                return $p;
            })->sortDesc();
            return [$m->id => $skor->first() >= 55 ? $skor->keys()->first() : null];
        })->filter();

        return view('dashboard.dosen-mk', [
            'pernah' => $pernah, 'materiApi' => $materiApi, 'saran' => $saran,
            'dipakaiAngkatan' => ($idKur = $mk->pluck('id_kurikulum_api')->filter()->first())
                ? Angkatan::where(['id_kurikulum_api' => $idKur, 'aktif' => true])->orderBy('periode')->pluck('periode') : collect(),
            'prodi' => $prodi, 'kurikulum' => $kurikulum, 'daftarKurikulum' => $daftarKurikulum,
            'mk' => $mk, 'dosen' => $dosen, 'singkat' => $this->namaSingkat($dosen),
            'ubah' => $r->boolean('ubah') && $this->isAdmin(), 'bolehUbah' => $this->isAdmin(),
            // Kekurangan dosen semua prodi pada kurikulum terbaru masing-masing (seperti sheet SUMMARY)
            'kurangSemua' => collect(Dosen::daftarProdi())->mapWithKeys(fn ($p) => [$p => MkInti::with('dosen:id')
                ->where(['program_studi' => $p, 'kurikulum' => $this->kurikulum($p)->first()])->orderBy('urutan')->get()
                ->tap(fn ($c) => $this->hitungKuasai($c))->filter(fn ($m) => $m->gap < 0)]),
        ]);
    }

    // Simpan matriks centang (mode ubah) satu prodi + kurikulum
    public function simpanMk(Request $r)
    {
        abort_unless($this->isAdmin(), 403);
        $d = $r->validate(['prodi' => ['required', Rule::in(Dosen::daftarProdi())], 'kurikulum' => 'required|string|max:20',
            'kuasai' => 'array', 'kebutuhan' => 'array', 'kebutuhan.*' => 'integer|min:0|max:20', 'solusi' => 'array', 'solusi.*' => 'nullable|string|max:255', 'kode' => 'array', 'kode.*' => 'nullable|string|max:20']);
        foreach (MkInti::where(['program_studi' => $d['prodi'], 'kurikulum' => $d['kurikulum']])->get() as $m) {
            $m->dosen()->sync(array_map('intval', array_keys($d['kuasai'][$m->id] ?? [])));
            $m->update(['kebutuhan' => $d['kebutuhan'][$m->id] ?? $m->kebutuhan, 'solusi' => $d['solusi'][$m->id] ?? null]
                + (array_key_exists($m->id, $d['kode'] ?? []) ? ['kode_mk' => $d['kode'][$m->id] ?: null] : []));
        }
        return redirect()->route('dashboard.dosen.mk', ['prodi' => $d['prodi'], 'kurikulum' => $d['kurikulum']])->with('ok', 'Penguasaan mata kuliah disimpan.');
    }

    public function tambahMk(Request $r)
    {
        abort_unless($this->isAdmin(), 403);
        $d = $r->validate([
            'program_studi' => ['required', Rule::in(Dosen::daftarProdi())], 'kurikulum' => 'required|string|max:20',
            'kode_mk' => 'nullable|string|max:20',
            'nama' => ['required_without:kode_mk', 'nullable', 'string', 'max:255', Rule::unique('mk_inti')->where(fn ($q) => $q->where(['program_studi' => $r->program_studi, 'kurikulum' => $r->kurikulum]))],
            'bidang_ilmu' => 'nullable|string|max:255', 'semester' => 'nullable|in:Ganjil,Genap', 'irisan' => 'nullable|string|max:255',
            'kebutuhan' => 'required|integer|min:0|max:20',
        ], ['nama.unique' => 'Mata kuliah ini sudah ada di kurikulum tersebut.', 'nama.required_without' => 'Pilih MK dari kurikulum eUniversity atau isi nama.']);
        // Dipilih dari kurikulum eUniversity: nama, SKS, semester, kode ikut API
        if ($d['kode_mk'] ?? null) {
            $idKur = MkInti::where(['program_studi' => $d['program_studi'], 'kurikulum' => $d['kurikulum']])->value('id_kurikulum_api');
            $m = $idKur ? collect(app(\App\Services\PctApi::class)->detail("/kurikulum/$idKur/detail")['semester'] ?? [])
                ->flatMap(fn ($s) => collect($s['materi'])->map(fn ($x) => $x + ['smt' => $s['semester']]))->firstWhere('kodeMateri', $d['kode_mk']) : null;
            abort_unless($m, 422, 'MK tidak ditemukan di kurikulum eUniversity.');
            if (MkInti::where(['program_studi' => $d['program_studi'], 'kurikulum' => $d['kurikulum'], 'kode_mk' => $m['kodeMateri']])->exists()) {
                return back()->withErrors('Mata kuliah ini sudah ada di matriks.');
            }
            $d = ['nama' => trim($m['namaMateri']), 'sks' => $m['sks'], 'semester' => $m['smt'] % 2 ? 'Ganjil' : 'Genap', 'id_kurikulum_api' => $idKur] + $d;
        }
        MkInti::create($d + ['urutan' => (int) MkInti::where(['program_studi' => $d['program_studi'], 'kurikulum' => $d['kurikulum']])->max('urutan') + 1]);
        return redirect()->route('dashboard.dosen.mk', ['prodi' => $d['program_studi'], 'kurikulum' => $d['kurikulum'], 'ubah' => 1])->with('ok', "{$d['nama']} ditambahkan.");
    }

    public function hapusMk(MkInti $mk)
    {
        abort_unless($this->isAdmin(), 403);
        $mk->delete();
        return back()->with('ok', "{$mk->nama} dihapus dari kurikulum {$mk->kurikulum}.");
    }

    // ---- Kompetensi dosen (skill personal & jabatan, sekarang vs target) ----

    public function kompetensi(Request $r)
    {
        $prodi = in_array($r->prodi, Dosen::daftarProdi()) ? $r->prodi : null;
        $daftarTahun = Kompetensi::distinct()->orderByDesc('tahun')->pluck('tahun')->push(now()->year)->unique()->sortDesc()->values();
        $tahun = $daftarTahun->contains($r->integer('tahun')) ? $r->integer('tahun') : $daftarTahun->first();
        $dosen = Dosen::where('aktif', true)->whereIn('program_studi', $prodi ? [$prodi] : Dosen::daftarProdi())->orderBy('nama')->get();
        $skill = Kompetensi::whereIn('dosen_id', $dosen->pluck('id'))->where('tahun', $tahun)->get()->groupBy('dosen_id');
        $lalu = Kompetensi::whereIn('dosen_id', $dosen->pluck('id'))->where('tahun', $tahun - 1)->get()->groupBy('dosen_id');
        $rata = function ($c, $k, $kol = 'sekarang') {
            $v = collect($c)->where('kelompok', $k)->whereNotNull($kol);
            return $v->isEmpty() ? null : round($v->avg($kol), 2);
        };

        $baris = $dosen->map(fn ($d) => (object) [
            'model' => $d,
            'skill' => $skill[$d->id] ?? collect(),
            'personal' => $rata($skill[$d->id] ?? null, 'personal'),
            'jabatan' => $rata($skill[$d->id] ?? null, 'jabatan'),
            'targetPersonal' => $rata($skill[$d->id] ?? null, 'personal', 'target'),
            'targetJabatan' => $rata($skill[$d->id] ?? null, 'jabatan', 'target'),
            'personalLalu' => $rata($lalu[$d->id] ?? null, 'personal'),
            'jabatanLalu' => $rata($lalu[$d->id] ?? null, 'jabatan'),
        ]);

        return view('dashboard.dosen-kompetensi', [
            'prodi' => $prodi, 'tahun' => $tahun, 'daftarTahun' => $daftarTahun, 'baris' => $baris,
            'edit' => $this->isAdmin() && $r->dosen ? $baris->first(fn ($b) => $b->model->id === $r->integer('dosen')) : null,
            // Rata-rata per skill: kelemahan bersama yang perlu pelatihan
            'perSkill' => $skill->flatten()->groupBy(fn ($k) => $k->kelompok . '|' . $k->skill)
                ->map(fn ($g) => ['sekarang' => round($g->avg('sekarang'), 1), 'target' => round($g->avg('target'), 1), 'n' => $g->count()]),
            'bolehUbah' => $this->isAdmin(),
        ]);
    }

    public function simpanKompetensi(Request $r, Dosen $dosen)
    {
        abort_unless($this->isAdmin(), 403);
        $d = $r->validate([
            'tahun' => 'required|integer|min:2000|max:2100',
            'skill' => 'array', 'skill.*.kelompok' => ['required', Rule::in(array_keys(Kompetensi::KELOMPOK))],
            'skill.*.nama' => 'nullable|string|max:255', 'skill.*.sekarang' => 'nullable|integer|min:1|max:5', 'skill.*.target' => 'nullable|integer|min:1|max:5',
            'skill.*.rencana' => 'nullable|string|max:255', 'skill.*.catatan' => 'nullable|string|max:255',
        ], [], ['skill.*.sekarang' => 'level sekarang', 'skill.*.target' => 'target level']);
        // Isi ulang seluruh skill tahun itu (baris yang namanya dikosongkan = dihapus)
        Kompetensi::where(['dosen_id' => $dosen->id, 'tahun' => $d['tahun']])->delete();
        foreach (collect($d['skill'] ?? [])->filter(fn ($s) => trim((string) ($s['nama'] ?? '')) !== '') as $s) {
            Kompetensi::updateOrCreate(['dosen_id' => $dosen->id, 'tahun' => $d['tahun'], 'kelompok' => $s['kelompok'], 'skill' => trim($s['nama'])],
                collect($s)->only(['sekarang', 'target', 'rencana', 'catatan'])->all());
        }
        return redirect()->route('dashboard.dosen.kompetensi', ['prodi' => $dosen->program_studi, 'tahun' => $d['tahun']])->with('ok', "Kompetensi {$dosen->nama} {$d['tahun']} disimpan.");
    }

    /**
     * Data eUniversity diutamakan: dosen aktif yang pernah mengampu MK (6 periode terakhir) dihitung menguasai,
     * ditambah centang manual HRD. Cocok lewat kode MK, atau nama yang dinormalkan.
     */
    private function hitungKuasai($mk): void
    {
        $dosenApi = Dosen::where('aktif', true)->whereNotNull('api_id')->pluck('id', 'api_id');
        $ajar = Mengajar::whereIn('dosen_api_id', $dosenApi->keys())->get(['dosen_api_id', 'kode_mk', 'nama_mk']);
        $perKode = $ajar->groupBy('kode_mk')->map->pluck('dosen_api_id');
        $perNama = $ajar->groupBy(fn ($a) => SinkronAkademik::kunci($a->nama_mk))->map->pluck('dosen_api_id');
        foreach ($mk as $m) {
            $api = collect($m->kode_mk ? $perKode[$m->kode_mk] ?? [] : [])->merge($perNama[SinkronAkademik::kunci($m->nama)] ?? []);
            $m->kuasai = $m->dosen->pluck('id')->merge($api->map(fn ($id) => $dosenApi[$id]))->unique()->values()->all();
        }
    }

    // Kurikulum prodi, terbaru dulu: tahun (2026, 2021, ...) sebelum label lain seperti "Lama"
    private function kurikulum(string $prodi)
    {
        // Urut menurut angkatan termuda yang memakainya (eUniversity), lalu tahun di kode kurikulum (OBE-SI-2026)
        $grup = MkInti::where('program_studi', $prodi)->select('kurikulum', 'id_kurikulum_api')->distinct()->get();
        $termuda = Angkatan::whereIn('id_kurikulum_api', $grup->pluck('id_kurikulum_api')->filter())->groupBy('id_kurikulum_api')
            ->selectRaw('id_kurikulum_api, max(periode) p')->pluck('p', 'id_kurikulum_api');
        return $grup->sortByDesc(fn ($g) => ($termuda[$g->id_kurikulum_api] ?? '0000') . (preg_match('/(\d{4})$/', $g->kurikulum, $t) ? $t[1] : '0000'))
            ->pluck('kurikulum')->unique()->values();
    }

    // "Dini", "Eka H", "Eka P": nama depan, ditambah inisial bila ada nama depan kembar
    private function namaSingkat($dosen)
    {
        $kata = fn ($d) => array_values(array_filter(explode(' ', preg_replace('/^((dr|prof|ir|muhammad|m)\.?\s+)+/i', '', trim(explode(',', $d->nama)[0])))));
        $depan = $dosen->mapWithKeys(fn ($d) => [$d->id => $kata($d)[0] ?? $d->nama]);
        return $dosen->mapWithKeys(fn ($d) => [$d->id => $depan->filter(fn ($v) => $v === $depan[$d->id])->count() > 1
            ? $depan[$d->id] . ' ' . mb_substr($kata($d)[1] ?? '', 0, 1) : $depan[$d->id]]);
    }

    // Admin HRD atau Warek I (akademik)
    private function bolehLihat(): bool
    {
        return $this->isAdmin() || Pejabat::peranDari($this->myStaffIds())->contains('warek1');
    }

    // Status jafung & serdos satu dosen
    public function nilaiDosen(Dosen $d): object
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
