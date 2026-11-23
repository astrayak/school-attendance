<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Siswa extends ModelDasar
{
    protected $table = 'siswa';

    protected $primaryKey = 'id_siswa';

    protected $fillable = [
        'id_pengguna',
        'nis',
        'nisn',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'nomor_telepon',
        'nama_orang_tua',
        'nomor_telepon_orang_tua',
        'status_siswa',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'id_siswa', 'id_siswa');
    }

    public function riwayatKelasAktif(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'id_siswa', 'id_siswa')
            ->where('status_aktif', true)
            ->latest('tanggal_mulai');
    }

    public function kelasSaatIni(): ?Kelas
    {
        return $this->riwayatKelasAktif()->with('kelas')->first()?->kelas;
    }

    public function catatanAbsensi(): HasMany
    {
        return $this->hasMany(CatatanAbsensi::class, 'id_siswa', 'id_siswa');
    }

    public function kodeQr(): HasMany
    {
        return $this->hasMany(KodeQrSiswa::class, 'id_siswa', 'id_siswa');
    }

    public function kodeQrAktif(): HasOne
    {
        return $this->hasOne(KodeQrSiswa::class, 'id_siswa', 'id_siswa')
            ->where('status_aktif', true)
            ->latestOfMany('id_kode_qr');
    }

    public function petugasKonfirmasi(): HasMany
    {
        return $this->hasMany(PetugasKonfirmasi::class, 'id_siswa', 'id_siswa');
    }

    public function permintaanKonfirmasi(): HasMany
    {
        return $this->hasMany(PermintaanKonfirmasi::class, 'id_siswa_penerima', 'id_siswa');
    }

    public function ringkasanHarian(): HasMany
    {
        return $this->hasMany(RingkasanAbsensiHarian::class, 'id_siswa', 'id_siswa');
    }
}
