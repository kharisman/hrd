<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use App\Models\JenisCuti;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CutiController extends Controller
{
    public function index(Request $request)
    {
        $query = Cuti::with('staff', 'jenisCuti')->latest();

        if (!$this->isAdmin()) {
            // Pegawai melihat cutinya sendiri + cuti bawahan langsung (atasan_id)
            $me = $this->myStaffId();
            $query->whereIn('staff_id', Staff::where('atasan_id', $me)->pluck('id')->push($me));
        }

        $cuti = $query->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->paginate(25)->withQueryString();

        return view('cuti.index', ['cuti' => $cuti, 'me' => $this->myStaffId()]);
    }

    public function create()
    {
        if (!$this->isAdmin() && !$this->myStaffId()) {
            return redirect()->route('cuti.index')->withErrors('Akun Anda belum tertaut ke data pegawai, belum bisa mengajukan cuti. Hubungi HRD.');
        }
        // Admin bisa mengajukan atas nama pegawai
        $pegawai = $this->isAdmin() ? Staff::aktif()->orderBy('nama')->get(['id', 'nama', 'nip', 'divisi']) : null;
        // Rekap jatah: milik sendiri, atau pegawai yang dipilih admin (?staff_id=)
        $staffId = $this->isAdmin() ? request()->integer('staff_id') : $this->myStaffId();
        $rekap = JenisCuti::rekap($staffId ?: 0);
        return view('cuti.create', compact('pegawai', 'rekap', 'staffId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'staff_id' => $this->isAdmin() ? ['required', Rule::exists('staffs', 'id')] : 'prohibited',
            'jenis_cuti_id' => ['required', Rule::exists('jenis_cuti', 'id')->where('aktif', true)],
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required|string',
        ]);
        $data['staff_id'] = $data['staff_id'] ?? $this->myStaffId();
        abort_unless($data['staff_id'], 403);

        // Cek jatah tahun kalender tgl_mulai (jenis tanpa kuota, mis. Izin, tidak dibatasi)
        $tahun = Carbon::parse($data['tgl_mulai'])->year;
        $jatah = JenisCuti::rekap($data['staff_id'], $tahun)[$data['jenis_cuti_id']];
        $hari = Carbon::parse($data['tgl_mulai'])->diffInDays($data['tgl_selesai']) + 1;
        if ($jatah->sisa !== null && $hari > $jatah->sisa) {
            throw ValidationException::withMessages(['tgl_selesai' => "Melebihi jatah {$jatah->jenis->nama} {$tahun}: diajukan {$hari} hari, sisa {$jatah->sisa} dari {$jatah->kuota} hari."]);
        }

        Cuti::create($data);
        return redirect()->route('cuti.index')->with('ok', 'Pengajuan cuti dikirim.');
    }

    public function proses(Request $request, Cuti $cuti)
    {
        // Yang boleh memproses: admin HRD, atau atasan langsung pemohon (atasan_id diisi HRD)
        $isAtasan = $this->myStaffId() && $cuti->staff?->atasan_id == $this->myStaffId();
        abort_unless($this->isAdmin() || $isAtasan, 403);
        abort_unless($cuti->status === 'Menunggu', 422, 'Cuti sudah diproses.');

        $data = $request->validate([
            'status' => 'required|in:Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        $data['diproses_oleh'] = auth()->user()->name;
        $cuti->update($data);

        return back()->with('ok', "Cuti {$data['status']}.");
    }
}
