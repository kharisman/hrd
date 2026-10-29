<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use App\Models\Bimbingan;
use App\Models\CapaianDosen;
use App\Models\Disc;
use App\Models\Dosen;
use App\Models\Kehadiran;
use App\Models\Kompetensi;
use App\Models\Mengajar;
use App\Models\MkInti;
use App\Models\Pejabat;
use App\Models\Prodi;
use App\Models\RiwayatPendidikan;
use App\Models\Staff;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Rapor dosen (pengganti tab per dosen & "Rapot" di spreadsheet): profil, jafung, serdos, pendidikan, DISC,
// kompetensi, penguasaan MK, kehadiran, roadmap karier, sertifikat, prestasi, publikasi, rekognisi.
// Dilihat admin HRD, Warek I, atau dosen itu sendiri; diubah admin.
class RaporDosenController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($this->bolehSemua(), 403);
        $prodi = Akses::bolehProdi($r->prodi) ? $r->prodi : (Akses::semuaProdi() ? null : Akses::prodiDosen()[0]);
        $dosen = Dosen::where('aktif', true)->whereIn('program_studi', $prodi ? [$prodi] : Akses::prodiDosen())->orderBy('nama')->get();
        $jumlah = CapaianDosen::whereIn('dosen_id', $dosen->pluck('id'))->selectRaw('dosen_id, jenis, count(*) n')
            ->groupBy('dosen_id', 'jenis')->toBase()->get()->groupBy('dosen_id')->map(fn ($g) => $g->pluck('n', 'jenis'));
        $roadmap = CapaianDosen::whereIn('dosen_id', $dosen->pluck('id'))->where('jenis', 'roadmap')->get()->groupBy('dosen_id');
        return view('dashboard.dosen-rapor-index', compact('prodi', 'dosen', 'jumlah', 'roadmap'));
    }

    public function show(Dosen $dosen)
    {
        abort_unless(Akses::bolehProdi($dosen->program_studi) || $this->milikSaya($dosen), 403);
        $dosen->load('staff');
        $staffId = Staff::utama($dosen->staff?->nip)?->id;
        $tahunKompetensi = Kompetensi::where('dosen_id', $dosen->id)->max('tahun');
        $tahunHadir = Kehadiran::where('staff_id', $staffId)->max('tahun');

        return view('dashboard.dosen-rapor', [
            'd' => $dosen,
            'nilai' => app(DashboardDosenController::class)->nilaiDosen($dosen),
            'pendidikan' => RiwayatPendidikan::milik($dosen->staff, $dosen)->get(),
            'disc' => $staffId ? Disc::where('staff_id', $staffId)->first() : null,
            'kompetensi' => Kompetensi::where(['dosen_id' => $dosen->id, 'tahun' => $tahunKompetensi])->get()->groupBy('kelompok'),
            'tahunKompetensi' => $tahunKompetensi,
            'mk' => MkInti::whereHas('dosen', fn ($q) => $q->whereKey($dosen->id))->orderBy('kurikulum')->orderBy('urutan')->get()->groupBy(fn ($m) => "{$m->program_studi} · kurikulum {$m->kurikulum}"),
            'hadir' => $tahunHadir ? Kehadiran::where(['staff_id' => $staffId, 'tahun' => $tahunHadir])->get() : collect(),
            'tahunHadir' => $tahunHadir,
            'capaian' => CapaianDosen::where('dosen_id', $dosen->id)->get()->groupBy('jenis'),
            // Kelas yang diampu per periode (eUniversity), terbaru dulu
            'bimbingan' => Bimbingan::where('dosen_id', $dosen->id)->orderByDesc('id_krs')->get()->groupBy('periode'),
            'pa' => $this->rincianPa($dosen),
            'diampu' => $dosen->api_id ? Mengajar::where('dosen_api_id', $dosen->api_id)->orderByDesc('id_krs')->orderBy('nama_mk')->get()->groupBy('periode') : collect(),
            'bolehUbah' => $this->isAdmin(),
        ]);
    }

    public function simpan(Request $r, Dosen $dosen)
    {
        abort_unless($this->isAdmin(), 403);
        $d = $r->validate([
            'jenis' => ['required', Rule::in(array_keys(CapaianDosen::JENIS))],
            'judul' => 'required|string|max:500', 'tahun' => 'nullable|string|max:40', 'bulan' => 'nullable|integer|min:1|max:12',
            'sumber' => 'nullable|string|max:255', 'status' => 'nullable|string|max:255', 'keterangan' => 'nullable|string|max:500', 'link' => 'nullable|url|max:500',
        ]);
        $dosen->hasMany(CapaianDosen::class)->create($d);
        return redirect()->to(route('dashboard.dosen.rapor', $dosen) . '#' . $d['jenis'])->with('ok', CapaianDosen::JENIS[$d['jenis']] . ' ditambahkan.');
    }

    // Ubah status roadmap (Tercapai / Tidak Tercapai) tanpa form panjang
    public function status(Request $r, CapaianDosen $capaian)
    {
        abort_unless($this->isAdmin(), 403);
        $capaian->update($r->validate(['status' => 'nullable|in:Tercapai,Tidak Tercapai']));
        return redirect()->to(route('dashboard.dosen.rapor', $capaian->dosen_id) . '#' . $capaian->jenis);
    }

    public function hapus(CapaianDosen $capaian)
    {
        abort_unless($this->isAdmin(), 403);
        $capaian->delete();
        return redirect()->to(route('dashboard.dosen.rapor', $capaian->dosen_id) . '#' . $capaian->jenis)->with('ok', 'Dihapus.');
    }

    /**
     * Mahasiswa bimbingan akademik (PA) dari eUniversity, hanya jumlah per status & angkatan (tanpa nama).
     * Diambil langsung saat rapor dibuka; gagal jaringan → null (rapor tetap tampil).
     */
    private function rincianPa(Dosen $d): ?array
    {
        if (!$d->api_id) {
            return null;
        }
        return rescue(function () use ($d) {
            $api = app(\App\Services\PctApi::class);
            $mhs = collect(session('pct_token') ? $api->detail("/dosen-pa/{$d->api_id}")['mahasiswa'] ?? [] : []);
            $prodi = Prodi::pluck('api_id', 'nama');
            $label = fn ($m) => Angkatan::where(['prodi_api_id' => $prodi[$m['programStudi']] ?? 0, 'kode' => $m['Angkatan']])->value('periode') ?? 'angkatan ' . $m['Angkatan'];
            return [
                'status' => $mhs->countBy('StatusMhs')->sortDesc()->all(),
                'angkatan' => $mhs->groupBy(fn ($m) => $m['programStudi'] . ' ' . $label($m))->map->count()->sortKeysDesc()->all(),
            ];
        }, null, false);
    }

    // Daftar rapor: admin, pimpinan, kaprodi (prodinya saja)
    private function bolehSemua(): bool
    {
        return Akses::dashboardDosen();
    }

    private function milikSaya(Dosen $d): bool
    {
        $akun = auth('pegawai')->user();
        return $akun->dosen_id === $d->id || ($d->staff_id && $this->myStaffIds()->contains($d->staff_id));
    }
}
