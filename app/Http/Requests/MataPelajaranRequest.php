<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MataPelajaranRequest extends FormRequest
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
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mapel = $this->route('mata_pelajaran');

        return [
            'kode_mata_pelajaran' => [
                'required', 'string', 'max:20',
                Rule::unique('mata_pelajaran', 'kode_mata_pelajaran')
                    ->ignore($mapel?->id_mata_pelajaran, 'id_mata_pelajaran'),
            ],
            'nama_mata_pelajaran' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'status_aktif' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kode_mata_pelajaran' => 'kode mata pelajaran',
            'nama_mata_pelajaran' => 'nama mata pelajaran',
            'status_aktif' => 'status aktif',
        ];
    }
}
