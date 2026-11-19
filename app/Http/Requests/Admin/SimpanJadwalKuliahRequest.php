<?php

namespace App\Http\Requests\Admin;

use App\Enums\JenisJadwal;
use Closure;
use Illuminate\Validation\Rule;

class SimpanJadwalKuliahRequest extends JadwalKuliahRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'kelas_mata_kuliah_id' => ['required', 'integer', Rule::exists('kelas_mata_kuliah', 'id')],
            'hari' => ['required', 'integer', 'min:0', 'max:6'],
            'jam_mulai' => ['required', 'date_format:H:i', 'not_in:00:00'],
            'jam_selesai' => [
                'required',
                'date_format:H:i',
                'not_in:00:00',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $mulai = $this->input('jam_mulai');

                    if (! is_string($value) || ! is_string($mulai) || $value <= $mulai) {
                        $fail('Jam selesai harus lebih besar dari jam mulai.');
                    }
                },
            ],
            'ruang' => ['nullable', 'string', 'max:50'],
            'jenis' => ['required', Rule::enum(JenisJadwal::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kelas_mata_kuliah_id.required' => 'Kelas mata kuliah wajib dipilih.',
            'kelas_mata_kuliah_id.exists' => 'Kelas mata kuliah tidak valid.',
            'hari.required' => 'Hari wajib dipilih.',
            'hari.integer' => 'Hari tidak valid.',
            'hari.min' => 'Hari tidak valid.',
            'hari.max' => 'Hari tidak valid.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'jam_mulai.not_in' => 'Jadwal tidak boleh dimulai pada tengah malam.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai harus HH:MM.',
            'jam_selesai.not_in' => 'Jadwal tidak boleh berakhir pada tengah malam.',
            'ruang.string' => 'Ruang harus berupa teks.',
            'ruang.max' => 'Ruang maksimal :max karakter.',
            'jenis.required' => 'Jenis jadwal wajib dipilih.',
            'jenis.enum' => 'Jenis jadwal tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        if (is_string($this->input('ruang'))) {
            $ruang = trim($this->input('ruang'));
            $normalisasi['ruang'] = $ruang === '' ? null : $ruang;
        }

        foreach (['jam_mulai', 'jam_selesai', 'jenis'] as $field) {
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
