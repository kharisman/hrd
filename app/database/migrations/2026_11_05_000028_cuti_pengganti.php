<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Cuti: pegawai pengganti yang bisa dihubungi selama pengaju cuti (isian tipe "pegawai")
return new class extends Migration
{
    public function up(): void
    {
        $isian = json_decode(DB::table('jenis_pengajuan')->where('kode', 'cuti')->value('isian') ?? '[]', true);
        if (!collect($isian)->contains('key', 'pengganti')) {
            array_splice($isian, 1, 0, [['key' => 'pengganti', 'label' => 'Pegawai pengganti (dihubungi selama cuti)', 'tipe' => 'pegawai', 'wajib' => false, 'opsi' => []]]);
            DB::table('jenis_pengajuan')->where('kode', 'cuti')->update(['isian' => json_encode($isian)]);
        }
    }

    public function down(): void
    {
        $isian = collect(json_decode(DB::table('jenis_pengajuan')->where('kode', 'cuti')->value('isian') ?? '[]', true))->reject(fn ($f) => $f['key'] === 'pengganti')->values();
        DB::table('jenis_pengajuan')->where('kode', 'cuti')->update(['isian' => $isian->toJson()]);
    }
};
