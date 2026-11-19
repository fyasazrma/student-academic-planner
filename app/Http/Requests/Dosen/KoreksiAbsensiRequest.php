<?php

namespace App\Http\Requests\Dosen;

class KoreksiAbsensiRequest extends AbsensiKelasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:hadir,izin,sakit,alfa'],
            'alasan' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status absensi wajib dipilih.',
            'status.in' => 'Status absensi tidak valid.',
            'alasan.required' => 'Alasan koreksi wajib diisi.',
            'alasan.max' => 'Alasan koreksi maksimal 255 karakter.',
        ];
    }
}
