<?php

namespace App\Models\Concerns;

use App\Models\Prodi;

// Kolom program_studi (label) selalu disertai prodi_id (prodi eUniversity) setiap kali disimpan
trait TautProdi
{
    protected static function bootTautProdi(): void
    {
        static::saving(function ($m) {
            if ($m->isDirty('program_studi') || ($m->program_studi && !$m->prodi_id)) {
                $m->prodi_id = Prodi::cari($m->program_studi)?->id;
            }
        });
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }
}
