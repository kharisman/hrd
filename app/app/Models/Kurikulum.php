<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Master kurikulum dari API /kurikulum (dirujuk angkatan & MK inti lewat api_id)
class Kurikulum extends Model
{
    protected $connection = 'mysql';
    protected $table = 'kurikulum';
    protected $guarded = ['id'];
    protected $casts = ['aktif' => 'boolean'];
}
