<?php

namespace App\Http\Controllers;

use App\Models\RiwayatPendidikan;
use App\Models\Staff;
use App\Services\PctApi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Pegawai = staff eUniversity: tambah/ubah/hapus ke API dulu, lalu salinan lokal (hrd_staffs).
// Isian HRD (atasan, status kepegawaian, pendidikan) hanya disimpan lokal.
class StaffController extends Controller
{
    use BiodataApi;

    public function __construct()
    {
        // Pegawai biasa hanya boleh melihat profilnya sendiri
        $this->middleware(function ($request, $next) {
            abort_unless($this->isAdmin(), 403);
            return $next($request);
        })->except('index', 'show');
    }

    public function index(Request $request)
    {
        if (!$this->isAdmin()) {
            return $this->myStaffId()
                ? redirect()->route('staff.show', $this->myStaffId())
                : redirect()->route('dashboard')->withErrors('Akun Anda belum tertaut ke data pegawai eUniversity. Hubungi HRD.');
        }

        $staff = Staff::with('atasan')
            ->when($request->divisi, fn ($q, $v) => $q->where('id_divisi', $v))
            ->when($request->level, fn ($q, $v) => $q->where('id_level', $v))
            ->when($request->atasan === 'kosong', fn ($q) => $q->whereNull('atasan_id'))
            ->when($request->atasan === 'ada', fn ($q) => $q->whereNotNull('atasan_id'))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('nama', 'like', "%$s%")->orWhere('nip', 'like', "%$s%")->orWhere('username', 'like', "%$s%")))
            ->orderBy('nama')
            ->paginate(50)->withQueryString();

        return view('staff.index', ['staff' => $staff] + $this->pilihan());
    }

    // Atur atasan banyak pegawai sekaligus dari daftar (centang). Atasan kosong = lepas atasan.
    // Data divisi/level di eUniversity tidak cukup rapi untuk pengisian otomatis (satu akun bisa ada di banyak divisi).
    public function aturAtasan(Request $request)
    {
        $data = $request->validate([
            'pegawai' => 'required|array|min:1',
            'pegawai.*' => 'integer',
            'atasan_id' => ['nullable', Rule::exists('staffs', 'id')],
        ], ['pegawai.required' => 'Centang dulu pegawai yang mau diatur atasannya.']);

        // Atasan tidak boleh dirinya sendiri
        $ids = collect($data['pegawai'])->reject(fn ($id) => $id == $data['atasan_id']);
        $n = Staff::whereIn('id', $ids)->update(['atasan_id' => $data['atasan_id']]);

        $atasan = $data['atasan_id'] ? Staff::find($data['atasan_id'])->nama : 'kosong';
        return back()->with('ok', "Atasan {$n} pegawai diatur ke: {$atasan}.");
    }

    public function show(Staff $staff)
    {
        abort_unless($this->isAdmin() || $staff->id == $this->myStaffId(), 403);
        $staff->load('atasan', 'bawahan', 'dosen');
        $riwayat = RiwayatPendidikan::milik($staff, $staff->dosen)->get();
        return view('staff.show', compact('staff', 'riwayat'));
    }

    public function create()
    {
        return view('staff.form', ['staff' => new Staff] + $this->pilihan());
    }

    public function store(Request $request, PctApi $api)
    {
        $this->wajibScope('staff.create');
        $data = $this->validasi($request);
        $hasil = $this->keApi(fn () => $api->tulis('POST', '/staff', $this->payloadStaff($data)));

        $staff = Staff::create($this->lokal($data) + ['api_id' => $hasil['idStaff']]);
        return redirect()->route('staff.show', $staff)->with('ok', "Pegawai {$staff->nama} ditambahkan ke eUniversity & HRD.");
    }

    public function edit(Staff $staff)
    {
        return view('staff.form', ['staff' => $staff] + $this->pilihan($staff));
    }

    public function update(Request $request, Staff $staff, PctApi $api)
    {
        $this->wajibScope('staff.update');
        $data = $this->validasi($request, $staff);
        $this->keApi(fn () => $api->tulis('PATCH', "/staff/{$staff->api_id}", $this->payloadStaff($data)));

        $staff->update($this->lokal($data));
        return redirect()->route('staff.show', $staff)->with('ok', "Data {$staff->nama} disimpan ke eUniversity & HRD.");
    }

    public function destroy(Staff $staff)
    {
        $this->wajibScope('staff.delete');
        if ($staff->api_id && $gagal = $this->hapusDiApi("/staff/{$staff->api_id}")) {
            return back()->withErrors($gagal);
        }
        Staff::where('atasan_id', $staff->id)->update(['atasan_id' => null]);
        $staff->delete();
        return redirect()->route('staff.index')->with('ok', "Pegawai {$staff->nama} dihapus.");
    }

    private function validasi(Request $request, ?Staff $staff = null): array
    {
        return $request->validate($this->aturanBiodata() + [
            // NIP tidak dicek unik di sini: satu orang bisa punya beberapa akun staff (beda divisi/role). API yang memutuskan (409).
            'nip' => 'required|string|max:30',
            'id_divisi' => 'required|integer',
            'id_level' => 'required|integer',
            // Isian HRD
            'atasan_id' => ['nullable', Rule::exists('staffs', 'id'), Rule::notIn([$staff?->id])],
            'status_kepegawaian' => ['nullable', Rule::in(Staff::STATUS)],
        ], ['atasan_id.not_in' => 'Atasan tidak boleh diri sendiri.']);
    }

    private function payloadStaff(array $d): array
    {
        return $this->payloadBiodata($d) + collect($d)->only(['nip', 'id_divisi', 'id_level'])->all();
    }

    private function lokal(array $d): array
    {
        $p = $this->pilihan();
        return $this->lokalBiodata($d) + collect($d)->only(['atasan_id', 'status_kepegawaian'])->all() + [
            'nip' => $d['nip'],
            'id_divisi' => $d['id_divisi'],
            'divisi' => $p['divisiList'][$d['id_divisi']] ?? null,
            'id_level' => $d['id_level'],
            'level' => $p['levelList'][$d['id_level']] ?? null,
        ];
    }

    // ponytail: pilihan divisi/level diambil dari staff yang sudah tersinkron, bukan memanggil API
    // (jaringan lambat). Divisi/level baru di eUniversity muncul setelah sinkron.
    private function pilihan(?Staff $kecuali = null): array
    {
        return [
            'divisiList' => Staff::whereNotNull('id_divisi')->orderBy('divisi')->pluck('divisi', 'id_divisi'),
            'levelList' => Staff::whereNotNull('id_level')->orderBy('level')->pluck('level', 'id_level'),
            'atasanList' => Staff::when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali->id))->orderBy('nama')->get(['id', 'nama', 'divisi', 'level']),
        ];
    }
}
