<?php

namespace App\Http\Controllers;

use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use App\Models\Pengaturan;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// Pengajuan umum (dinas luar, surat keterangan, reimburse, dll.). Isian & alur diatur per jenis.
class PengajuanController extends Controller
{
    public function index(Request $r)
    {
        $me = $this->myStaffId();
        $bawahan = $me ? Staff::where('atasan_id', $me)->pluck('id') : collect();
        $tab = $r->input('tab', $this->isAdmin() ? 'semua' : 'saya');

        $q = Pengajuan::with('jenis', 'staff')->latest();
        if ($tab === 'atasan') {
            $q->whereIn('staff_id', $bawahan)->where('anonim', false)->where('status', Pengajuan::MENUNGGU_ATASAN);
        } elseif ($tab === 'semua') {
            abort_unless($this->isAdmin(), 403);
            $q->when($r->status, fn ($w, $v) => $w->where('status', $v))
                ->when($r->jenis, fn ($w, $v) => $w->where('jenis_pengajuan_id', $v));
        } else {
            $q->where('staff_id', $me ?? 0);
        }

        return view('pengajuan.index', [
            'pengajuan' => $q->paginate(25)->withQueryString(),
            'tab' => $tab,
            'me' => $me,
            'jumlahAtasan' => $bawahan->isEmpty() ? 0 : Pengajuan::whereIn('staff_id', $bawahan)->where('anonim', false)->where('status', Pengajuan::MENUNGGU_ATASAN)->count(),
            'jumlahHrd' => $this->isAdmin() ? Pengajuan::where('status', Pengajuan::MENUNGGU_HRD)->count() : 0,
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
        $p = Pengajuan::create([
            'jenis_pengajuan_id' => $jenis->id, 'staff_id' => $staff->id, 'anonim' => $anonim,
            'data' => $data, 'status' => Pengajuan::statusAwal($jenis, $staff, $anonim),
        ]);
        return redirect()->route('pengajuan.show', $p)->with('ok', "Pengajuan {$jenis->nama} dikirim ({$p->status}).");
    }

    public function show(Pengajuan $pengajuan)
    {
        abort_unless($this->bolehLihat($pengajuan), 403);
        $pengajuan->load('jenis', 'staff.atasan');
        return view('pengajuan.show', ['p' => $pengajuan, 'me' => $this->myStaffId(), 'tahap' => $this->tahapProses($pengajuan)]);
    }

    // Setujui / tolak oleh atasan (tahap 1) atau HRD (tahap akhir)
    public function proses(Request $r, Pengajuan $pengajuan)
    {
        $tahap = $this->tahapProses($pengajuan);
        abort_unless($tahap, 403, 'Anda tidak berwenang memproses pengajuan ini, atau pengajuan sudah diproses.');
        $d = $r->validate(['keputusan' => 'required|in:setujui,tolak', 'catatan' => 'nullable|string|max:2000']);

        $setuju = $d['keputusan'] === 'setujui';
        $pengajuan->load('jenis');
        $pengajuan->update([
            "{$tahap}_oleh" => auth()->user()->name,
            "{$tahap}_at" => now(),
            "{$tahap}_catatan" => $d['catatan'] ?? null,
            'status' => !$setuju ? 'Ditolak' : ($tahap === 'atasan' ? $pengajuan->statusSetelahAtasan() : 'Disetujui'),
        ]);
        if ($pengajuan->status === 'Disetujui') {
            $pengajuan->sahkan(); // nomor surat + QR tanda tangan
        }
        return back()->with('ok', "Pengajuan {$pengajuan->status}." . ($pengajuan->nomor ? " Nomor: {$pengajuan->nomor}" : ''));
    }

    // Pengaju membatalkan selama belum selesai diproses
    public function batal(Pengajuan $pengajuan)
    {
        abort_unless($pengajuan->staff_id === $this->myStaffId() && !$pengajuan->selesai(), 403);
        $pengajuan->update(['status' => 'Dibatalkan']);
        return back()->with('ok', 'Pengajuan dibatalkan.');
    }

    // PDF pengajuan. Disetujui → tanda tangan pejabat Rektor berupa QR menuju halaman validasi publik.
    public function pdf(Pengajuan $pengajuan)
    {
        abort_unless($this->bolehLihat($pengajuan), 403);
        $pengajuan->load('jenis', 'staff');

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

    private function bolehLihat(Pengajuan $p): bool
    {
        $me = $this->myStaffId();
        $atasannya = !$p->anonim && $me && Staff::whereKey($p->staff_id)->value('atasan_id') === $me;
        return $this->isAdmin() || ($me && $p->staff_id === $me) || $atasannya;
    }

    // 'atasan' / 'hrd' / null: tahap yang boleh diproses user ini sekarang
    private function tahapProses(Pengajuan $p): ?string
    {
        $me = $this->myStaffId();
        if ($p->status === Pengajuan::MENUNGGU_ATASAN && $me && Staff::whereKey($p->staff_id)->value('atasan_id') === $me) {
            return 'atasan';
        }
        // HRD boleh memutus di tahap mana pun yang masih berjalan (mis. atasan tidak aktif), kecuali pengajuannya sendiri
        if ($this->isAdmin() && !$p->selesai() && $p->staff_id !== $me) {
            return 'hrd';
        }
        return null;
    }
}
