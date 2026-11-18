<?php

namespace App\Http\Requests\Admin;

use App\Enums\Semester;
use App\Models\TahunAkademik;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

abstract class TahunAkademikRequest extends FormRequest
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
        $uniks = Rule::unique('tahun_akademik', 'tahun_mulai')
            ->where(fn ($query) => $query
                ->where('tahun_selesai', $this->input('tahun_selesai'))
                ->where('semester', $this->input('semester')));

        $kunciDiabaikan = $this->periodeDiabaikan();

        if ($kunciDiabaikan !== null) {
            $uniks->ignore($kunciDiabaikan);
        }

        return [
            'tahun_mulai' => [
                'required',
                'integer',
                'digits:4',
                'min:2000',
                'max:2200',
                $uniks,
            ],
            'tahun_selesai' => [
                'required',
                'integer',
                'digits:4',
                'min:2000',
                'max:2200',
                'gt:tahun_mulai',
            ],
            'semester' => [
                'required',
                'string',
                Rule::in(array_column(Semester::cases(), 'value')),
            ],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun_mulai.required' => 'Tahun mulai wajib diisi.',
            'tahun_mulai.integer' => 'Tahun mulai harus berupa bilangan bulat.',
            'tahun_mulai.digits' => 'Tahun mulai harus terdiri dari 4 digit.',
            'tahun_mulai.min' => 'Tahun mulai minimal :min.',
            'tahun_mulai.max' => 'Tahun mulai maksimal :max.',
            'tahun_mulai.unique' => 'Periode tahun akademik ini sudah ada.',
            'tahun_selesai.required' => 'Tahun selesai wajib diisi.',
            'tahun_selesai.integer' => 'Tahun selesai harus berupa bilangan bulat.',
            'tahun_selesai.digits' => 'Tahun selesai harus terdiri dari 4 digit.',
            'tahun_selesai.min' => 'Tahun selesai minimal :min.',
            'tahun_selesai.max' => 'Tahun selesai maksimal :max.',
            'tahun_selesai.gt' => 'Tahun selesai harus lebih besar dari tahun mulai.',
            'semester.required' => 'Semester wajib dipilih.',
            'semester.string' => 'Semester harus berupa teks.',
            'semester.in' => 'Semester harus ganjil atau genap.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date_format' => 'Tanggal mulai harus berformat YYYY-MM-DD.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.date_format' => 'Tanggal selesai harus berformat YYYY-MM-DD.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }

    /**
     * Normalisasi ringan sebelum validasi unik dan enum dijalankan.
     */
    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        foreach (['tahun_mulai', 'tahun_selesai', 'tanggal_mulai', 'tanggal_selesai'] as $field) {
            $nilai = $this->input($field);

            if (is_string($nilai)) {
                $normalisasi[$field] = trim($nilai);
            }
        }

        if (is_string($this->input('semester'))) {
            $normalisasi['semester'] = Str::lower(trim($this->input('semester')));
        }

        if ($normalisasi !== []) {
            $this->merge($normalisasi);
        }
    }

    /**
     * Primary key yang diabaikan saat memeriksa keunikan periode.
     */
    abstract protected function periodeDiabaikan(): mixed;

    protected function kunciDariRoute(): mixed
    {
        try {
            $parameter = $this->route('tahun_akademik');
        } catch (\Throwable) {
            return null;
        }

        if ($parameter instanceof TahunAkademik) {
            return $parameter->getKey();
        }

        return is_numeric($parameter) ? (int) $parameter : null;
    }
}
