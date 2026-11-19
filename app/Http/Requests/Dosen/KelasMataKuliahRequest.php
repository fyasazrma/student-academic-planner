<?php

namespace App\Http\Requests\Dosen;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest dosen memeriksa role sebagai lapisan otorisasi tambahan.
 * Kepemilikan kelas tidak diperiksa di sini; service melakukan scoped lookup
 * berdasarkan dosen_id pengguna yang sedang login.
 */
abstract class KelasMataKuliahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDosen() === true;
    }
}
