<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_absensi', function (Blueprint $table) {
            $table->bigIncrements('id_catatan_absensi');
            $table->unsignedBigInteger('id_sesi_absensi');
            $table->unsignedBigInteger('id_siswa');
            $table->enum('status_kehadiran', ['hadir', 'izin', 'sakit', 'alpha']);
            $table->enum('metode_absensi', ['manual', 'qr_code']);
            $table->dateTime('waktu_kehadiran')->nullable();
            $table->unsignedBigInteger('id_pengguna_pencatat');
            $table->string('keterangan', 255)->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_sesi_absensi', 'id_siswa'], 'uq_catatan_sesi_siswa');
            $table->index(['id_sesi_absensi', 'status_kehadiran'], 'idx_catatan_sesi_status');
            $table->index(['id_siswa', 'status_kehadiran'], 'idx_catatan_siswa_status');

            $table->foreign('id_sesi_absensi', 'fk_catatan_sesi')
                ->references('id_sesi_absensi')->on('sesi_absensi')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_siswa', 'fk_catatan_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_pengguna_pencatat', 'fk_catatan_pencatat')
                ->references('id_pengguna')->on('pengguna')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('kode_qr_siswa', function (Blueprint $table) {
            $table->bigIncrements('id_kode_qr');
            $table->unsignedBigInteger('id_siswa');
            $table->char('token_hash', 64)->unique();
            $table->boolean('status_aktif')->default(true);
            $table->dateTime('tanggal_dibuat');
            $table->dateTime('tanggal_dicabut')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->index(['id_siswa', 'status_aktif'], 'idx_qr_siswa_aktif');

            $table->foreign('id_siswa', 'fk_qr_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_qr_siswa');
        Schema::dropIfExists('catatan_absensi');
    }
};
