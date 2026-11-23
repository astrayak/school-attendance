<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ringkasan_absensi_harian', function (Blueprint $table) {
            $table->bigIncrements('id_ringkasan_harian');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas')->nullable();
            $table->date('tanggal');
            $table->unsignedTinyInteger('jumlah_mapel_dijadwalkan')->default(0);
            $table->unsignedTinyInteger('jumlah_hadir')->default(0);
            $table->unsignedTinyInteger('jumlah_izin')->default(0);
            $table->unsignedTinyInteger('jumlah_sakit')->default(0);
            $table->unsignedTinyInteger('jumlah_alpha')->default(0);
            $table->unsignedTinyInteger('jumlah_belum_diisi')->default(0);
            $table->enum('status_harian', [
                'hadir_penuh',
                'parsial',
                'izin_penuh',
                'sakit_penuh',
                'alpha_penuh',
                'campuran_non_hadir',
                'belum_lengkap',
            ]);
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_siswa', 'tanggal'], 'uq_ringkasan_siswa_tanggal');
            $table->index(['tanggal', 'status_harian'], 'idx_ringkasan_tanggal_status');
            $table->index(['id_kelas', 'tanggal'], 'idx_ringkasan_kelas_tanggal');

            $table->foreign('id_siswa', 'fk_ringkasan_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_kelas', 'fk_ringkasan_kelas')
                ->references('id_kelas')->on('kelas')
                ->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('pengaturan_sistem', function (Blueprint $table) {
            $table->bigIncrements('id_pengaturan');
            $table->string('kunci', 80);
            $table->text('nilai')->nullable();
            $table->enum('tipe', ['angka', 'teks', 'boolean', 'json'])->default('teks');
            $table->string('deskripsi', 255)->nullable();
            $table->date('tanggal_berlaku');
            $table->boolean('status_aktif')->default(true);
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['kunci', 'tanggal_berlaku'], 'uq_pengaturan_kunci_tanggal');
            $table->index(['kunci', 'status_aktif', 'tanggal_berlaku'], 'idx_pengaturan_kunci_aktif');

            $table->foreign('dibuat_oleh', 'fk_pengaturan_pembuat')
                ->references('id_pengguna')->on('pengguna')
                ->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->bigIncrements('id_notifikasi');
            $table->unsignedBigInteger('id_pengguna');
            $table->string('judul', 150);
            $table->text('pesan');
            $table->enum('jenis', ['absensi', 'konfirmasi', 'pemeriksaan', 'sistem'])->default('sistem');
            $table->string('url', 255)->nullable();
            $table->boolean('sudah_dibaca')->default(false);
            $table->dateTime('dibaca_pada')->nullable();
            $table->timestamp('dibuat_pada')->nullable();

            $table->index(['id_pengguna', 'sudah_dibaca', 'dibuat_pada'], 'idx_notifikasi_pengguna_baca');

            $table->foreign('id_pengguna', 'fk_notifikasi_pengguna')
                ->references('id_pengguna')->on('pengguna')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('pengaturan_sistem');
        Schema::dropIfExists('ringkasan_absensi_harian');
    }
};
