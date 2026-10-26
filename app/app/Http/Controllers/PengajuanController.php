<?php

namespace App\Http\Controllers;

use App\Models\JenisCuti;
use App\Models\JenisPengajuan;
use App\Models\Pejabat;
use App\Models\Pengajuan;
use App\Models\Pengaturan;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Pengajuan umum (dinas luar, surat keterangan, reimburse, dll.). Isian & alur diatur per jenis.
class PengajuanController extends Controller
{
    public function index(Request $r)
    {
        $me = $this->myStaffId();
        $bawahan = $this->bawahan();
        $jumlahTugas = Pengajuan::tugas($this->myStaffIds(), $this->isAdmin())->count();
        $tab = $r->input('tab', $jumlahTugas ? 'tugas' : ($this->isAdmin() ? 'semua' : 'saya'));

        $q = Pengajuan::with('jenis', 'staff')->latest();
        if ($tab === 'tugas') {
            // Menunggu keputusan saya: sebagai atasan, pejabat (Warek/Keuangan/BAAK), atau HRD
            $q->tugas($this->myStaffIds(), $this->isAdmin());
        } elseif ($tab === 'atasan') {
            // Semua pengajuan bawahan (riwayat juga)
            $q->whereIn('staff_id', $bawahan)->where('anonim', false)
                ->when($r->status, fn ($w, $v) => $w->where('status', $v));
        } elseif ($tab === 'semua') {
            abort_unless($this->isAdmin(), 403);
            $q->when($r->status, fn ($w, $v) => $w->where('status', $v))
                ->when($r->jenis, fn ($w, $v) => $w->where('jenis_pengajuan_id', $v));
        } else {
            $q->whereIn('staff_id', $this->myStaffIds());
        }

        return view('pengajuan.index', [
            'pengajuan' => $q->paginate(25)->withQueryString(),
            'tab' => $tab,
            'me' => $me,
            'punyaBawahan' => $bawahan->isNotEmpty(),
            'jumlahTugas' => $jumlahTugas,
            // Tab "Perlu Persetujuan" tampil untuk siapa pun yang bisa memutus (atasan, pejabat, HRD)
            'bisaMenyetujui' => $jumlahTugas || $bawahan->isNotEmpty() || $this->isAdmin() || Pejabat::peranDari($this->myStaffIds())->isNotEmpty(),
            'jenisList' => JenisPengajuan::orderBy('urutan')->pluck('nama', 'id'),
        ]);
    }

    // Tanpa ?jenis: pilih jenis form. Dengan ?jenis: tampilkan form-nya.
    public function create(Request $r)
    {
        if (!$this->myStaffId()) {
            return redirect()->route('pengajuan.index')->withErrors('Akun Anda belum tertaut ke data pegawai, belum bisa mengajukan. Hubungi HRD.');
        }
        $jenis = $r->jenis ? JenisPengajuan::where('aktif', true)->findOrFail($r->jenis) : null;
        return view('pengajuan.create', [
            'jenis' => $jenis,
            'daftar' => JenisPengajuan::where('aktif', true)->orderBy('urutan')->get()->groupBy('kategori'),
            'staff' => Staff::with('atasan')->find($this->myStaffId()),
            'rekap' => $jenis?->isCuti() ? JenisCuti::rekap($this->myStaffIds()->all()) : null,
        ]);
    }

    public function store(Request $r)
    {
        $staff = Staff::findOrFail($this->myStaffId() ?? abort(403));
        $jenis = JenisPengajuan::where('aktif', true)->findOrFail($r->jenis_pengajuan_id);

        // Validasi dinamis sesuai isian jenisnya. Nama input: isian[key]
        $aturan = [];
        foreach ($jenis->isian as $f) {
            $aturan["isian.{$f['key']}"] = array_merge([$f['wajib'] ? 'required' : 'nullable'], match ($f['tipe']) {
                'date' => ['date'], 'time' => ['date_format:H:i'], 'number' => ['numeric', 'min:0'],
                'rupiah' => ['regex:/^[\d.,\s]+$/'], 'select' => [Rule::in($f['opsi'] ?? [])],
                'file' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:5120'],
                'textarea' => ['string', 'max:5000'], default => ['string', 'max:255'],
            });
        }
        $label = collect($jenis->isian)->mapWithKeys(fn ($f) => ["isian.{$f['key']}" => $f['label']])->all();
        $r->validate($aturan, [], $label);
        // Sebelum berkas disimpan, supaya gagal jatah tidak meninggalkan berkas yatim
        $cuti = $jenis->isCuti() ? $this->validasiCuti($r, $staff) : [];

        $data = [];
        foreach ($jenis->isian as $f) {
            $nilai = $r->input("isian.{$f['key']}");
            $data[$f['key']] = match ($f['tipe']) {
                'file' => $r->file("isian.{$f['key']}")?->store('pengajuan'),
                'rupiah' => $nilai === null ? null : (int) preg_replace('/\D/', '', $nilai),
                default => $nilai,
            };
        }

        $anonim = $jenis->boleh_anonim && $r->boolean('anonim');
        $p = Pengajuan::create($cuti + [
            'jenis_pengajuan_id' => $jenis->id, 'staff_id' => $staff->id, 'anonim' => $anonim,
            'data' => $data,
        ] + Pengajuan::alurAwal($jenis, $staff, $anonim));
        return redirect()->route('pengajuan.show', $p)->with('ok', "Pengajuan {$jenis->nama} dikirim ({$p->status}).");
    }

    // Cuti: jenis & tanggal + cek jatah tahun kalender tgl_mulai (jenis tanpa kuota, mis. Izin, tidak dibatasi)
    private function validasiCuti(Request $r, Staff $staff): array
    {
        $d = $r->validate([
            'jenis_cuti_id' => ['required', Rule::exists('jenis_cuti', 'id')->where('aktif', true)],
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
        ], [], ['jenis_cuti_id' => 'jenis cuti', 'tgl_mulai' => 'tanggal mulai', 'tgl_selesai' => 'tanggal selesai']);

        $tahun = Carbon::parse($d['tgl_mulai'])->year;
        $jatah = JenisCuti::rekap($this->myStaffIds()->all(), $tahun)[$d['jenis_cuti_id']];
        $hari = Carbon::parse($d['tgl_mulai'])->diffInDays($d['tgl_selesai']) + 1;
        if ($jatah->sisa !== null && $hari > $jatah->sisa) {
            throw ValidationException::withMessages(['tgl_selesai' => "Melebihi jatah {$jatah->jenis->nama} {$tahun}: diajukan {$hari} hari, sisa {$jatah->sisa} dari {$jatah->kuota} hari."]);
        }
        return $d;
    }

    public function show(Pengajuan $pengajuan)
    {
        abort_unless($this->bolehLihat($pengajuan), 403);
        $pengajuan->load('jenis', 'jenisCuti', 'staff.atasan');
        return view('pengajuan.show', ['p' => $pengajuan, 'me' => $this->myStaffId(), 'tahap' => $this->tahapProses($pengajuan)]);
    }

    // Setujui / tolak oleh atasan (tahap 1) atau HRD (tahap akhir)
    public function proses(Request $r, Pengajuan $pengajuan)
    {
        $tahap = $this->tahapProses($pengajuan);
        abort_unless($tahap, 403, 'Anda tidak berwenang memproses pengajuan ini, atau pengajuan sudah diproses.');
        $d = $r->validate(['keputusan' => 'required|in:setujui,tolak', 'catatan' => 'nullable|string|max:2000']);

        $pengajuan->putuskan($d['keputusan'] === 'setujui', auth()->user()->name, $d['catatan'] ?? null);
        return back()->with('ok', "Pengajuan {$pengajuan->status}." . ($pengajuan->nomor ? " Nomor: {$pengajuan->nomor}" : ''));
    }

    // Pengaju membatalkan selama belum selesai diproses
    public function batal(Pengajuan $pengajuan)
    {
        abort_unless($this->myStaffIds()->contains($pengajuan->staff_id) && !$pengajuan->selesai(), 403);
        $pengajuan->update(['status' => 'Dibatalkan']);
        return back()->with('ok', 'Pengajuan dibatalkan.');
    }

    // PDF pengajuan. Disetujui → tanda tangan pejabat Rektor berupa QR menuju halaman validasi publik.
    public function pdf(Pengajuan $pengajuan)
    {
        abort_unless($this->bolehLihat($pengajuan), 403);
        $pengajuan->load('jenis', 'jenisCuti', 'staff');

        $qr = $pengajuan->kode_validasi
            ? base64_encode(\Endroid\QrCode\Builder\Builder::create()
                ->data(route('validasi', $pengajuan->kode_validasi))->size(240)->margin(0)->build()->getString())
            : null;

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pengajuan.pdf', [
            'p' => $pengajuan,
            'qr' => $qr,
            'pengaju' => $pengajuan->namaPengaju($this->myStaffId()),
            'kota' => Pengaturan::nilai('kota', 'Palembang'),
        ])->setPaper('a4')->stream('Pengajuan-' . str_replace('/', '-', $pengajuan->nomor ?? $pengajuan->id) . '.pdf');
    }

    // Halaman validasi publik (tujuan QR). Hanya ringkasan; isi rinci tidak ditampilkan.
    public function validasi(string $kode)
    {
        return view('pengajuan.validasi', ['p' => Pengajuan::with('jenis', 'staff')->where('kode_validasi', $kode)->first()]);
    }

    // Berkas lampiran disimpan privat
    public function berkas(Pengajuan $pengajuan, string $key)
    {
        abort_unless($this->bolehLihat($pengajuan), 403);
        $path = $pengajuan->data[$key] ?? null;
        abort_unless($path && Storage::exists($path), 404);
        return Storage::response($path);
    }

    // Pegawai yang atasannya salah satu akun staff saya
    private function bawahan()
    {
        $ids = $this->myStaffIds();
        return $ids->isEmpty() ? collect() : Staff::whereIn('atasan_id', $ids)->pluck('id');
    }

    private function atasannya(Pengajuan $p): bool
    {
        return $this->myStaffIds()->contains(Staff::whereKey($p->staff_id)->value('atasan_id'));
    }

    // Pengaju, admin, atasannya (bukan anonim), atau pejabat yang ada di alur pengajuan ini
    private function bolehLihat(Pengajuan $p): bool
    {
        return $this->isAdmin() || $this->myStaffIds()->contains($p->staff_id) || (!$p->anonim && $this->atasannya($p))
            || Pejabat::peranDari($this->myStaffIds())->intersect($p->tahapan ?? [])->isNotEmpty();
    }

    // Tahap yang boleh diputus user ini sekarang, atau null. Alur wajib urut (hanya tahap yang sedang berjalan),
    // dan tidak ada yang memutus pengajuannya sendiri. Atasan tidak aktif → HRD mengganti atasan di Atasan & Bawahan.
    private function tahapProses(Pengajuan $p): ?string
    {
        $tahap = $p->tahapSekarang();
        if (!$tahap || $this->myStaffIds()->contains($p->staff_id)) {
            return null;
        }
        $boleh = match ($tahap) {
            'atasan' => $this->atasannya($p),
            'hrd' => $this->isAdmin(),
            default => Pejabat::peranDari($this->myStaffIds())->contains($tahap),
        };
        return $boleh ? $tahap : null;
    }
}
