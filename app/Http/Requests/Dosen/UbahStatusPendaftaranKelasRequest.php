<?php

namespace App\Http\Requests\Dosen;

use App\Enums\StatusPendaftaranKelas;
use Illuminate\Validation\Rule;

class UbahStatusPendaftaranKelasRequest extends KelasMataKuliahRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status_pendaftaran' => [
                'required',
                'string',
                Rule::in(array_column(StatusPendaftaranKelas::cases(), 'value')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status_pendaftaran.required' => 'Status pendaftaran wajib ditentukan.',
            'status_pendaftaran.string' => 'Status pendaftaran tidak valid.',
            'status_pendaftaran.in' => 'Status pendaftaran tidak dikenal.',
        ];
    }
}
