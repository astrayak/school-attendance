<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends ModelDasar
{
    protected $table = 'kelas';

    protected $primaryKey = 'id_kelas';

    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'jurusan',
        'wali_kelas',
        'id_tahun_ajaran',
        'status_kelas',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'wali_kelas', 'id_guru');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function riwayatSiswa(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'id_kelas', 'id_kelas');
    }

    public function penugasanGuru(): HasMany
    {
        return $this->hasMany(PenugasanGuru::class, 'id_kelas', 'id_kelas');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'id_kelas', 'id_kelas');
    }

    public function petugasKonfirmasi(): HasMany
    {
        return $this->hasMany(PetugasKonfirmasi::class, 'id_kelas', 'id_kelas');
    }

    public function ringkasanHarian(): HasMany
    {
        return $this->hasMany(RingkasanAbsensiHarian::class, 'id_kelas', 'id_kelas');
    }
}
