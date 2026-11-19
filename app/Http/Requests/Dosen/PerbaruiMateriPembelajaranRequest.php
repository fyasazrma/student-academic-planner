<?php

namespace App\Http\Requests\Dosen;

class PerbaruiMateriPembelajaranRequest extends MateriPembelajaranRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->aturanTeks(), $this->aturanFile(false));
    }
}
