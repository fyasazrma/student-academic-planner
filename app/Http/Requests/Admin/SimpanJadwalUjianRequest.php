<?php

namespace App\Http\Requests\Admin;

use App\Enums\JenisUjian;
use App\Enums\Semester;
use App\Models\TahunAkademik;
use Closure;
use Illuminate\Validation\Rule;

class SimpanJadwalUjianRequest extends JadwalUjianRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'jenis_ujian' => ['required', Rule::enum(JenisUjian::class)],
            'tahun_akademik_id' => ['required', 'integer', Rule::exists('tahun_akademik', 'id')],
            'semester' => [
                'required',
                Rule::enum(Semester::class),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $tahunId = $this->input('tahun_akademik_id');

                    if (! is_numeric($tahunId)) {
                        return;
                    }

                    $tahun = TahunAkademik::query()->find((int) $tahunId);
                    if ($tahun !== null && $tahun->semester?->value !== $value) {
                        $fail('Semester harus sesuai dengan tahun akademik yang dipilih.');
                    }
                },
            ],
            'judul' => [
                'required',
                'string',
                'max:200',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $jenisUjian = JenisUjian::tryFrom((string) $this->input('jenis_ujian'));

                    if ($jenisUjian !== null && ! str_contains(strtoupper((string) $value), $jenisUjian->label())) {
                        $fail('Judul harus sesuai dengan jenis ujian yang dipilih.');
                    }
                },
            ],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'waktu_mulai' => [
                'nullable',
                'date_format:H:i',
                'not_in:00:00',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && $value !== '') {
                        $selesai = $this->input('waktu_selesai');

                        if (! is_string($selesai) || $selesai === '') {
                            $fail('Jam selesai wajib diisi jika jam mulai diisi.');
                        }
                    }
                },
            ],
            'waktu_selesai' => [
                'nullable',
                'date_format:H:i',
                'not_in:00:00',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $mulai = $this->input('waktu_mulai');
                    if (! is_string($mulai) || $mulai === '') {
                        $fail('Jam mulai wajib diisi jika jam selesai diisi.');

                        return;
                    }

                    if ($value <= $mulai) {
                        $fail('Jam selesai harus lebih besar dari jam mulai.');
                    }
                },
            ],
            'lokasi' => ['nullable', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'dipublikasikan' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis_ujian.required' => 'Jenis ujian wajib dipilih.',
            'jenis_ujian.enum' => 'Jenis ujian hanya UTS atau UAS.',
            'tahun_akademik_id.required' => 'Tahun akademik wajib dipilih.',
            'tahun_akademik_id.integer' => 'Tahun akademik tidak valid.',
            'tahun_akademik_id.exists' => 'Tahun akademik tidak valid.',
            'semester.required' => 'Semester wajib dipilih.',
            'semester.enum' => 'Semester hanya Ganjil atau Genap.',
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'judul.string' => 'Judul pengumuman harus berupa teks.',
            'judul.max' => 'Judul pengumuman maksimal :max karakter.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date_format' => 'Format tanggal mulai harus YYYY-MM-DD.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.date_format' => 'Format tanggal selesai harus YYYY-MM-DD.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'waktu_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'waktu_mulai.not_in' => 'Ujian tidak boleh dimulai pada tengah malam.',
            'waktu_selesai.date_format' => 'Format jam selesai harus HH:MM.',
            'waktu_selesai.not_in' => 'Ujian tidak boleh berakhir pada tengah malam.',
            'lokasi.string' => 'Lokasi harus berupa teks.',
            'lokasi.max' => 'Lokasi maksimal :max karakter.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
            'deskripsi.max' => 'Deskripsi maksimal :max karakter.',
            'dipublikasikan.required' => 'Status publikasi wajib ditentukan.',
            'dipublikasikan.boolean' => 'Status publikasi tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        foreach (['judul', 'lokasi', 'deskripsi', 'waktu_mulai', 'waktu_selesai', 'tanggal_mulai', 'tanggal_selesai'] as $field) {
            $nilai = $this->input($field);

            if (is_string($nilai)) {
                $normalisasi[$field] = trim($nilai);
            }
        }

        foreach (['lokasi', 'deskripsi', 'waktu_mulai', 'waktu_selesai'] as $field) {
            if (array_key_exists($field, $normalisasi) && $normalisasi[$field] === '') {
                $normalisasi[$field] = null;
            }
        }

        if (! $this->has('dipublikasikan')) {
            $normalisasi['dipublikasikan'] = true;
        }

        if ($normalisasi !== []) {
            $this->merge($normalisasi);
        }
    }
}
