<?php

namespace App\Http\Requests\Dosen;

use App\Services\Dosen\MateriFileService;
use App\Services\Dosen\MateriPembelajaranService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * FormRequest materi dosen memeriksa role dan kepemilikan kelas serta materi.
 * Scoped lookup yang gagal akan menghasilkan 404, bukan 403 atau 422.
 */
abstract class MateriPembelajaranRequest extends FormRequest
{
    public function authorize(MateriPembelajaranService $materi): bool
    {
        if ($this->user()?->isDosen() !== true) {
            return false;
        }

        $kelas = $materi->kelasMilikDosen(
            (int) $this->user()->getAuthIdentifier(),
            $this->route('kelas'),
        );

        if ($this->route('materi') !== null) {
            $materi->materiMilikKelas($kelas, $this->route('materi'));
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function aturanTeks(): array
    {
        return [
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,dipublikasikan'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function aturanFile(bool $wajib): array
    {
        $aturan = [
            $wajib ? 'required' : 'nullable',
            'file',
            'max:'.MateriFileService::UKURAN_MAKSIMAL_KB,
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                $pesan = app(MateriFileService::class)->validasiBerkas($value);

                if ($pesan !== null) {
                    $fail($pesan);
                }
            },
        ];

        return ['file' => $aturan];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul materi wajib diisi.',
            'judul.string' => 'Judul materi harus berupa teks.',
            'judul.max' => 'Judul materi maksimal :max karakter.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
            'deskripsi.max' => 'Deskripsi maksimal :max karakter.',
            'file.required' => 'File materi wajib diunggah.',
            'file.file' => 'Berkas materi tidak valid.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
            'status.required' => 'Status materi wajib dipilih.',
            'status.in' => 'Status materi tidak valid.',
        ];
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

        if (is_string($this->input('status'))) {
            $normalisasi['status'] = trim($this->input('status'));
        }

        if ($normalisasi !== []) {
            $this->merge($normalisasi);
        }
    }
}
