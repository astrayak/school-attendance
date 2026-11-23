<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends ModelDasar
{
    protected $table = 'guru';

    protected $primaryKey = 'id_guru';

    protected $fillable = [
        'id_pengguna',
        'nip',
        'nuptk',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'nomor_telepon',
        'alamat',
        'status_guru',
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

    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_kelas', 'id_guru');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(PenugasanGuru::class, 'id_guru', 'id_guru');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'id_guru', 'id_guru');
    }

    public function jadwalPiket(): HasMany
    {
        return $this->hasMany(JadwalGuruPiket::class, 'id_guru', 'id_guru');
    }

    public function sesiPenanggungJawab(): HasMany
    {
        return $this->hasMany(SesiAbsensi::class, 'id_guru_penanggung_jawab', 'id_guru');
    }

    public function sesiPelaksana(): HasMany
    {
        return $this->hasMany(SesiAbsensi::class, 'id_guru_pelaksana', 'id_guru');
    }
}
