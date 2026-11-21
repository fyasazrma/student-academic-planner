<?php

namespace App\Http\Requests\Mahasiswa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Otorisasi dasar aksi progres materi. Otorisasi kelas/material dilakukan di
 * service berdasarkan identitas mahasiswa yang login.
 */
class ProgresMateriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMahasiswa() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
