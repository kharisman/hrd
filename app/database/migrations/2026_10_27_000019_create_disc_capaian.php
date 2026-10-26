<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fase 4 dashboard (dulu tab "DISC ..." di Dashboard HRD & tab per dosen di Dashboard Dosen):
// - disc: peta karakter per orang (NIP): profil DISC + 4 skor komitmen + 4 skor kompetensi (1–4)
// - capaian_dosen: isi rapor dosen dalam satu tabel, dibedakan "jenis":
//   roadmap (rencana karier & capaiannya), sertifikat (sertifikasi/training), prestasi (HKI, buku, hibah, penghargaan),
//   publikasi, rekognisi (reviewer, narasumber, juri)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disc', function (Blueprint $t) {
            $t->id();
            $t->string('nip', 30)->unique();
            $t->string('tim')->nullable();     // kelompok pemetaan, mis. Prodi SI, SarPras
            $t->string('profil', 20)->nullable(); // mis. S / C-S
            foreach (['inisiatif', 'tanggung_jawab', 'totalitas', 'ekstra', 'pengalaman', 'sertifikasi', 'dukungan', 'proyek'] as $k) {
                $t->unsignedTinyInteger($k)->nullable();
            }
            $t->date('dinilai_at')->nullable();
            $t->timestamps();
        });
        Schema::create('capaian_dosen', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $t->string('jenis', 12);
            $t->string('tahun', 40)->nullable(); // apa adanya: 2024, "24-26 April 2024", ...
            $t->unsignedTinyInteger('bulan')->nullable();
            $t->string('judul', 500);
            $t->string('sumber')->nullable();     // penyelenggara / penerbit / jenis prestasi
            $t->string('status')->nullable();     // roadmap: Tercapai/Tidak Tercapai; publikasi: peringkat Sinta
            $t->string('keterangan', 500)->nullable();
            $t->string('link', 500)->nullable();
            $t->timestamps();
            $t->index(['dosen_id', 'jenis']);
        });
    }
};
