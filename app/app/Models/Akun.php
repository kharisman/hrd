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
}
