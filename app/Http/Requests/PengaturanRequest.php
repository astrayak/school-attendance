<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kunci_absensi_otomatis' => $this->boolean('kunci_absensi_otomatis'),
            'pengalihan_konfirmasi_otomatis' => $this->boolean('pengalihan_konfirmasi_otomatis'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'menit_buka_sebelum_selesai' => ['required', 'integer', 'min:0', 'max:120'],
            'menit_tutup_setelah_selesai' => ['required', 'integer', 'min:0', 'max:120'],
            'menit_batas_konfirmasi' => ['required', 'integer', 'min:1', 'max:120'],
            'detik_per_alihan_konfirmasi' => ['required', 'integer', 'min:10', 'max:3600'],
            'zona_waktu' => ['required', 'timezone'],
            'kunci_absensi_otomatis' => ['required', 'boolean'],
            'pengalihan_konfirmasi_otomatis' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'menit_buka_sebelum_selesai' => 'menit buka sebelum selesai',
            'menit_tutup_setelah_selesai' => 'menit tutup setelah selesai',
            'menit_batas_konfirmasi' => 'menit batas konfirmasi',
            'detik_per_alihan_konfirmasi' => 'detik per alihan konfirmasi',
            'zona_waktu' => 'zona waktu',
            'kunci_absensi_otomatis' => 'kunci absensi otomatis',
            'pengalihan_konfirmasi_otomatis' => 'pengalihan konfirmasi otomatis',
        ];
    }
}
