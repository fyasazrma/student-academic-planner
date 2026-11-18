<?php

namespace App\Http\Requests\Admin;

class PerbaruiMataKuliahRequest extends MataKuliahRequest
{
    /**
     * Kode milik record yang sedang diedit boleh tetap dipakai.
     */
    protected function kodeDiabaikan(): mixed
    {
        return $this->kunciDariRoute();
    }
}
