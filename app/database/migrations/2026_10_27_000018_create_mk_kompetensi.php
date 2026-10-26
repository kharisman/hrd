<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fase 3 dashboard dosen (dulu tab "Penguasaan MK" & "Kompetensi" di spreadsheet):
// - mk_inti + mk_dosen: matriks mata kuliah inti prodi per kurikulum × dosen yang menguasai
// - kompetensi: skill dosen per tahun (personal / jabatan), level sekarang vs target, rencana aksi
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mk_inti', function (Blueprint $t) {
            $t->id();
            $t->string('program_studi');
            $t->string('kurikulum', 20); // mis. 2026
            $t->string('nama');
            $t->string('bidang_ilmu')->nullable();
            $t->string('semester', 10)->nullable(); // Ganjil / Genap
            $t->string('irisan')->nullable();       // prodi lain yang juga punya MK ini, mis. "SI, IF, BD"
            $t->unsignedTinyInteger('kebutuhan')->default(2); // minimal dosen yang menguasai
            $t->string('solusi')->nullable();
            $t->unsignedSmallInteger('urutan')->default(0);
            $t->timestamps();
            $t->unique(['program_studi', 'kurikulum', 'nama']);
        });
        Schema::create('mk_dosen', function (Blueprint $t) {
            $t->foreignId('mk_inti_id')->constrained('mk_inti')->cascadeOnDelete();
            $t->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $t->primary(['mk_inti_id', 'dosen_id']);
        });
        Schema::create('kompetensi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $t->unsignedSmallInteger('tahun');
            $t->string('kelompok', 10); // personal / jabatan
            $t->string('skill');
            $t->unsignedTinyInteger('sekarang')->nullable(); // skala 1–5
            $t->unsignedTinyInteger('target')->nullable();
            $t->string('rencana')->nullable();
            $t->string('catatan')->nullable();
            $t->timestamps();
            $t->unique(['dosen_id', 'tahun', 'kelompok', 'skill']);
        });
    }
};
