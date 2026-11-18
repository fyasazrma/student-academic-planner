<?php

namespace App\Http\Requests\Mahasiswa;

use Illuminate\Foundation\Http\FormRequest;

class CariKelasRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.string' => 'Kata kunci harus berupa teks.',
            'q.max' => 'Kata kunci maksimal :max karakter.',
        ];
    }
}
