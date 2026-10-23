<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Kode OTP login (hrd_otp). kode_hash = bcrypt; kode asli tidak pernah disimpan.
class Otp extends Model
{
    protected $connection = 'mysql';
    protected $table = 'otp';
    protected $guarded = ['id'];
    protected $casts = ['kedaluwarsa_at' => 'datetime', 'dipakai_at' => 'datetime'];
}
