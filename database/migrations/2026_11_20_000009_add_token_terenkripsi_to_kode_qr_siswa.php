<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kode_qr_siswa', function (Blueprint $table) {
            // Token disimpan terenkripsi hanya agar QR milik siswa dapat ditampilkan kembali.
            // Validasi selalu memakai token_hash (SHA-256), bukan nilai terenkripsi ini.
            $table->text('token_terenkripsi')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('kode_qr_siswa', function (Blueprint $table) {
            $table->dropColumn('token_terenkripsi');
        });
    }
};
