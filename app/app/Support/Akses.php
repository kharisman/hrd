<?php

namespace App\Support;

use App\Models\Dosen;
use App\Models\Staff;

/**
 * Akses dashboard mengikuti data eUniversity (bukan isian HRD):
 * - role admin/superadmin                        → semua dashboard, semua prodi
 * - role rektor / warek1 / warek2                → semua dashboard, semua prodi (pimpinan)
 * - role kaprodi                                 → dashboard dosen, hanya prodi homebase-nya
 * Role = role akun saat login + role pegawai tersinkron (/role-map & /api-users).
 * ponytail: level jabatan /staff (Rektor, Wakil Rektor, Kaprodi) masih dipakai sebagai cadangan selama role belum diisi
 * di eUniversity; hapus cadangan ini setelah semua pimpinan & kaprodi punya role.
 */
class Akses
{
    private static array $cache = [];

    public static function pimpinan(): bool
    {
        return self::ingat('pimpinan', fn () => auth()->user()?->peran === 'admin'
            || self::roles()->intersect(['rektor', 'warek1', 'warek2'])->isNotEmpty()
            || self::level()->contains(fn ($l) => (bool) preg_match('/^(Rektor|Wakil Rektor)\b/i', (string) $l)));
    }

    public static function dashboardPegawai(): bool
    {
        return self::pimpinan();
    }

    public static function dashboardDosen(): bool
    {
        return self::prodiDosen() !== [];
    }

    // Prodi yang boleh dilihat di dashboard dosen: semua untuk pimpinan, homebase untuk kaprodi
    public static function prodiDosen(): array
    {
        return self::ingat('prodi', function () {
            if (self::pimpinan()) {
                return Dosen::daftarProdi();
            }
            $akun = auth()->user();
            $kaprodi = self::roles()->contains('kaprodi') || self::level()->contains(fn ($l) => strcasecmp((string) $l, 'Kaprodi') === 0);
            if (!$kaprodi) {
                return [];
            }
            $ids = self::staffIds();
            return Dosen::where('aktif', true)->where(fn ($q) => $q->where('id', $akun->dosen_id)->orWhereIn('staff_id', $ids))
                ->whereNotNull('program_studi')->pluck('program_studi')->unique()->intersect(Dosen::daftarProdi())->values()->all();
        });
    }

    public static function semuaProdi(): bool
    {
        return count(self::prodiDosen()) === count(Dosen::daftarProdi());
    }

    public static function bolehProdi(?string $prodi): bool
    {
        return $prodi !== null && in_array($prodi, self::prodiDosen(), true);
    }

    // Role eUniversity: dari akun (saat login) + semua akun staff orang ini (sinkron)
    private static function roles()
    {
        return self::ingat('roles', fn () => collect((array) auth()->user()?->roles)
            ->merge(Staff::whereIn('id', self::staffIds())->where('aktif', true)->pluck('roles')->flatten())->filter()->unique()->values());
    }

    // Level jabatan semua akun staff orang ini (NIP sama), dari eUniversity
    private static function level()
    {
        return self::ingat('level', fn () => Staff::whereIn('id', self::staffIds())->where('aktif', true)->pluck('level'));
    }

    private static function staffIds()
    {
        return self::ingat('staff', function () {
            $id = auth()->user()?->staff_id;
            $nip = $id ? Staff::whereKey($id)->value('nip') : null;
            return $nip ? Staff::where('nip', $nip)->pluck('id') : collect($id ? [$id] : []);
        });
    }

    // Disimpan per pengguna selama satu request (kunci memuat request, jadi tidak terbawa ke request berikutnya)
    private static function ingat(string $kunci, \Closure $f)
    {
        $kunci = spl_object_id(request()) . ':' . (auth()->id() ?? 0) . ":$kunci";
        return array_key_exists($kunci, self::$cache) ? self::$cache[$kunci] : self::$cache[$kunci] = $f();
    }
}
