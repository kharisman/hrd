<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Lepas dari spmi (survei.nips): pegawai = staff eUniversity (hrd_staffs), tautan pakai staff_id.
return new class extends Migration
{
    public function up()
    {
        // Isian khusus HRD (tidak ditimpa sinkron API)
        Schema::table('staffs', function (Blueprint $table) {
            $table->unsignedBigInteger('atasan_id')->nullable()->after('level'); // hrd_staffs.id, untuk persetujuan cuti
            $table->string('status_kepegawaian')->nullable()->after('atasan_id');
            $table->string('pendidikan_terakhir')->nullable()->after('status_kepegawaian');
        });

        // Dosen yang juga pegawai: nips.id -> staffs.id lewat staffs.nip_id (diisi sinkron sebelumnya)
        Schema::table('dosens', fn (Blueprint $t) => $t->unsignedBigInteger('staff_id')->nullable()->unique()->after('api_id'));
        $staffByNip = DB::table('staffs')->whereNotNull('nip_id')->orderBy('id')->get(['id', 'nip_id'])->unique('nip_id')->pluck('id', 'nip_id');
        foreach (DB::table('dosens')->whereNotNull('nip_id')->get(['id', 'nip_id']) as $d) {
            DB::table('dosens')->where('id', $d->id)->update(['staff_id' => $staffByNip[$d->nip_id] ?? null]);
        }

        // Akun login: username eUniversity = username staff
        Schema::table('akuns', fn (Blueprint $t) => $t->unsignedBigInteger('staff_id')->nullable()->after('nip'));
        foreach (DB::table('akuns')->get(['id', 'username']) as $a) {
            DB::table('akuns')->where('id', $a->id)->update(['staff_id' => DB::table('staffs')->where('username', $a->username)->value('id')]);
        }

        Schema::table('cuti', fn (Blueprint $t) => $t->renameColumn('nip_id', 'staff_id'));
        Schema::table('dosens', fn (Blueprint $t) => $t->dropColumn('nip_id'));
        Schema::table('akuns', fn (Blueprint $t) => $t->dropColumn('nip_id'));
        Schema::table('staffs', fn (Blueprint $t) => $t->dropColumn('nip_id'));

        // Isinya hanya biodata salinan API (sudah ada di hrd_staffs)
        Schema::dropIfExists('pegawai_detail');
    }

    public function down()
    {
        throw new RuntimeException('Tidak bisa dikembalikan: koneksi ke spmi sudah dilepas.');
    }
};
