<?php

namespace App\Http\Requests;

class KoreksiAbsensiRequest extends SimpanAbsensiRequest
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
        return array_merge(parent::rules(), [
            'alasan' => ['required', 'string', 'min:5', 'max:255'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'alasan' => 'alasan koreksi',
        ]);
    }
}
