<?php

namespace App\Http\Controllers;

use App\Models\CapaianDosen;
use App\Models\Disc;
use App\Models\Dosen;
use App\Models\Kehadiran;
use App\Models\Kompetensi;
use App\Models\MkInti;
use App\Models\Pejabat;
use App\Models\RiwayatPendidikan;
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
        $prodi = in_array($r->prodi, Dosen::PRODI) ? $r->prodi : null;
        $dosen = Dosen::where('aktif', true)->whereIn('program_studi', $prodi ? [$prodi] : Dosen::PRODI)->orderBy('nama')->get();
        $jumlah = CapaianDosen::whereIn('dosen_id', $dosen->pluck('id'))->selectRaw('dosen_id, jenis, count(*) n')
            ->groupBy('dosen_id', 'jenis')->toBase()->get()->groupBy('dosen_id')->map(fn ($g) => $g->pluck('n', 'jenis'));
        $roadmap = CapaianDosen::whereIn('dosen_id', $dosen->pluck('id'))->where('jenis', 'roadmap')->get()->groupBy('dosen_id');
        return view('dashboard.dosen-rapor-index', compact('prodi', 'dosen', 'jumlah', 'roadmap'));
    }

    public function show(Dosen $dosen)
    {
        abort_unless($this->bolehSemua() || $this->milikSaya($dosen), 403);
        $dosen->load('staff');
        $nip = $dosen->staff?->nip;
        $tahunKompetensi = Kompetensi::where('dosen_id', $dosen->id)->max('tahun');
        $tahunHadir = Kehadiran::where('nip', $nip)->max('tahun');

        return view('dashboard.dosen-rapor', [
            'd' => $dosen,
            'nilai' => app(DashboardDosenController::class)->nilaiDosen($dosen),
            'pendidikan' => RiwayatPendidikan::milik($dosen->staff, $dosen)->get(),
            'disc' => $nip ? Disc::where('nip', $nip)->first() : null,
            'kompetensi' => Kompetensi::where(['dosen_id' => $dosen->id, 'tahun' => $tahunKompetensi])->get()->groupBy('kelompok'),
            'tahunKompetensi' => $tahunKompetensi,
            'mk' => MkInti::whereHas('dosen', fn ($q) => $q->whereKey($dosen->id))->orderBy('kurikulum')->orderBy('urutan')->get()->groupBy(fn ($m) => "{$m->program_studi} · kurikulum {$m->kurikulum}"),
            'hadir' => $tahunHadir ? Kehadiran::where(['nip' => $nip, 'tahun' => $tahunHadir])->get() : collect(),
            'tahunHadir' => $tahunHadir,
            'capaian' => CapaianDosen::where('dosen_id', $dosen->id)->get()->groupBy('jenis'),
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

    private function bolehSemua(): bool
    {
        return $this->isAdmin() || Pejabat::peranDari($this->myStaffIds())->contains('warek1');
    }

    private function milikSaya(Dosen $d): bool
    {
        $akun = auth('pegawai')->user();
        return $akun->dosen_id === $d->id || ($d->staff_id && $this->myStaffIds()->contains($d->staff_id));
    }
}
