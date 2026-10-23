<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\RiwayatPendidikan;
use App\Models\Staff;
use App\Services\PctApi;
use App\Services\Sinkron;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DosenController extends Controller
{
    use BiodataApi;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->isAdmin(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $dosen = Dosen::query()
            ->where('aktif', $request->input('aktif', 1))
            ->when($request->prodi, fn ($q, $v) => $q->where('program_studi', $v))
            ->when($request->jabfung, fn ($q, $v) => $q->where('jabatan_fungsional', $v))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('nama', 'like', "%$s%")->orWhere('nidn', 'like', "%$s%")))
            ->orderBy('nama')
            ->paginate(25)->withQueryString();

        return view('dosen.index', compact('dosen'));
    }

    public function sinkron(Sinkron $sinkron)
    {
        $this->wajibScope('staff.read', 'dosen.read');
        set_time_limit(0); // jaringan ke API lambat, bisa beberapa menit
        try {
            $s = $sinkron->jalankan();
        } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
            report($e);
            return back()->withErrors('Sinkron gagal: server eUniversity tidak bisa dihubungi. Coba lagi nanti.');
        }
        return back()->with('ok', "Sinkron selesai: {$s['staff_api']} pegawai & {$s['dosen_api']} dosen dari API "
            . "({$s['dosen_baru']} dosen baru, {$s['dosen_diperbarui']} diperbarui, {$s['dosen_tertaut_pegawai']} tertaut pegawai).");
    }

    public function show(Dosen $dosen)
    {
        $dosen->load('staff');
        $riwayat = RiwayatPendidikan::milik($dosen->staff, $dosen)->get();
        return view('dosen.show', compact('dosen', 'riwayat'));
    }

    public function create(Request $request)
    {
        $dosen = new Dosen(['aktif' => true]);
        // Dari halaman pegawai: isi otomatis biodata pegawai tsb
        if ($p = Staff::find($request->staff_id)) {
            $dosen->fill(['staff_id' => $p->id, 'status_dosen' => 'Tetap'] + $p->only([
                'nama', 'jenis_kelamin', 'tempat_lahir', 'tgl_lahir', 'agama', 'gol_dar', 'no_hp', 'alamat', 'mulai_kerja',
            ]));
        }
        return view('dosen.form', ['dosen' => $dosen, 'pegawai' => $this->pilihanPegawai()]);
    }

    public function store(Request $request, PctApi $api)
    {
        $this->wajibScope('dosen.create');
        $data = $this->validasi($request);
        $hasil = $this->keApi(fn () => $api->tulis('POST', '/dosen', $this->payloadDosen($data)));

        $dosen = Dosen::create($this->lokal($data) + ['api_id' => $hasil['idDosen']]);
        return redirect()->route('dosen.index')->with('ok', "Dosen {$dosen->nama} ditambahkan ke eUniversity & HRD.");
    }

    public function edit(Dosen $dosen)
    {
        return view('dosen.form', ['dosen' => $dosen, 'pegawai' => $this->pilihanPegawai($dosen->staff_id)]);
    }

    public function update(Request $request, Dosen $dosen, PctApi $api)
    {
        $this->wajibScope($dosen->api_id ? 'dosen.update' : 'dosen.create');
        $data = $this->validasi($request, $dosen);
        // Dosen yang belum ada di eUniversity (input manual lama) didaftarkan sekalian
        $hasil = $this->keApi(fn () => $dosen->api_id
            ? $api->tulis('PATCH', "/dosen/{$dosen->api_id}", $this->payloadDosen($data))
            : $api->tulis('POST', '/dosen', $this->payloadDosen($data)));

        $dosen->update($this->lokal($data) + ['api_id' => $dosen->api_id ?? $hasil['idDosen']]);
        return redirect()->route('dosen.index')->with('ok', "Data {$dosen->nama} disimpan ke eUniversity & HRD.");
    }

    public function destroy(Dosen $dosen)
    {
        $this->wajibScope('dosen.delete');
        // 409 = dosen masih dipakai (jadwal, PA, dll.): sarankan nonaktifkan saja
        if ($dosen->api_id && $gagal = $this->hapusDiApi("/dosen/{$dosen->api_id}")) {
            return back()->withErrors("$gagal Nonaktifkan saja di HRD.");
        }
        $dosen->delete();
        return redirect()->route('dosen.index')->with('ok', "Dosen {$dosen->nama} dihapus.");
    }

    private function payloadDosen(array $d): array
    {
        // NIDN kosong dikirim '-' (format data lama eUniversity)
        return $this->payloadBiodata($d) + ['nidn' => $d['nidn'] ?? '-'];
    }

    // Kolom lokal = biodata (sama dengan API) + isian khusus HRD
    private function lokal(array $d): array
    {
        return $this->lokalBiodata($d) + collect($d)->only([
            'staff_id', 'nidn', 'email', 'program_studi', 'status_dosen', 'pendidikan_terakhir',
            'jabatan_fungsional', 'tmt_jabfung', 'angka_kredit', 'serdos', 'aktif',
        ])->all();
    }

    private function validasi(Request $request, ?Dosen $dosen = null): array
    {
        $data = $request->validate($this->aturanBiodata() + [
            'staff_id' => ['nullable', 'integer', Rule::unique('dosens')->ignore($dosen)],
            'nidn' => ['nullable', 'string', 'max:20', Rule::unique('dosens')->ignore($dosen)],
            'email' => 'nullable|email',
            'program_studi' => 'nullable|string|max:100',
            'status_dosen' => ['nullable', Rule::in(Dosen::STATUS)],
            'pendidikan_terakhir' => 'nullable|in:S1,S2,S3',
            'jabatan_fungsional' => ['nullable', Rule::in(Dosen::JENJANG)],
            'tmt_jabfung' => 'nullable|date',
            'angka_kredit' => 'nullable|integer|min:0',
        ], ['staff_id.unique' => 'Pegawai ini sudah terdaftar sebagai dosen.', 'nidn.unique' => 'NIDN sudah dipakai.']);
        $data['serdos'] = $request->boolean('serdos');
        $data['aktif'] = $request->boolean('aktif');
        return $data;
    }

    // Pegawai yang belum terdaftar sebagai dosen (+ yang sedang dipakai saat edit)
    private function pilihanPegawai(?int $kecuali = null)
    {
        $sudah = Dosen::whereNotNull('staff_id')->when($kecuali, fn ($q) => $q->where('staff_id', '!=', $kecuali))->pluck('staff_id');
        return Staff::whereNotIn('id', $sudah)->orderBy('nama')->get(['id', 'nama', 'nip', 'divisi']);
    }
}
