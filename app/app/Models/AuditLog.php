<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'mysql';
    protected $table = 'audit_log';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['data' => 'array', 'created_at' => 'datetime'];

    const RAHASIA = ['password', 'password_confirmation', '_token', '_method', 'kode', 'token', 'api_key'];

    public static function catat(string $aksi, ?\Illuminate\Http\Request $r = null): void
    {
        $r ??= request();
        $data = collect($r->except(self::RAHASIA))->map(fn ($v) => is_string($v) ? \Illuminate\Support\Str::limit($v, 300) : $v)
            ->merge(collect($r->allFiles())->map(fn ($f) => is_array($f) ? collect($f)->map->getClientOriginalName()->all() : $f->getClientOriginalName()))
            ->all();
        rescue(fn () => static::create([
            'akun_id' => auth()->id(), 'nama' => auth()->user()?->name, 'aksi' => $aksi, 'metode' => $r->method(),
            'url' => \Illuminate\Support\Str::limit($r->fullUrl(), 490, ''), 'data' => $data ?: null, 'ip' => $r->ip(),
        ]), report: true);
    }
}
