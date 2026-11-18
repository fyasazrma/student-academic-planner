<?php

namespace App\Http\Requests\Admin;

use App\Models\MataKuliah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

abstract class MataKuliahRequest extends FormRequest
{
    /**
     * Role sudah dijamin oleh middleware admin, tetapi pemeriksaan ini
     * dipertahankan sebagai lapisan otorisasi di dalam FormRequest.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $uniks = Rule::unique('mata_kuliah', 'kode');
        $kunciDiabaikan = $this->kodeDiabaikan();

        if ($kunciDiabaikan !== null) {
            $uniks->ignore($kunciDiabaikan);
        }

        return [
            'kode' => [
                'required',
                'string',
                'max:20',
                $uniks,
            ],
            'nama' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'program_studi' => ['nullable', 'string', 'max:100'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode mata kuliah wajib diisi.',
            'kode.string' => 'Kode mata kuliah harus berupa teks.',
            'kode.max' => 'Kode mata kuliah maksimal :max karakter.',
            'kode.unique' => 'Kode mata kuliah sudah digunakan.',
            'nama.required' => 'Nama mata kuliah wajib diisi.',
            'nama.string' => 'Nama mata kuliah harus berupa teks.',
            'nama.max' => 'Nama mata kuliah maksimal :max karakter.',
            'sks.required' => 'SKS wajib diisi.',
            'sks.integer' => 'SKS harus berupa bilangan bulat.',
            'sks.min' => 'SKS minimal :min.',
            'sks.max' => 'SKS maksimal :max.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
            'deskripsi.max' => 'Deskripsi maksimal :max karakter.',
            'program_studi.string' => 'Program studi harus berupa teks.',
            'program_studi.max' => 'Program studi maksimal :max karakter.',
            'semester.integer' => 'Semester kurikuler harus berupa bilangan bulat.',
            'semester.min' => 'Semester kurikuler minimal :min.',
            'semester.max' => 'Semester kurikuler maksimal :max.',
        ];
    }

    /**
     * Kode selalu dinormalisasi sebelum validasi unik dijalankan.
     * Field nullable yang dikirim kosong diubah menjadi null agar sesuai schema.
     */
    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        if (is_string($this->input('kode'))) {
            $normalisasi['kode'] = Str::upper(trim($this->input('kode')));
        }

        foreach (['deskripsi', 'program_studi', 'semester'] as $field) {
            if ($this->input($field) === '') {
                $normalisasi[$field] = null;
            }
        }

        if ($normalisasi !== []) {
            $this->merge($normalisasi);
        }
    }

    /**
     * Primary key yang diabaikan saat memeriksa keunikan kode.
     */
    abstract protected function kodeDiabaikan(): mixed;

    protected function kunciDariRoute(): mixed
    {
        try {
            $parameter = $this->route('mata_kuliah');
        } catch (\Throwable) {
            return null;
        }

        if ($parameter instanceof MataKuliah) {
            return $parameter->getKey();
        }

        return is_numeric($parameter) ? (int) $parameter : null;
    }
}
