<?php

namespace App\Http\Requests\Admin;

class PerbaruiTahunAkademikRequest extends TahunAkademikRequest
{
    /**
     * Periode milik record yang sedang diedit boleh tetap dipakai.
     */
    protected function periodeDiabaikan(): mixed
    {
        return $this->kunciDariRoute();
    }
}
