<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $kelas = $this->route('kelas');
        $idTahun = $this->input('id_tahun_ajaran');

        return [
            'nama_kelas' => [
                'required', 'string', 'max:50',
                Rule::unique('kelas', 'nama_kelas')
                    ->where(fn ($q) => $q->where('id_tahun_ajaran', $idTahun))
                    ->ignore($kelas?->id_kelas, 'id_kelas'),
            ],
            'tingkat' => ['required', 'string', 'max:10'],
            'jurusan' => ['nullable', 'string', 'max:50'],
            'wali_kelas' => ['nullable', 'integer', 'exists:guru,id_guru'],
            'id_tahun_ajaran' => ['required', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'status_kelas' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_kelas' => 'nama kelas',
            'tingkat' => 'tingkat',
            'wali_kelas' => 'wali kelas',
            'id_tahun_ajaran' => 'tahun ajaran',
            'status_kelas' => 'status kelas',
        ];
    }
}
