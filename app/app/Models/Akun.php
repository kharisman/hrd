<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

// User login via API eUniversity (guard "pegawai")
class Akun extends Authenticatable
{
    protected $connection = 'mysql';
    protected $guarded = ['id'];
    protected $casts = ['roles' => 'array', 'scopes' => 'array', 'last_login_at' => 'datetime'];

    // Punya semua scope API yang diminta? ('*' = semua)
    public function bisa(array $scopes): bool
    {
        $milik = $this->scopes ?? [];
        return in_array('*', $milik) || !array_diff($scopes, $milik);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    /**
     * Tautan akun login → [Staff|null, Dosen|null]. Username akun dosen eUniversity sering beda dengan username
     * master staff (mis. tri_octavian vs octafian), jadi cadangannya: NIP akun, lalu pegawai yang tertaut ke dosennya.
     */
    public static function tautan(string $username, ?string $nip = null): array
    {
        $dosen = Dosen::where('username', $username)->first();
        $staff = Staff::where('username', $username)->first()
            ?? Staff::utama($nip)
            ?? ($dosen?->staff_id ? Staff::utama(Staff::whereKey($dosen->staff_id)->value('nip')) ?? Staff::find($dosen->staff_id) : null);
        // Akun pegawai (mis. eko_dosentetap) → dosen aktif yang tertaut ke pegawai ber-NIP sama
        $dosen ??= $staff ? Dosen::whereIn('staff_id', Staff::where('nip', $staff->nip ?? '-')->pluck('id')->push($staff->id))->orderByDesc('aktif')->first() : null;
        return [$staff, $dosen];
    }
}
