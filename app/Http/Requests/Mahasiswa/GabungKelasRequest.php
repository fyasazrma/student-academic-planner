<?php

namespace App\Http\Requests\Mahasiswa;

use Illuminate\Foundation\Http\FormRequest;

class GabungKelasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Role sudah dijamin oleh middleware route mahasiswa.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Identitas mahasiswa dan kelas sengaja tidak diterima dari request.
     * Controller hanya memakai kode_gabung dan auth()->id().
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'kode_gabung' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_gabung.required' => 'Kunci gabung wajib diisi.',
            'kode_gabung.string' => 'Kunci gabung harus berupa teks.',
            'kode_gabung.max' => 'Kunci gabung maksimal :max karakter.',
        ];
    }
}
