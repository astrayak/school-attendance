<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LayananFotoProfil
{
    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    /**
     * @return array<int, string>
     */
    public function aturan(): array
    {
        return ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }

    public function simpan(
        Pengguna $pengguna,
        UploadedFile $foto,
        string $namaModul,
        ?string $jenisData = null,
        int|string|null $idDataTerkait = null,
    ): string {
        $ekstensi = strtolower($foto->extension() ?: 'jpg');
        $namaBerkas = 'foto-'.$pengguna->id_pengguna.'-'.Str::lower(Str::random(12)).'.'.$ekstensi;

        $path = $foto->storeAs('foto-profil', $namaBerkas, 'public');
        $lama = $pengguna->foto_profil;

        $pengguna->update(['foto_profil' => $path]);

        if ($lama && $lama !== $path) {
            Storage::disk('public')->delete($lama);
        }

        $this->log->catat(
            $pengguna,
            'ubah_foto_profil',
            $namaModul,
            'Memperbarui foto profil.',
            $jenisData,
            $idDataTerkait,
            ['foto_profil' => $lama],
            ['foto_profil' => $path],
        );

        return $path;
    }
}