<?php

namespace App\Http\Requests\Mahasiswa;

use App\Services\Dosen\MateriFileService;
use App\Services\Mahasiswa\PengumpulanFileService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Validasi pengumpulan tugas mahasiswa.
 *
 * Identitas mahasiswa, kelas, tugas, status keterlambatan, dan nilai sengaja
 * tidak diterima dari request. Semua nilai tersebut ditentukan ulang oleh
 * server di dalam transaksi.
 */
class SimpanPengumpulanRequest extends FormRequest
{
    /**
     * Role sudah dijamin oleh middleware route mahasiswa.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'lampiran' => [
                'required',
                'file',
                'max:'.MateriFileService::UKURAN_MAKSIMAL_KB,
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $pesan = app(PengumpulanFileService::class)->validasiBerkas($value);

                    if ($pesan !== null) {
                        $fail($pesan);
                    }
                },
            ],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lampiran.required' => 'Lampiran jawaban wajib diunggah.',
            'lampiran.file' => 'Lampiran jawaban tidak valid.',
            'lampiran.max' => 'Ukuran lampiran maksimal 10 MB.',
            'catatan.string' => 'Catatan harus berupa teks.',
            'catatan.max' => 'Catatan maksimal 5000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('catatan'))) {
            $catatan = trim($this->input('catatan'));
            $this->merge(['catatan' => $catatan === '' ? null : $catatan]);
        }
    }
}
