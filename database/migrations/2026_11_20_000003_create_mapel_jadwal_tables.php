<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->bigIncrements('id_mata_pelajaran');
            $table->string('kode_mata_pelajaran', 20)->unique();
            $table->string('nama_mata_pelajaran', 100);
            $table->text('deskripsi')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('penugasan_guru', function (Blueprint $table) {
            $table->bigIncrements('id_penugasan_guru');
            $table->unsignedBigInteger('id_guru');
            $table->unsignedBigInteger('id_mata_pelajaran');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_semester');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(
                ['id_guru', 'id_mata_pelajaran', 'id_kelas', 'id_semester'],
                'uq_penugasan_guru_mapel_kelas_semester'
            );
            $table->index(['id_semester', 'status_aktif'], 'idx_penugasan_semester_aktif');

            $table->foreign('id_guru', 'fk_penugasan_guru')
                ->references('id_guru')->on('guru')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_mata_pelajaran', 'fk_penugasan_mapel')
                ->references('id_mata_pelajaran')->on('mata_pelajaran')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_kelas', 'fk_penugasan_kelas')
                ->references('id_kelas')->on('kelas')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_semester', 'fk_penugasan_semester')
                ->references('id_semester')->on('semester')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('versi_jadwal', function (Blueprint $table) {
            $table->bigIncrements('id_versi_jadwal');
            $table->unsignedBigInteger('id_semester');
            $table->string('nama_versi', 100);
            $table->date('tanggal_berlaku');
            $table->date('tanggal_berakhir')->nullable();
            $table->enum('status_versi', ['draf', 'aktif', 'arsip'])->default('draf');
            $table->unsignedBigInteger('dibuat_oleh');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_semester', 'nama_versi'], 'uq_versi_jadwal_semester_nama');
            $table->index(['id_semester', 'status_versi'], 'idx_versi_jadwal_semester_status');

            $table->foreign('id_semester', 'fk_versi_jadwal_semester')
                ->references('id_semester')->on('semester')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('dibuat_oleh', 'fk_versi_jadwal_pembuat')
                ->references('id_pengguna')->on('pengguna')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
            $table->bigIncrements('id_jadwal');
            $table->unsignedBigInteger('id_versi_jadwal');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_mata_pelajaran');
            $table->unsignedBigInteger('id_guru');
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('ruang_kelas', 50)->nullable();
            $table->unsignedSmallInteger('urutan_pelajaran')->default(1);
            $table->enum('status_jadwal', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(
                ['id_versi_jadwal', 'id_kelas', 'hari', 'jam_mulai'],
                'uq_jadwal_versi_kelas_hari_jam'
            );
            $table->index(['id_versi_jadwal', 'id_guru', 'hari'], 'idx_jadwal_versi_guru_hari');
            $table->index(['id_versi_jadwal', 'id_kelas', 'hari'], 'idx_jadwal_versi_kelas_hari');

            $table->foreign('id_versi_jadwal', 'fk_jadwal_versi')
                ->references('id_versi_jadwal')->on('versi_jadwal')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_kelas', 'fk_jadwal_kelas')
                ->references('id_kelas')->on('kelas')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_mata_pelajaran', 'fk_jadwal_mapel')
                ->references('id_mata_pelajaran')->on('mata_pelajaran')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_guru', 'fk_jadwal_guru')
                ->references('id_guru')->on('guru')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('jadwal_guru_piket', function (Blueprint $table) {
            $table->bigIncrements('id_jadwal_piket');
            $table->unsignedBigInteger('id_guru');
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']);
            $table->date('tanggal_mulai_berlaku');
            $table->date('tanggal_berakhir')->nullable();
            $table->unsignedBigInteger('id_semester');
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(
                ['id_semester', 'id_guru', 'hari', 'tanggal_mulai_berlaku'],
                'uq_piket_semester_guru_hari_mulai'
            );
            $table->index(['id_semester', 'hari', 'status_aktif'], 'idx_piket_semester_hari_aktif');

            $table->foreign('id_guru', 'fk_piket_guru')
                ->references('id_guru')->on('guru')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_semester', 'fk_piket_semester')
                ->references('id_semester')->on('semester')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_guru_piket');
        Schema::dropIfExists('jadwal_pelajaran');
        Schema::dropIfExists('versi_jadwal');
        Schema::dropIfExists('penugasan_guru');
        Schema::dropIfExists('mata_pelajaran');
    }
};
