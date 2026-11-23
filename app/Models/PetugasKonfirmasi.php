<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetugasKonfirmasi extends ModelDasar
{
    protected $table = 'petugas_konfirmasi';

    protected $primaryKey = 'id_petugas_konfirmasi';

    protected $fillable = [
        'id_siswa',
        'id_kelas',
        'id_semester',
        'urutan_prioritas',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status_aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_aktif', true);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }

    public function permintaanKonfirmasi(): HasMany
    {
        return $this->hasMany(PermintaanKonfirmasi::class, 'id_petugas_konfirmasi', 'id_petugas_konfirmasi');
    }
}
