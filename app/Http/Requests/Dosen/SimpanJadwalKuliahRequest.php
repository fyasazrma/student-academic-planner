<?php

namespace App\Http\Requests\Dosen;

use App\Enums\JenisJadwal;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Input jadwal hanya menerima field yang memang boleh diisi dosen.
 * Kelas, pemilik, dan identitas jadwal tidak diterima dari request.
 */
class SimpanJadwalKuliahRequest extends JadwalKuliahRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'hari' => ['required', 'integer', 'min:0', 'max:6'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => [
                'required',
                'date_format:H:i',
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
            'hari.required' => 'Hari wajib dipilih.',
            'hari.integer' => 'Hari tidak valid.',
            'hari.min' => 'Hari tidak valid.',
            'hari.max' => 'Hari tidak valid.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai harus HH:MM.',
            'ruang.string' => 'Ruang harus berupa teks.',
            'ruang.max' => 'Ruang maksimal :max karakter.',
            'jenis.required' => 'Jenis jadwal wajib dipilih.',
            'jenis.enum' => 'Jenis jadwal tidak valid.',
        ];
    }

    /**
     * Duplikasi diperiksa terhadap representasi jam yang mungkin tersimpan
     * di database (H:i atau H:i:s), dengan mengabaikan jadwal yang sedang
     * diedit. Pengecekan ini tetap didukung unique constraint database.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hari = $this->hariUntukPengecekan();
            $jamMulai = $this->jamMulaiUntukPengecekan();

            if ($hari === null || $jamMulai === null) {
                return;
            }

            $kelasId = is_numeric($this->route('kelas')) ? (int) $this->route('kelas') : 0;
            $jadwalId = $this->jadwalIdUntukPengecekan();

            $sudahAda = DB::table('jadwal_kuliah')
                ->where('kelas_mata_kuliah_id', $kelasId)
                ->where('hari', $hari)
                ->whereIn('jam_mulai', [$jamMulai, $jamMulai.':00'])
                ->when($jadwalId !== null, fn ($query) => $query
                    ->where('id', '<>', $jadwalId))
                ->exists();

            if ($sudahAda) {
                $validator->errors()->add(
                    'jam_mulai',
                    'Kombinasi hari dan jam mulai sudah digunakan pada kelas ini.',
                );
            }
        });
    }

    private function hariUntukPengecekan(): ?int
    {
        $hari = filter_var($this->input('hari'), FILTER_VALIDATE_INT);

        return is_int($hari) && $hari >= 0 && $hari <= 6 ? $hari : null;
    }

    private function jamMulaiUntukPengecekan(): ?string
    {
        $jamMulai = $this->input('jam_mulai');

        if (! is_string($jamMulai) || preg_match('/^\d{2}:\d{2}$/', $jamMulai) !== 1) {
            return null;
        }

        return $jamMulai;
    }

    private function jadwalIdUntukPengecekan(): ?int
    {
        $jadwalId = $this->route('jadwal');

        return is_numeric($jadwalId) ? (int) $jadwalId : null;
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
