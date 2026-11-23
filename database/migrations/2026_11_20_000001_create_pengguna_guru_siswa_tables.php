<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->bigIncrements('id_pengguna');
            $table->string('nama_pengguna', 120);
            $table->string('email', 150)->unique();
            $table->string('kata_sandi', 255);
            $table->enum('peran', ['admin', 'guru', 'siswa'])->default('siswa');
            $table->boolean('status_aktif')->default(true);
            $table->string('foto_profil', 255)->nullable();
            $table->string('token_ingat', 100)->nullable();
            $table->timestamp('terakhir_login')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->index(['peran', 'status_aktif'], 'idx_pengguna_peran_status');
        });

        Schema::create('guru', function (Blueprint $table) {
            $table->bigIncrements('id_guru');
            $table->unsignedBigInteger('id_pengguna')->unique();
            $table->string('nip', 30)->nullable()->unique();
            $table->string('nuptk', 30)->nullable()->unique();
            $table->string('nama_lengkap', 150);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nomor_telepon', 25)->nullable();
            $table->text('alamat')->nullable();
            $table->enum('status_guru', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_pengguna', 'fk_guru_pengguna')
                ->references('id_pengguna')->on('pengguna')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->bigIncrements('id_siswa');
            $table->unsignedBigInteger('id_pengguna')->unique();
            $table->string('nis', 30)->unique();
            $table->string('nisn', 30)->nullable()->unique();
            $table->string('nama_lengkap', 150);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->string('nomor_telepon', 25)->nullable();
            $table->string('nama_orang_tua', 150)->nullable();
            $table->string('nomor_telepon_orang_tua', 25)->nullable();
            $table->enum('status_siswa', ['aktif', 'lulus', 'pindah', 'nonaktif'])->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_pengguna', 'fk_siswa_pengguna')
                ->references('id_pengguna')->on('pengguna')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('guru');
        Schema::dropIfExists('pengguna');
    }
};
