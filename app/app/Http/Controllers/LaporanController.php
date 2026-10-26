<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\JenisCuti;
use App\Models\Pengajuan;
use App\Models\RiwayatPendidikan;
use App\Models\Staff;
use App\Support\Menu;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Semua laporan memakai pola sama: filter → kolom + baris → tampil tabel, atau ?export=1 → unduh Excel.
class LaporanController extends Controller
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
        return view('menu-induk', ['judul' => 'Laporan', 'item' => Menu::laporan()]);
    }

    // Rekap cuti per pegawai per tahun: hari terpakai per jenis (status Disetujui)
    public function cuti(Request $r)
    {
        $tahun = $r->integer('tahun') ?: now()->year;
        $jenis = JenisCuti::orderBy('id')->get();
        $status = $r->input('status', 'Disetujui');
        $hari = Pengajuan::cuti()->whereIn('status', $status === 'Menunggu' ? Pengajuan::MENUNGGU : [$status])->whereYear('tgl_mulai', $tahun)
            ->selectRaw('staff_id, jenis_cuti_id, SUM(DATEDIFF(tgl_selesai, tgl_mulai) + 1) hari, COUNT(*) n')
            ->groupBy('staff_id', 'jenis_cuti_id')->toBase()->get()->groupBy('staff_id');

        $baris = Staff::whereIn('id', $hari->keys())
            ->when($r->divisi, fn ($q, $v) => $q->where('id_divisi', $v))
            ->orderBy('nama')->get()
            ->map(function ($s) use ($hari, $jenis) {
                $per = $hari[$s->id]->keyBy('jenis_cuti_id');
                return array_merge(
                    [$s->nama, $s->nip, $s->divisi],
                    $jenis->map(fn ($j) => (int) ($per[$j->id]->hari ?? 0))->all(),
                    [(int) $per->sum('hari'), (int) $per->sum('n')]
                );
            });

        return $this->tampil($r, 'Rekap Cuti ' . $tahun,
            array_merge(['Nama', 'NIP', 'Divisi'], $jenis->map(fn ($j) => $j->nama . ($j->kuota ? " (/{$j->kuota})" : ''))->all(), ['Total Hari', 'Pengajuan']),
            $baris, [
                ['tahun', 'Tahun', array_combine($t = range(now()->year + 1, now()->year - 4), $t), $tahun],
                ['status', 'Status', ['Disetujui' => 'Disetujui', 'Menunggu' => 'Menunggu', 'Ditolak' => 'Ditolak'], $r->input('status', 'Disetujui')],
                ['divisi', 'Divisi', ['' => 'Semua divisi'] + $this->divisi(), $r->divisi],
            ], 'Angka = jumlah hari kalender. Hanya pegawai yang punya cuti pada tahun & status terpilih.');
    }

    public function pegawai(Request $r)
    {
        $aktif = $r->input('aktif', '1');
        $baris = Staff::with('atasan')
            ->when($aktif !== 'semua', fn ($q) => $q->where('aktif', $aktif))
            ->when($r->divisi, fn ($q, $v) => $q->where('id_divisi', $v))
            ->when($r->level, fn ($q, $v) => $q->where('id_level', $v))
            ->orderBy('divisi')->orderBy('nama')->get()
            ->map(fn ($s) => [$s->nip, $s->nama, $s->username, $s->aktif ? 'Aktif' : 'Nonaktif', $s->divisi, $s->level, $s->atasan->nama ?? '', $s->status_kepegawaian,
                $s->pendidikan_terakhir, $s->mulai_kerja?->format('d-m-Y'), ['L' => 'L', 'P' => 'P'][$s->jenis_kelamin] ?? '', $s->no_hp]);

        return $this->tampil($r, 'Daftar Pegawai',
            ['NIP', 'Nama', 'Username', 'Aktif', 'Divisi', 'Level', 'Atasan', 'Status', 'Pendidikan', 'Mulai Kerja', 'L/P', 'No. HP'],
            $baris, [
                ['aktif', 'Status', ['1' => 'Aktif', '0' => 'Nonaktif', 'semua' => 'Aktif & nonaktif'], $aktif],
                ['divisi', 'Divisi', ['' => 'Semua divisi'] + $this->divisi(), $r->divisi],
                ['level', 'Level', ['' => 'Semua level'] + Staff::whereNotNull('id_level')->orderBy('level')->pluck('level', 'id_level')->all(), $r->level],
            ]);
    }

    public function dosen(Request $r)
    {
        $aktif = $r->input('aktif', '1');
        $baris = Dosen::with('staff')
            ->when($aktif !== 'semua', fn ($q) => $q->where('aktif', $aktif))
            ->when($r->prodi, fn ($q, $v) => $q->where('program_studi', $v))
            ->when($r->jabfung, fn ($q, $v) => $q->where('jabatan_fungsional', $v))
            ->orderBy('nama')->get()
            ->map(fn ($d) => [$d->nama, $d->nidn, $d->program_studi, $d->status_dosen, $d->aktif ? 'Aktif' : 'Nonaktif', $d->jabatan_fungsional,
                $d->tmt_jabfung?->format('d-m-Y'), $d->angka_kredit, $d->pendidikan_terakhir, $d->serdos ? 'Sudah' : 'Belum', $d->staff->nip ?? '']);

        return $this->tampil($r, 'Daftar Dosen',
            ['Nama', 'NIDN', 'Prodi', 'Status', 'Aktif', 'Jabatan Fungsional', 'TMT Jabfung', 'Angka Kredit', 'Pendidikan', 'Serdos', 'NIP Pegawai'],
            $baris, [
                ['aktif', 'Status', ['1' => 'Aktif', '0' => 'Nonaktif', 'semua' => 'Aktif & nonaktif'], $aktif],
                ['prodi', 'Prodi', ['' => 'Semua prodi'] + array_combine(Dosen::PRODI, Dosen::PRODI), $r->prodi],
                ['jabfung', 'Jabatan', ['' => 'Semua jabatan'] + array_combine(Dosen::JENJANG, Dosen::JENJANG), $r->jabfung],
            ]);
    }

    public function pendidikan(Request $r)
    {
        $baris = RiwayatPendidikan::with('staff', 'dosen')
            ->when($r->status, fn ($q, $v) => $q->where('status', $v))
            ->when($r->jenjang, fn ($q, $v) => $q->where('jenjang', $v))
            ->get()->sortBy('nama')
            ->map(fn ($x) => [$x->nama, $x->staff->nip ?? '', $x->dosen->nidn ?? '', $x->jenjang, $x->institusi, $x->program_studi,
                $x->tahun_masuk, $x->tahun_lulus, $x->status, $x->pembiayaan, $x->berkas ? 'Ada' : '']);

        return $this->tampil($r, 'Riwayat Pendidikan',
            ['Nama', 'NIP', 'NIDN', 'Jenjang', 'Institusi', 'Program Studi', 'Masuk', 'Lulus/Target', 'Status', 'Pembiayaan', 'Berkas'],
            $baris->values(), [
                ['status', 'Status', ['' => 'Semua status'] + array_combine(RiwayatPendidikan::STATUS, RiwayatPendidikan::STATUS), $r->status],
                ['jenjang', 'Jenjang', ['' => 'Semua jenjang'] + array_combine(RiwayatPendidikan::JENJANG, RiwayatPendidikan::JENJANG), $r->jenjang],
            ]);
    }

    // Jumlah pengajuan per jenis × status dalam satu tahun
    public function pengajuan(Request $r)
    {
        $tahun = $r->integer('tahun') ?: now()->year;
        $hitung = \App\Models\Pengajuan::whereYear('created_at', $tahun)
            ->selectRaw('jenis_pengajuan_id, status, count(*) n')->groupBy('jenis_pengajuan_id', 'status')
            ->toBase()->get()->groupBy('jenis_pengajuan_id');
        $status = \App\Models\Pengajuan::STATUS;

        $baris = \App\Models\JenisPengajuan::orderBy('urutan')
            ->when($r->kategori, fn ($q, $v) => $q->where('kategori', $v))->get()
            ->map(function ($j) use ($hitung, $status) {
                $per = ($hitung[$j->id] ?? collect())->pluck('n', 'status');
                return array_merge([$j->nama, $j->kategori], array_map(fn ($s) => (int) ($per[$s] ?? 0), $status), [(int) $per->sum()]);
            });

        return $this->tampil($r, 'Rekap Pengajuan ' . $tahun, array_merge(['Jenis', 'Kategori'], $status, ['Total']), $baris, [
            ['tahun', 'Tahun', array_combine($t = range(now()->year, now()->year - 4), $t), $tahun],
            ['kategori', 'Kategori', ['' => 'Semua kategori'] + array_combine(\App\Models\JenisPengajuan::KATEGORI, \App\Models\JenisPengajuan::KATEGORI), $r->kategori],
        ], 'Dihitung dari tanggal pengajuan dibuat.');
    }

    // ---- bersama ----

    private function tampil(Request $r, string $judul, array $kolom, $baris, array $filter, ?string $catatan = null)
    {
        if ($r->boolean('export')) {
            $book = new Spreadsheet();
            $sheet = $book->getActiveSheet()->setTitle(substr(preg_replace('/[^\w ]/', '', $judul), 0, 31));
            $sheet->fromArray([[$judul], ['Dicetak ' . now()->translatedFormat('d F Y H:i')], [], $kolom], null, 'A1');
            $sheet->fromArray($baris->map(fn ($b) => array_map(fn ($v) => $v ?? '', $b))->all(), null, 'A5', true);
            $akhir = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($kolom));
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A4:{$akhir}4")->getFont()->setBold(true);
            $sheet->setAutoFilter("A4:{$akhir}" . (4 + $baris->count()));
            $sheet->freezePane('A5');
            foreach (range(1, count($kolom)) as $c) {
                $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
            }
            $nama = \Illuminate\Support\Str::slug($judul) . '-' . now()->format('Ymd') . '.xlsx';
            return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), $nama, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        return view('laporan.tabel', compact('judul', 'kolom', 'baris', 'filter', 'catatan'));
    }

    private function divisi(): array
    {
        return Staff::whereNotNull('id_divisi')->orderBy('divisi')->pluck('divisi', 'id_divisi')->all();
    }
}
