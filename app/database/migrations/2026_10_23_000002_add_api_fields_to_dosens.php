<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->unsignedBigInteger('api_id')->nullable()->unique()->after('id'); // idDosen eUniversity
            $table->string('username')->nullable()->index()->after('nama');
            $table->string('jenis_kelamin', 1)->nullable()->after('username');
            $table->string('tempat_lahir')->nullable()->after('jenis_kelamin');
            $table->date('tgl_lahir')->nullable()->after('tempat_lahir');
            $table->text('alamat')->nullable()->after('no_hp');
            $table->date('mulai_kerja')->nullable()->after('alamat');
            $table->timestamp('disinkron_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->dropColumn(['api_id', 'username', 'jenis_kelamin', 'tempat_lahir', 'tgl_lahir', 'alamat', 'mulai_kerja', 'disinkron_at']);
        });
    }
};
