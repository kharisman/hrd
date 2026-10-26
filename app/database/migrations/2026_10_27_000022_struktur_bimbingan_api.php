<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data API eUniversity yang sebelumnya belum dipakai HRD:
// - struktur: master divisi × level (/struktur) → pilihan & validasi divisi/level pegawai.
//   staffs.jabatan (impor Excel) dihapus: sama dengan level dari API.
// - bimbingan: dosen pembimbing PKL / proposal / skripsi per periode (/pkl-skripsi/{jenis}/{idKRS}), tanpa data pribadi mahasiswa
// - dosens.penguji: terdaftar sebagai dosen penguji (/pkl-skripsi/penguji)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('struktur', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('api_id')->unique();
            $t->unsignedInteger('id_divisi');
            $t->unsignedInteger('id_level');
            $t->string('divisi');
            $t->string('level');
            $t->boolean('aktif')->default(true);
            $t->timestamps();
            $t->unique(['id_divisi', 'id_level']);
        });
        Schema::create('bimbingan', function (Blueprint $t) {
            $t->id();
            $t->string('jenis', 10); // pkl / proposal / skripsi
            $t->unsignedBigInteger('api_id');
            $t->unsignedBigInteger('id_krs')->index();
            $t->string('periode', 30);
            $t->foreignId('dosen_id')->nullable()->constrained('dosens')->nullOnDelete();
            $t->string('pembimbing');   // nama dari API (dicocokkan ke dosen_id)
            $t->string('program_studi')->nullable();
            $t->string('judul', 500)->nullable();
            $t->timestamps();
            $t->unique(['jenis', 'api_id']);
        });
        Schema::table('dosens', fn (Blueprint $t) => $t->boolean('penguji')->default(false)->after('jumlah_pa'));
        if (Schema::hasColumn('staffs', 'jabatan')) {
            Schema::table('staffs', fn (Blueprint $t) => $t->dropColumn('jabatan'));
        }
    }
};
