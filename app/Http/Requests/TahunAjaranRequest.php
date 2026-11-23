<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TahunAjaranRequest extends FormRequest
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
        $tahun = $this->route('tahun_ajaran');

        return [
            'nama_tahun_ajaran' => [
                'required', 'string', 'max:20',
                Rule::unique('tahun_ajaran', 'nama_tahun_ajaran')
                    ->ignore($tahun?->id_tahun_ajaran, 'id_tahun_ajaran'),
            ],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'status_aktif' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_tahun_ajaran' => 'nama tahun ajaran',
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
            'status_aktif' => 'status aktif',
        ];
    }
}
