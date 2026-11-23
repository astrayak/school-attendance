<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->bigIncrements('id_tahun_ajaran');
            $table->string('nama_tahun_ajaran', 20)->unique();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('status_aktif')->default(false);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->index('status_aktif', 'idx_tahun_ajaran_aktif');
        });

        Schema::create('semester', function (Blueprint $table) {
            $table->bigIncrements('id_semester');
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->enum('nama_semester', ['ganjil', 'genap']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('status_aktif')->default(false);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_tahun_ajaran', 'nama_semester'], 'uq_semester_tahun_nama');
            $table->index('status_aktif', 'idx_semester_aktif');

            $table->foreign('id_tahun_ajaran', 'fk_semester_tahun_ajaran')
                ->references('id_tahun_ajaran')->on('tahun_ajaran')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->bigIncrements('id_kelas');
            $table->string('nama_kelas', 50);
            $table->string('tingkat', 10);
            $table->string('jurusan', 50)->nullable();
            $table->unsignedBigInteger('wali_kelas')->nullable();
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->enum('status_kelas', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['nama_kelas', 'id_tahun_ajaran'], 'uq_kelas_nama_tahun');
            $table->index(['id_tahun_ajaran', 'status_kelas'], 'idx_kelas_tahun_status');

            $table->foreign('wali_kelas', 'fk_kelas_wali')
                ->references('id_guru')->on('guru')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_tahun_ajaran', 'fk_kelas_tahun_ajaran')
                ->references('id_tahun_ajaran')->on('tahun_ajaran')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('riwayat_kelas_siswa', function (Blueprint $table) {
            $table->bigIncrements('id_riwayat_kelas');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_siswa', 'id_kelas', 'id_tahun_ajaran'], 'uq_riwayat_siswa_kelas_tahun');
            $table->index(['id_siswa', 'status_aktif'], 'idx_riwayat_siswa_aktif');
            $table->index(['id_kelas', 'status_aktif'], 'idx_riwayat_kelas_aktif');

            $table->foreign('id_siswa', 'fk_riwayat_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_kelas', 'fk_riwayat_kelas')
                ->references('id_kelas')->on('kelas')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_tahun_ajaran', 'fk_riwayat_tahun_ajaran')
                ->references('id_tahun_ajaran')->on('tahun_ajaran')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelas_siswa');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('semester');
        Schema::dropIfExists('tahun_ajaran');
    }
};
