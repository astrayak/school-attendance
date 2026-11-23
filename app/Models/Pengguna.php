<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    protected $fillable = [
        'nama_pengguna',
        'email',
        'kata_sandi',
        'peran',
        'status_aktif',
        'foto_profil',
        'token_ingat',
        'terakhir_login',
    ];

    protected $hidden = [
        'kata_sandi',
        'token_ingat',
    ];

    protected function casts(): array
    {
        return [
            'kata_sandi' => 'hashed',
            'status_aktif' => 'boolean',
            'terakhir_login' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    public function getFotoProfilUrlAttribute(): ?string
    {
        return $this->foto_profil
            ? asset('storage/'.ltrim($this->foto_profil, '/'))
            : null;
    }

    public function getRememberTokenName(): string
    {
        return 'token_ingat';
    }

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class, 'id_pengguna', 'id_pengguna');
    }

    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class, 'id_pengguna', 'id_pengguna');
    }

    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'id_pengguna', 'id_pengguna');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_pengguna', 'id_pengguna');
    }

    public function isAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    public function isGuru(): bool
    {
        return $this->peran === 'guru';
    }

    public function isSiswa(): bool
    {
        return $this->peran === 'siswa';
    }
}
