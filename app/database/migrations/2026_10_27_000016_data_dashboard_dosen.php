<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data kepegawaian yang tidak ada di eUniversity (dulu dikelola HRD di spreadsheet "Dashboard HRD"):
// - staffs: jabatan struktural, tanggal keluar (turn over), akhir kontrak/SK
// - dosens: TMT sebagai dosen (dasar "jafung seharusnya")
// - rasio_prodi: jumlah mahasiswa per prodi per tahun ajaran untuk rasio dosen : mahasiswa
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staffs', function (Blueprint $t) {
            $t->string('jabatan')->nullable()->after('level');
            $t->date('tanggal_keluar')->nullable()->after('mulai_kerja');
            $t->date('kontrak_berakhir')->nullable()->after('tanggal_keluar');
        });
        Schema::table('dosens', fn (Blueprint $t) => $t->date('tmt_dosen')->nullable()->after('mulai_kerja'));
        Schema::create('rasio_prodi', function (Blueprint $t) {
            $t->id();
            $t->string('program_studi');
            $t->string('tahun_ajaran', 9); // 2026/2027
            $t->unsignedInteger('jumlah_mahasiswa');
            $t->unsignedTinyInteger('target')->default(22); // maks. mahasiswa per dosen
            $t->timestamps();
            $t->unique(['program_studi', 'tahun_ajaran']);
        });
    }
};
