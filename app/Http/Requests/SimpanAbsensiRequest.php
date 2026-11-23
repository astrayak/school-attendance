<?php

namespace App\Http\Requests;

use App\Models\CatatanAbsensi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGuru() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'absensi' => ['required', 'array', 'min:1'],
            'absensi.*.status' => ['required', Rule::in([
                CatatanAbsensi::HADIR,
                CatatanAbsensi::IZIN,
                CatatanAbsensi::SAKIT,
                CatatanAbsensi::ALPHA,
            ])],
            'absensi.*.keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'absensi.required' => 'Data absensi wajib diisi.',
            'absensi.*.status.required' => 'Setiap siswa wajib memiliki status kehadiran.',
            'absensi.*.status.in' => 'Status kehadiran tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'absensi' => 'data absensi',
            'absensi.*.status' => 'status kehadiran',
            'absensi.*.keterangan' => 'keterangan',
        ];
    }
}
