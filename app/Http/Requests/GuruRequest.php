<?php

namespace App\Http\Requests;

use App\Models\Guru;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuruRequest extends FormRequest
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
        /** @var Guru|null $guru */
        $guru = $this->route('guru');

        return [
            'nama_pengguna' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('pengguna', 'email')->ignore($guru?->id_pengguna, 'id_pengguna'),
            ],
            'kata_sandi' => [$guru ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'status_aktif' => ['required', 'boolean'],

            'nip' => [
                'nullable', 'string', 'max:30',
                Rule::unique('guru', 'nip')->ignore($guru?->id_guru, 'id_guru'),
            ],
            'nuptk' => [
                'nullable', 'string', 'max:30',
                Rule::unique('guru', 'nuptk')->ignore($guru?->id_guru, 'id_guru'),
            ],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'nomor_telepon' => ['nullable', 'string', 'max:25'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'status_guru' => ['required', Rule::in(['aktif', 'nonaktif'])],
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
            'status_guru' => 'status guru',
        ];
    }
}
