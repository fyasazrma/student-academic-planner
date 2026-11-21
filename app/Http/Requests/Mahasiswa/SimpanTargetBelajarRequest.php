<?php

namespace App\Http\Requests\Mahasiswa;

/**
 * Validasi pembuatan target belajar baru.
 */
class SimpanTargetBelajarRequest extends TargetBelajarRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'capaian' => ['sometimes', 'numeric', 'min:0', 'max:9999999999'],
        ]);
    }
}
