<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petugas_konfirmasi', function (Blueprint $table) {
            $table->bigIncrements('id_petugas_konfirmasi');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_semester');
            $table->unsignedSmallInteger('urutan_prioritas');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_siswa', 'id_kelas', 'id_semester'], 'uq_petugas_siswa_kelas_semester');
            $table->index(
                ['id_kelas', 'id_semester', 'status_aktif', 'urutan_prioritas'],
                'idx_petugas_kelas_semester_aktif'
            );

            $table->foreign('id_siswa', 'fk_petugas_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_kelas', 'fk_petugas_kelas')
                ->references('id_kelas')->on('kelas')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_semester', 'fk_petugas_semester')
                ->references('id_semester')->on('semester')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('permintaan_konfirmasi', function (Blueprint $table) {
            $table->bigIncrements('id_permintaan_konfirmasi');
            $table->unsignedBigInteger('id_sesi_absensi');
            $table->unsignedBigInteger('id_siswa_penerima');
            $table->unsignedBigInteger('id_petugas_konfirmasi')->nullable();
            $table->unsignedSmallInteger('urutan_penerima');
            $table->enum('status_permintaan', [
                'menunggu',
                'dikonfirmasi',
                'tidak_dikonfirmasi',
                'dialihkan',
                'kedaluwarsa',
            ])->default('menunggu');
            $table->dateTime('waktu_dikirim');
            $table->dateTime('waktu_direspons')->nullable();
            $table->dateTime('waktu_kedaluwarsa');
            $table->enum('jawaban', [
                'pembelajaran_berlangsung',
                'pembelajaran_tidak_berlangsung',
            ])->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_sesi_absensi', 'urutan_penerima'], 'uq_konfirmasi_sesi_urutan');
            $table->index(['id_siswa_penerima', 'status_permintaan'], 'idx_konfirmasi_siswa_status');
            $table->index(['status_permintaan', 'waktu_kedaluwarsa'], 'idx_konfirmasi_status_kedaluwarsa');

            $table->foreign('id_sesi_absensi', 'fk_konfirmasi_sesi')
                ->references('id_sesi_absensi')->on('sesi_absensi')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_siswa_penerima', 'fk_konfirmasi_siswa_penerima')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_petugas_konfirmasi', 'fk_konfirmasi_petugas')
                ->references('id_petugas_konfirmasi')->on('petugas_konfirmasi')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_konfirmasi');
        Schema::dropIfExists('petugas_konfirmasi');
    }
};
