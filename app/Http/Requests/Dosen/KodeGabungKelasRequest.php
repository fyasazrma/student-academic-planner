<?php

namespace App\Http\Requests\Dosen;

/**
 * Endpoint kunci gabung tidak menerima input. Kunci dibuat oleh server dan
 * identitas kelas tetap ditentukan oleh scoped lookup di service.
 */
class KodeGabungKelasRequest extends KelasMataKuliahRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
