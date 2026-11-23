<?php

namespace App\Http\Requests;

use App\Support\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['jam_mulai', 'jam_selesai'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => substr((string) $this->input($field), 0, 5)]);
            }
        }

        $jadwal = $this->route('jadwal');

        if ($jadwal) {
            $this->merge(['id_versi_jadwal' => $jadwal->id_versi_jadwal]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_versi_jadwal' => ['required', 'integer', 'exists:versi_jadwal,id_versi_jadwal'],
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'id_mata_pelajaran' => ['required', 'integer', 'exists:mata_pelajaran,id_mata_pelajaran'],
            'id_guru' => ['required', 'integer', 'exists:guru,id_guru'],
            'hari' => ['required', Rule::in(array_keys(Status::HARI))],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'ruang_kelas' => ['nullable', 'string', 'max:50'],
            'urutan_pelajaran' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_versi_jadwal' => 'versi jadwal',
            'id_kelas' => 'kelas',
            'id_mata_pelajaran' => 'mata pelajaran',
            'id_guru' => 'guru',
            'hari' => 'hari',
            'jam_mulai' => 'jam mulai',
            'jam_selesai' => 'jam selesai',
            'ruang_kelas' => 'ruang kelas',
            'urutan_pelajaran' => 'urutan pelajaran',
        ];
    }

    public function messages(): array
    {
        return [
            'jam_selesai.after' => 'Jam selesai harus lebih besar daripada jam mulai.',
        ];
    }
}
