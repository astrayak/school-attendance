<?php

namespace App\Http\Requests;

use App\Models\TahunAjaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SemesterRequest extends FormRequest
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
        $semester = $this->route('semester');

        return [
            'id_tahun_ajaran' => ['required', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'nama_semester' => [
                'required', Rule::in(['ganjil', 'genap']),
                Rule::unique('semester', 'nama_semester')
                    ->where(fn ($q) => $q->where('id_tahun_ajaran', $this->input('id_tahun_ajaran')))
                    ->ignore($semester?->id_semester, 'id_semester'),
            ],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'status_aktif' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $tahun = TahunAjaran::find($this->input('id_tahun_ajaran'));

                if (! $tahun) {
                    return;
                }

                if ($this->date('tanggal_mulai')->lt($tahun->tanggal_mulai)
                    || $this->date('tanggal_selesai')->gt($tahun->tanggal_selesai)) {
                    $validator->errors()->add(
                        'tanggal_mulai',
                        'Rentang semester harus berada di dalam rentang tahun ajaran '
                            .$tahun->tanggal_mulai->format('d/m/Y').' - '.$tahun->tanggal_selesai->format('d/m/Y').'.'
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'id_tahun_ajaran' => 'tahun ajaran',
            'nama_semester' => 'nama semester',
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
            'status_aktif' => 'status aktif',
        ];
    }
}
