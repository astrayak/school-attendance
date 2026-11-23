<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_pembaruan', function (Blueprint $table) {
            $table->bigIncrements('id_riwayat_pembaruan');
            $table->unsignedBigInteger('id_pengguna')->nullable();
            $table->string('jenis_tindakan', 80);
            $table->string('label_tindakan', 120);
            $table->enum('status', ['berhasil', 'gagal', 'sebagian']);
            $table->string('keterangan', 255)->nullable();
            $table->longText('keluaran')->nullable();
            $table->unsignedInteger('durasi_ms')->nullable();
            $table->timestamp('dibuat_pada')->nullable();

            $table->index(['status', 'dibuat_pada'], 'idx_riwayat_pembaruan_status_waktu');
            $table->index(['id_pengguna', 'dibuat_pada'], 'idx_riwayat_pembaruan_pengguna_waktu');

            $table->foreign('id_pengguna', 'fk_riwayat_pembaruan_pengguna')
                ->references('id_pengguna')->on('pengguna')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pembaruan');
    }
};