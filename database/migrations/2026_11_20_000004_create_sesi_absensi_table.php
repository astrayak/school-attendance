<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_absensi', function (Blueprint $table) {
            $table->bigIncrements('id_sesi_absensi');
            $table->unsignedBigInteger('id_jadwal');
            $table->date('tanggal_pelajaran');
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->dateTime('waktu_absensi_dibuka');
            $table->dateTime('waktu_absensi_ditutup');
            $table->dateTime('waktu_batas_konfirmasi')->nullable();
            $table->unsignedBigInteger('id_guru_penanggung_jawab');
            $table->unsignedBigInteger('id_guru_pelaksana')->nullable();
            $table->enum('jenis_pelaksana', ['guru_pengampu', 'guru_piket'])->nullable();
            $table->enum('metode_absensi', ['manual', 'qr_code', 'gabungan'])->nullable();
            $table->enum('status_sesi', [
                'menunggu',
                'aktif',
                'menunggu_konfirmasi',
                'terkonfirmasi',
                'perlu_pemeriksaan',
                'belum_dikonfirmasi',
                'ditutup',
                'dibatalkan',
            ])->default('menunggu');
            $table->dateTime('waktu_disimpan')->nullable();
            $table->dateTime('diambil_alih_pada')->nullable();
            $table->string('alasan_pengambilalihan', 255)->nullable();
            $table->string('alasan_pembatalan', 255)->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_jadwal', 'tanggal_pelajaran'], 'uq_sesi_jadwal_tanggal');
            $table->index(['tanggal_pelajaran', 'status_sesi'], 'idx_sesi_tanggal_status');
            $table->index(['id_guru_pelaksana', 'tanggal_pelajaran'], 'idx_sesi_pelaksana_tanggal');

            $table->foreign('id_jadwal', 'fk_sesi_jadwal')
                ->references('id_jadwal')->on('jadwal_pelajaran')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_guru_penanggung_jawab', 'fk_sesi_guru_penanggung_jawab')
                ->references('id_guru')->on('guru')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_guru_pelaksana', 'fk_sesi_guru_pelaksana')
                ->references('id_guru')->on('guru')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_absensi');
    }
};
