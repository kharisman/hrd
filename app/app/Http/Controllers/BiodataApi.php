<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Services\ApiDitolak;
use App\Services\PctApi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Kolom biodata yang wajib di API POST/PATCH /dosen & /staff. Nama input form = nama kolom API,
// supaya error 422 dari API langsung tampil di kolom yang benar.
trait BiodataApi
{
    protected function aturanBiodata(): array
    {
        return [
            'nama' => 'required|string|max:150',
            'username' => 'required|string|max:50',
            'password' => 'nullable|string|min:6', // kosong: API pakai default saat tambah, tidak diubah saat edit
            'jenis_kelamin' => 'required|in:Pria,Wanita',
            'tempat_lahir' => 'required|string|max:100',
            'tgl_lahir' => 'required|date',
            'agama' => 'required|string|max:15',
            'telp' => 'required|string|max:15',
            'alamat' => 'required|string',
            'gol_dar' => ['nullable', Rule::in(Dosen::GOL_DAR)],
            'mulai_kerja' => 'required|date',
        ];
    }

    // Aksi ke API hanya untuk akun eUniversity yang login dengan scope cukup (tombol juga dinonaktifkan di tampilan)
    protected function wajibScope(string ...$scopes): void
    {
        abort_unless(Gate::allows('api', $scopes), 403,
            'Akun Anda tidak punya izin eUniversity untuk aksi ini (' . implode(', ', $scopes) . '). Login dengan akun eUniversity yang punya izin tersebut.');
    }

    // Jalankan panggilan API; penolakan dikembalikan ke form, gangguan jaringan jadi pesan umum
    protected function keApi(callable $panggil): ?array
    {
        try {
            return $panggil();
        } catch (ApiDitolak $e) {
            throw $e->keForm();
        } catch (ConnectionException|RequestException $e) {
            report($e);
            throw ValidationException::withMessages(['api' => 'Server eUniversity tidak bisa dihubungi, data belum disimpan. Coba lagi.']);
        }
    }

    // DELETE ke API. Return pesan error (null = boleh hapus lokal). 404 = sudah tidak ada di API.
    protected function hapusDiApi(string $path): ?string
    {
        try {
            app(PctApi::class)->tulis('DELETE', $path);
        } catch (ApiDitolak $e) {
            return str_contains($e->getMessage(), 'tidak ditemukan') ? null : "Tidak bisa dihapus: {$e->getMessage()}.";
        } catch (ConnectionException|RequestException $e) {
            return 'Server eUniversity tidak bisa dihubungi. Coba lagi nanti.';
        }
        return null;
    }

    protected function payloadBiodata(array $d): array
    {
        return collect($d)->only(['nama', 'username', 'password', 'jenis_kelamin', 'tempat_lahir', 'tgl_lahir', 'agama', 'telp', 'alamat', 'gol_dar', 'mulai_kerja'])
            ->reject(fn ($v, $k) => $k === 'password' && blank($v)) // kosong = password tidak diubah
            ->all();
    }

    // Kolom tabel lokal dari input form
    protected function lokalBiodata(array $d): array
    {
        return [
            'nama' => $d['nama'],
            'username' => $d['username'],
            'jenis_kelamin' => $d['jenis_kelamin'] === 'Pria' ? 'L' : 'P',
            'tempat_lahir' => $d['tempat_lahir'],
            'tgl_lahir' => $d['tgl_lahir'],
            'agama' => $d['agama'],
            'gol_dar' => $d['gol_dar'] ?? null,
            'no_hp' => $d['telp'],
            'alamat' => $d['alamat'],
            'mulai_kerja' => $d['mulai_kerja'],
            'disinkron_at' => now(),
        ];
    }
}
