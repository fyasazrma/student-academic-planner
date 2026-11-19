<?php

namespace App\Http\Requests\Dosen;

class PerbaruiKelasMataKuliahRequest extends KelasMataKuliahRequest
{
    /**
     * Identitas kelas dikunci setelah dibuat. Hanya nama dan kapasitas yang
     * dapat diubah dari formulir edit.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nama_kelas' => ['nullable', 'string', 'max:100'],
            'kapasitas' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_kelas.string' => 'Nama kelas harus berupa teks.',
            'nama_kelas.max' => 'Nama kelas maksimal :max karakter.',
            'kapasitas.integer' => 'Kapasitas harus berupa bilangan bulat.',
            'kapasitas.min' => 'Kapasitas minimal :min.',
            'kapasitas.max' => 'Kapasitas maksimal :max.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        foreach (['nama_kelas', 'kapasitas'] as $field) {
            $nilai = $this->input($field);

            if (is_string($nilai)) {
                $normalisasi[$field] = trim($nilai);
            }
        }

        if ($normalisasi !== []) {
            $this->merge($normalisasi);
        }
    }
}
