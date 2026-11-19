<?php

namespace App\Http\Requests\Dosen;

use App\Services\Dosen\MateriFileService;
use App\Services\Dosen\TugasFileService;
use App\Services\Dosen\TugasService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * FormRequest tugas dosen memeriksa role dan kepemilikan kelas serta tugas.
 * Scoped lookup yang gagal akan menghasilkan 404, bukan 403 atau 422.
 */
abstract class TugasRequest extends FormRequest
{
    public function authorize(TugasService $tugas): bool
    {
        if ($this->user()?->isDosen() !== true) {
            return false;
        }

        $kelas = $tugas->kelasMilikDosen(
            (int) $this->user()->getAuthIdentifier(),
            $this->route('kelas'),
        );

        if ($this->route('tugas') !== null) {
            $tugas->tugasMilikKelas($kelas, $this->route('tugas'));
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function aturanUmum(): array
    {
        return [
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,dipublikasikan'],
            'dipublikasikan_pada' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'required_if:status,dipublikasikan',
            ],
            'deadline' => ['required', 'date_format:Y-m-d H:i:s'],
            'nilai_maksimal' => ['required', 'numeric', 'min:0', 'max:100'],
            'izinkan_terlambat' => ['required', 'boolean'],
            'hapus_lampiran' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function aturanLampiran(): array
    {
        return [
            'lampiran' => [
                'nullable',
                'file',
                'max:'.MateriFileService::UKURAN_MAKSIMAL_KB,
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $pesan = app(TugasFileService::class)->validasiBerkas($value);

                    if ($pesan !== null) {
                        $fail($pesan);
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul tugas wajib diisi.',
            'judul.string' => 'Judul tugas harus berupa teks.',
            'judul.max' => 'Judul tugas maksimal :max karakter.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
            'deskripsi.max' => 'Deskripsi maksimal :max karakter.',
            'status.required' => 'Status tugas wajib dipilih.',
            'status.in' => 'Status tugas tidak valid.',
            'dipublikasikan_pada.date_format' => 'Tanggal publikasi tidak valid.',
            'dipublikasikan_pada.required_if' => 'Tanggal publikasi wajib diisi ketika tugas dipublikasikan.',
            'deadline.required' => 'Deadline wajib diisi.',
            'deadline.date_format' => 'Deadline tidak valid.',
            'nilai_maksimal.required' => 'Nilai maksimal wajib diisi.',
            'nilai_maksimal.numeric' => 'Nilai maksimal harus berupa angka.',
            'nilai_maksimal.min' => 'Nilai maksimal tidak boleh negatif.',
            'nilai_maksimal.max' => 'Nilai maksimal tidak boleh lebih dari 100.',
            'izinkan_terlambat.required' => 'Kebijakan keterlambatan wajib dipilih.',
            'izinkan_terlambat.boolean' => 'Kebijakan keterlambatan tidak valid.',
            'lampiran.file' => 'Berkas lampiran tidak valid.',
            'lampiran.max' => 'Ukuran lampiran maksimal 10 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'dipublikasikan') {
                return;
            }

            $publikasi = $this->input('dipublikasikan_pada');
            $deadline = $this->input('deadline');

            if (! is_string($publikasi) || ! is_string($deadline)) {
                return;
            }

            if (Carbon::parse($publikasi)->greaterThan(Carbon::parse($deadline))) {
                $validator->errors()->add(
                    'dipublikasikan_pada',
                    'Tanggal publikasi tidak boleh setelah deadline.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        if (is_string($this->input('judul'))) {
            $normalisasi['judul'] = trim($this->input('judul'));
        }

        if (is_string($this->input('deskripsi'))) {
            $deskripsi = trim($this->input('deskripsi'));
            $normalisasi['deskripsi'] = $deskripsi === '' ? null : $deskripsi;
        }

        $status = is_string($this->input('status')) ? trim($this->input('status')) : $this->input('status');

        if ($status !== null) {
            $normalisasi['status'] = $status;
        }

        $deadline = $this->keFormatDatabase($this->input('deadline'));

        if ($deadline !== null) {
            $normalisasi['deadline'] = $deadline;
        }

        if ($status === 'draft') {
            $normalisasi['dipublikasikan_pada'] = null;
        } else {
            $publikasi = $this->keFormatDatabase($this->input('dipublikasikan_pada'));

            if ($publikasi !== null) {
                $normalisasi['dipublikasikan_pada'] = $publikasi;
            }
        }

        if (is_string($this->input('nilai_maksimal'))) {
            $normalisasi['nilai_maksimal'] = trim($this->input('nilai_maksimal'));
        }

        $normalisasi['izinkan_terlambat'] = $this->boolean('izinkan_terlambat') ? '1' : '0';
        $normalisasi['hapus_lampiran'] = $this->boolean('hapus_lampiran') ? '1' : '0';

        $this->merge($normalisasi);
    }

    private function keFormatDatabase(mixed $nilai): ?string
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        $nilai = trim($nilai);

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $nilai) === 1) {
            return str_replace('T', ' ', $nilai).':00';
        }

        return $nilai;
    }
}
