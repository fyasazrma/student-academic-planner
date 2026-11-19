<?php

namespace App\Http\Requests\Dosen;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SimpanKelasMataKuliahRequest extends KelasMataKuliahRequest
{
    /**
     * Identitas pemilik dan status pendaftaran sengaja tidak diterima.
     * Keduanya ditetapkan server.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $mataKuliahId = $this->input('mata_kuliah_id');
        $tahunAkademikId = $this->input('tahun_akademik_id');

        return [
            'mata_kuliah_id' => [
                'required',
                'integer',
                Rule::exists('mata_kuliah', 'id')->where('aktif', true),
            ],
            'tahun_akademik_id' => [
                'required',
                'integer',
                Rule::exists('tahun_akademik', 'id')->where('aktif', true),
            ],
            'kode_kelas' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('kelas_mata_kuliah', 'kode_kelas')
                    ->where(fn ($query) => $query
                        ->where('mata_kuliah_id', $mataKuliahId)
                        ->where('tahun_akademik_id', $tahunAkademikId)),
            ],
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
            'mata_kuliah_id.required' => 'Mata kuliah wajib dipilih.',
            'mata_kuliah_id.integer' => 'Mata kuliah tidak valid.',
            'mata_kuliah_id.exists' => 'Mata kuliah tidak tersedia atau sudah dinonaktifkan.',
            'tahun_akademik_id.required' => 'Tahun akademik wajib dipilih.',
            'tahun_akademik_id.integer' => 'Tahun akademik tidak valid.',
            'tahun_akademik_id.exists' => 'Tahun akademik tidak tersedia atau sudah dinonaktifkan.',
            'kode_kelas.required' => 'Kode kelas wajib diisi.',
            'kode_kelas.string' => 'Kode kelas harus berupa teks.',
            'kode_kelas.max' => 'Kode kelas maksimal :max karakter.',
            'kode_kelas.regex' => 'Kode kelas hanya boleh memakai huruf, angka, titik, garis bawah, dan tanda hubung.',
            'kode_kelas.unique' => 'Kode kelas sudah digunakan pada mata kuliah dan tahun akademik ini.',
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

        foreach (['mata_kuliah_id', 'tahun_akademik_id', 'kapasitas'] as $field) {
            $nilai = $this->input($field);

            if (is_string($nilai) && trim($nilai) !== '') {
                $normalisasi[$field] = trim($nilai);
            }
        }

        foreach (['kode_kelas', 'nama_kelas'] as $field) {
            $nilai = $this->input($field);

            if (is_string($nilai)) {
                $normalisasi[$field] = trim($nilai);
            }
        }

        if (is_string($this->input('kode_kelas'))) {
            $normalisasi['kode_kelas'] = Str::upper($normalisasi['kode_kelas']);
        }

        if ($normalisasi !== []) {
            $this->merge($normalisasi);
        }
    }
}
