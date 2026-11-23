<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_absensi', function (Blueprint $table) {
            $table->bigIncrements('id_audit_absensi');
            $table->unsignedBigInteger('id_catatan_absensi');
            $table->unsignedBigInteger('id_pengguna');
            $table->enum('status_sebelumnya', ['hadir', 'izin', 'sakit', 'alpha'])->nullable();
            $table->enum('status_sesudahnya', ['hadir', 'izin', 'sakit', 'alpha']);
            $table->string('alasan_perubahan', 255)->nullable();
            $table->dateTime('waktu_perubahan');
            $table->timestamp('dibuat_pada')->nullable();

            $table->index(['id_catatan_absensi', 'waktu_perubahan'], 'idx_audit_catatan_waktu');

            $table->foreign('id_catatan_absensi', 'fk_audit_catatan')
                ->references('id_catatan_absensi')->on('catatan_absensi')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_pengguna', 'fk_audit_pengguna')
                ->references('id_pengguna')->on('pengguna')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->bigIncrements('id_log_aktivitas');
            $table->unsignedBigInteger('id_pengguna');
            $table->string('jenis_aktivitas', 50);
            $table->string('nama_modul', 60);
            $table->string('deskripsi_aktivitas', 255);
            $table->string('jenis_data', 60)->nullable();
            $table->string('id_data_terkait', 50)->nullable();
            $table->json('data_sebelumnya')->nullable();
            $table->json('data_sesudahnya')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->dateTime('waktu_aktivitas');
            $table->timestamp('dibuat_pada')->nullable();

            $table->index(['id_pengguna', 'waktu_aktivitas'], 'idx_log_pengguna_waktu');
            $table->index(['nama_modul', 'waktu_aktivitas'], 'idx_log_modul_waktu');
            $table->index(['jenis_aktivitas', 'waktu_aktivitas'], 'idx_log_jenis_waktu');

            $table->foreign('id_pengguna', 'fk_log_pengguna')
                ->references('id_pengguna')->on('pengguna')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
        Schema::dropIfExists('audit_absensi');
    }
};
