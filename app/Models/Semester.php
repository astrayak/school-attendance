<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends ModelDasar
{
    protected $table = 'semester';

    protected $primaryKey = 'id_semester';

    protected $fillable = [
        'id_tahun_ajaran',
        'nama_semester',
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

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function penugasanGuru(): HasMany
    {
        return $this->hasMany(PenugasanGuru::class, 'id_semester', 'id_semester');
    }

    public function versiJadwal(): HasMany
    {
        return $this->hasMany(VersiJadwal::class, 'id_semester', 'id_semester');
    }

    public function jadwalPiket(): HasMany
    {
        return $this->hasMany(JadwalGuruPiket::class, 'id_semester', 'id_semester');
    }

    public function petugasKonfirmasi(): HasMany
    {
        return $this->hasMany(PetugasKonfirmasi::class, 'id_semester', 'id_semester');
    }
}
