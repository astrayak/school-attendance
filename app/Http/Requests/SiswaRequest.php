<?php

namespace App\Http\Requests;

use App\Models\Siswa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status_aktif' => $this->boolean('status_aktif'),
        ]);

        if (! $this->filled('kata_sandi')) {
            $this->request->remove('kata_sandi');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Siswa|null $siswa */
        $siswa = $this->route('siswa');
        $idPengguna = $siswa?->id_pengguna;

        return [
            'nama_pengguna' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('pengguna', 'email')->ignore($idPengguna, 'id_pengguna'),
            ],
            'kata_sandi' => [$siswa ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'status_aktif' => ['required', 'boolean'],

            'nis' => [
                'required', 'string', 'max:30',
                Rule::unique('siswa', 'nis')->ignore($siswa?->id_siswa, 'id_siswa'),
            ],
            'nisn' => [
                'nullable', 'string', 'max:30',
                Rule::unique('siswa', 'nisn')->ignore($siswa?->id_siswa, 'id_siswa'),
            ],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'agama' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'nomor_telepon' => ['nullable', 'string', 'max:25'],
            'nama_orang_tua' => ['nullable', 'string', 'max:150'],
            'nomor_telepon_orang_tua' => ['nullable', 'string', 'max:25'],
            'status_siswa' => ['required', Rule::in(['aktif', 'lulus', 'pindah', 'nonaktif'])],

            'id_kelas' => ['nullable', 'integer', 'exists:kelas,id_kelas'],
            'tanggal_mulai_kelas' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_pengguna' => 'nama pengguna',
            'kata_sandi' => 'kata sandi',
            'status_aktif' => 'status akun',
            'nama_lengkap' => 'nama lengkap',
            'jenis_kelamin' => 'jenis kelamin',
            'tanggal_lahir' => 'tanggal lahir',
            'nomor_telepon' => 'nomor telepon',
            'status_siswa' => 'status siswa',
            'id_kelas' => 'kelas',
            'tanggal_mulai_kelas' => 'tanggal mulai kelas',
        ];
    }
}
