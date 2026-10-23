<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuti extends Model
{
    protected $connection = 'mysql';
    protected $table = 'cuti';
    protected $guarded = ['id'];
    protected $casts = ['tgl_mulai' => 'date', 'tgl_selesai' => 'date'];

    public function jenisCuti()
    {
        return $this->belongsTo(JenisCuti::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function getHariAttribute()
    {
        return $this->tgl_mulai->diffInDays($this->tgl_selesai) + 1;
    }
}
