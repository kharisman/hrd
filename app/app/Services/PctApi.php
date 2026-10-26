<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

// Klien API Akademik PalComTech. Server butuh X-API-Key + User-Agent browser (tanpa UA kena 403).
// Semua permintaan memakai token akun eUniversity yang sedang login (disimpan di session),
// jadi hak aksesnya mengikuti scope akun tersebut. Tidak ada akun layanan.
class PctApi
{
    private ?string $token = null;

    // $coba/$jeda: login dibuat cepat menyerah, sinkron (proses panjang) dibuat lebih sabar
    private function http(?string $token = null, int $coba = 3, int $jeda = 1000)
    {
        return Http::baseUrl(config('services.pct.url'))
            ->withHeaders(['X-API-Key' => config('services.pct.key'), 'User-Agent' => 'Mozilla/5.0 HRD-PalComTech'])
            ->when($token, fn ($h) => $h->withToken($token))
            ->acceptJson()
            ->connectTimeout(30) // jaringan ke beta1 bisa lambat hanya untuk membuka koneksi
            ->timeout(45)
            // ponytail: koneksi ke beta1 kadang timeout, retry hanya untuk error jaringan
            ->retry($coba, $jeda, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException, false);
    }

    // Token dipakai: yang di-set (CLI), atau milik user yang login (session)
    public function pakaiToken(?string $token): static
    {
        $this->token = $token;
        return $this;
    }

    private function token(): string
    {
        return $this->token ?? session('pct_token') ?? throw new SesiApiHabis();
    }

    /**
     * Login & kumpulkan profil yang dibutuhkan HRD. Token TIDAK dicabut: disimpan di session
     * untuk semua aksi berikutnya. Return null jika kredensial salah.
     */
    public function login(string $username, string $password): ?array
    {
        $res = $this->http()->post('/auth/login', compact('username', 'password'));
        if ($res->status() === 422 || $res->status() === 401) {
            return null;
        }
        $res->throw();
        return $this->profil($res->json('data.token'), $res->json('data.user'));
    }

    /**
     * Login OTP Telegram (diurus eUniversity). Kirim: null = terkirim, selain itu [error, pesan, data].
     * error: not_found, ambigu (data.kandidat = daftar username), telegram_belum_terhubung, terlalu_sering, ...
     */
    public function otpKirim(string $identitas): ?array
    {
        $res = $this->http(null, 1)->post('/auth/otp/kirim', compact('identitas'));
        return $res->successful() ? null : $this->galat($res);
    }

    // Verifikasi kode → [profil, null] bila benar, [null, galat] bila ditolak
    public function otpVerifikasi(string $identitas, string $kode): array
    {
        $res = $this->http(null, 1)->post('/auth/otp/verifikasi', compact('identitas', 'kode'));
        if (!$res->successful()) {
            return [null, $this->galat($res)];
        }
        return [$this->profil($res->json('data.token'), $res->json('data.user')), null];
    }

    // Token atas nama aplikasi HRD (cukup X-API-Key) untuk sinkron terjadwal tanpa password orang
    public function tokenAplikasi(): string
    {
        return $this->cek($this->http(null, 8, 3000)->post('/auth/token'))->json('data.token');
    }

    private function galat($res): array
    {
        if ($res->serverError()) {
            $res->throw();
        }
        return ['error' => $res->json('error'), 'pesan' => $res->json('message'), 'data' => (array) $res->json('data')];
    }

    // Profil yang dibutuhkan HRD dari token + user hasil login
    private function profil(string $token, array $user): array
    {
        // Balasan verifikasi OTP mungkin tanpa id → ambil dari /me
        $user['id'] ??= $this->http($token)->get('/me')->json('data.id');
        $username = $user['username'];
        $scopes = $user['scopes'] ?? [];

        // Profil staff/dosen hanya bisa dibaca bila punya scope read; cocokkan persis Username ("q" bersifat like)
        $cari = fn ($path, $scope) => array_intersect(['*', $scope], $scopes)
            ? collect($this->http($token)->get($path, ['q' => $username, 'per_page' => 25])->json('data'))->firstWhere('Username', $username)
            : null;
        $staff = $cari('/staff', 'staff.read');
        $dosen = $cari('/dosen', 'dosen.read');

        return [
            'token' => $token,
            'api_id' => $user['id'],
            'username' => $user['username'],
            'name' => $staff['Nama'] ?? $dosen['Nama'] ?? $user['name'],
            'roles' => $user['roles'] ?? [],
            'scopes' => $scopes,
            'nip' => ($nip = trim($staff['Nip'] ?? '')) && $nip !== '-' ? $nip : null,
            'nidn' => ($nidn = trim($dosen['NIDN'] ?? '')) && $nidn !== '-' ? $nidn : null,
            // Tanpa scope read tidak bisa dipastikan → anggap terdaftar (API sendiri menolak akun nonaktif)
            'terdaftar' => (bool) ($staff || $dosen) || !array_intersect(['*', 'staff.read', 'dosen.read'], $scopes),
            'divisi' => $staff['divisi'] ?? null,
            'level' => $staff['level'] ?? null,
        ];
    }

    // Cabut token di server eUniversity (logout). Gagal jaringan diabaikan.
    public function logout(?string $token): void
    {
        if ($token) {
            rescue(fn () => $this->http($token, 1)->post('/auth/logout'), report: false);
        }
    }

    /**
     * Ambil seluruh baris (semua halaman) beberapa endpoint.
     * @return array<string, array> [path => rows]
     */
    public function ambilSemua(array $paths, array $query = []): array
    {
        $hasil = [];
        foreach ($paths as $path) {
            $hasil[$path] = [];
            $page = 1;
            do {
                $res = $this->cek($this->http($this->token(), 8, 3000)->get($path, $query + ['per_page' => 100, 'page' => $page]));
                array_push($hasil[$path], ...($res->json('data') ?? []));
            } while ($page++ < $res->json('meta.last_page', 1));
        }
        return $hasil;
    }

    // Cek koneksi: jumlah total baris endpoint (1 permintaan kecil)
    public function ambilSatuHalaman(string $path): int
    {
        return (int) $this->cek($this->http($this->token())->get($path, ['per_page' => 1, 'page' => 1]))->json('meta.total', 0);
    }

    /**
     * Tulis ke API (POST/PATCH/DELETE). Sukses → data respons.
     * Ditolak API (403/404/409/422) → ApiDitolak berisi pesan & error per kolom.
     */
    public function tulis(string $method, string $path, array $data = []): ?array
    {
        $res = $this->http($this->token())->send($method, $path, $method === 'DELETE' ? [] : ['json' => $data]);
        if (in_array($res->status(), [403, 404, 409, 422])) {
            throw new ApiDitolak($res->json('message') ?? 'Ditolak server eUniversity', (array) $res->json('errors'));
        }
        return $this->cek($res)->json('data');
    }

    // 401 = token kedaluwarsa/dicabut → user harus login ulang
    private function cek($res)
    {
        if ($res->status() === 401) {
            throw new SesiApiHabis();
        }
        return $res->throw();
    }

    // Peran HRD dari daftar role API; null = tidak berhak (mis. mahasiswa)
    public static function peran(array $roles): ?string
    {
        foreach (config('services.pct.peran') as $peran => $cocok) {
            if (array_intersect($cocok, $roles)) {
                return $peran;
            }
        }
        return null;
    }
}
