<?php

namespace App\Http\Requests\Dosen;

use App\Enums\StatusPublikasi;
use App\Services\Dosen\MataKuliahService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengumumanRequest extends FormRequest
{
    public function authorize(MataKuliahService $mataKuliah): bool
    {
        if ($this->user()?->isDosen() !== true) {
            return false;
        }

        $kelasId = $this->input('kelas_mata_kuliah_id');
        if ($kelasId !== null && $kelasId !== '') {
            $mataKuliah->milikDosen(
                (int) $this->user()->getAuthIdentifier(),
                $kelasId,
            );
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:200'],
            'isi' => ['required', 'string', 'max:5000'],
            'kelas_mata_kuliah_id' => [
                'required',
                'integer',
                Rule::exists('kelas_mata_kuliah', 'id'),
            ],
            'status' => ['required', Rule::enum(StatusPublikasi::class)],
            'tanggal_publikasi' => [
                'required_if:status,terjadwal',
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:tanggal_mulai',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'judul.max' => 'Judul pengumuman maksimal :max karakter.',
            'isi.required' => 'Isi pengumuman wajib diisi.',
            'isi.max' => 'Isi pengumuman maksimal :max karakter.',
            'kelas_mata_kuliah_id.required' => 'Kelas wajib dipilih.',
            'kelas_mata_kuliah_id.exists' => 'Kelas tidak valid.',
            'status.required' => 'Status publikasi wajib dipilih.',
            'tanggal_publikasi.required_if' => 'Waktu publikasi wajib diisi untuk pengumuman terjadwal.',
            'tanggal_publikasi.date_format' => 'Format waktu publikasi tidak valid.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date_format' => 'Format tanggal mulai harus YYYY-MM-DD.',
            'tanggal_selesai.date_format' => 'Format tanggal berakhir harus YYYY-MM-DD.',
            'tanggal_selesai.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        foreach (['judul', 'isi', 'tanggal_publikasi', 'tanggal_mulai', 'tanggal_selesai'] as $field) {
            $nilai = $this->input($field);
            if (is_string($nilai)) {
                $normalisasi[$field] = trim($nilai);
            }
        }

        foreach (['tanggal_publikasi', 'tanggal_selesai'] as $field) {
            if (array_key_exists($field, $normalisasi) && $normalisasi[$field] === '') {
                $normalisasi[$field] = null;
            }
        }

        $this->merge($normalisasi);
    }
}
