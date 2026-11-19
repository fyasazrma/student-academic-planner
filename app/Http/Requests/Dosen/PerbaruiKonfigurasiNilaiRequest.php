<?php

namespace App\Http\Requests\Dosen;

class PerbaruiKonfigurasiNilaiRequest extends NilaiKelasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gunakan_tugas' => ['required', 'boolean'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
