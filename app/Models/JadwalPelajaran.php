<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalPelajaran extends ModelDasar
{
    protected $table = 'jadwal_pelajaran';

    protected $primaryKey = 'id_jadwal';

    protected $fillable = [
        'id_versi_jadwal',
        'id_kelas',
        'id_mata_pelajaran',
        'id_guru',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruang_kelas',
        'urutan_pelajaran',
        'status_jadwal',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_jadwal', 'aktif');
    }

    public function versiJadwal(): BelongsTo
    {
        return $this->belongsTo(VersiJadwal::class, 'id_versi_jadwal', 'id_versi_jadwal');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'id_mata_pelajaran', 'id_mata_pelajaran');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function sesiAbsensi(): HasMany
    {
        return $this->hasMany(SesiAbsensi::class, 'id_jadwal', 'id_jadwal');
    }
}
