<?php

namespace App\Services\Admin;

use App\Enums\JenisAgenda;
use App\Enums\JenisUjian;
use App\Enums\Semester;
use App\Models\AgendaAkademik;
use App\Models\TahunAkademik;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Pengelolaan jadwal UTS/UAS institusional administrator.
 *
 * Ujian disimpan sebagai satu baris agenda_akademik dengan jenis = ujian,
 * jenis_ujian = uts|uas, dan kelas_mata_kuliah_id = NULL. Kolom jenis_ujian
 * hanya menandai UTS/UAS agar tidak membuat tabel atau event kalender duplikat.
 */
class JadwalUjianService
{
    private const JUDUL_MAKSIMAL = 200;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function daftar(array $filters): LengthAwarePaginator
    {
        Carbon::setLocale('id');

        $query = AgendaAkademik::query()
            ->where('jenis', JenisAgenda::Ujian->value)
            ->whereNotNull('jenis_ujian')
            ->whereNull('kelas_mata_kuliah_id');

        if (($filters['q'] ?? '') !== '') {
            $cari = '%'.strtolower((string) $filters['q']).'%';
            $query->where(function ($group) use ($cari): void {
                $group->whereRaw('lower(judul) like ?', [$cari])
                    ->orWhereRaw("lower(coalesce(deskripsi, '')) like ?", [$cari]);
            });
        }

        if (($filters['jenis_ujian'] ?? '') !== '') {
            $query->where('jenis_ujian', $filters['jenis_ujian']);
        }

        if (($filters['tahun_akademik'] ?? '') !== '') {
            $tahun = TahunAkademik::query()->whereKey((int) $filters['tahun_akademik'])->first();

            if ($tahun === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('judul', 'like', '%'.$this->periodeLabel($tahun).'%');
            }
        }

        if (($filters['semester'] ?? '') !== '') {
            $semester = Semester::tryFrom((string) $filters['semester']);

            if ($semester === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('judul', 'like', '%— '.ucfirst($semester->value).' %');
            }
        }

        if (($filters['tanggal'] ?? '') !== '') {
            $query->whereDate('tanggal_mulai', $filters['tanggal']);
        }

        $query->orderBy('tanggal_mulai')->orderBy('waktu_mulai')->orderBy('id');

        return $query->paginate(15)->through(fn (AgendaAkademik $item): array => $this->baris($item));
    }

    /**
     * @return array<string, string>
     */
    public function opsiJenisUjian(): array
    {
        return [
            JenisUjian::Uts->value => 'UTS — Ujian Tengah Semester',
            JenisUjian::Uas->value => 'UAS — Ujian Akhir Semester',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function opsiSemester(): array
    {
        return [
            Semester::Ganjil->value => 'Ganjil',
            Semester::Genap->value => 'Genap',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function opsiTahunAkademik(): array
    {
        return TahunAkademik::query()
            ->orderByDesc('tahun_mulai')
            ->orderByDesc('semester')
            ->get()
            ->mapWithKeys(fn (TahunAkademik $tahun): array => [$tahun->id => $this->periodeLabel($tahun)])
            ->all();
    }

    /**
     * Nilai awal formulir. Periode tidak memiliki kolom khusus pada schema,
     * sehingga dibaca dari akhir judul agenda institusional yang dibuat service ini.
     *
     * @return array{tahun_akademik_id: int|null, semester: string, judul: string}
     */
    public function formulir(?AgendaAkademik $ujian): array
    {
        $tahun = $ujian === null
            ? TahunAkademik::aktif()->first()
            : $this->tahunDariJudul((string) $ujian->judul);
        $tahun ??= TahunAkademik::aktif()->first();
        $jenisUjian = $ujian?->jenis_ujian ?? JenisUjian::Uts;

        return [
            'tahun_akademik_id' => $tahun === null ? null : (int) $tahun->getKey(),
            'semester' => $tahun?->semester?->value ?? Semester::Ganjil->value,
            'judul' => $tahun === null ? '' : $this->judulOtomatis($jenisUjian, $this->periodeLabel($tahun)),
        ];
    }

    public function temukan(mixed $id): AgendaAkademik
    {
        return AgendaAkademik::query()
            ->whereKey(is_numeric($id) ? (int) $id : 0)
            ->where('jenis', JenisAgenda::Ujian->value)
            ->whereNotNull('jenis_ujian')
            ->whereNull('kelas_mata_kuliah_id')
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    public function simpan(array $data, int $adminId): array
    {
        return $this->tulis($data, $adminId, null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    public function perbarui(mixed $id, array $data): array
    {
        return $this->tulis($data, null, $this->temukan($id));
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    public function hapus(mixed $id): array
    {
        return DB::transaction(function () use ($id): array {
            $ujian = $this->temukan($id);
            $ujian->delete();

            return ['berhasil' => true, 'pesan' => 'Jadwal ujian berhasil dihapus.'];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    private function tulis(array $data, ?int $adminId, ?AgendaAkademik $ujian): array
    {
        $jenisUjian = JenisUjian::tryFrom((string) $data['jenis_ujian']);
        if ($jenisUjian === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Jenis ujian hanya UTS atau UAS.',
                'field' => 'jenis_ujian',
            ];
        }

        $tahun = TahunAkademik::query()->whereKey((int) $data['tahun_akademik_id'])->first();
        if ($tahun === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Tahun akademik tidak valid.',
                'field' => 'tahun_akademik_id',
            ];
        }

        $semester = Semester::tryFrom((string) $data['semester']);
        if ($semester === null || $tahun->semester !== $semester) {
            return [
                'berhasil' => false,
                'pesan' => 'Semester harus sesuai dengan tahun akademik yang dipilih.',
                'field' => 'semester',
            ];
        }

        $tanggalMulai = (string) $data['tanggal_mulai'];
        $tanggalSelesai = (string) ($data['tanggal_selesai'] ?? $tanggalMulai);
        if ($tanggalSelesai < $tanggalMulai) {
            return [
                'berhasil' => false,
                'pesan' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                'field' => 'tanggal_selesai',
            ];
        }

        $mulai = $this->jam($data['waktu_mulai'] ?? null);
        $selesai = $this->jam($data['waktu_selesai'] ?? null);
        if (($mulai === null) !== ($selesai === null)) {
            return [
                'berhasil' => false,
                'pesan' => 'Jam mulai dan jam selesai harus diisi berpasangan.',
                'field' => 'waktu_mulai',
            ];
        }
        if ($mulai !== null && $selesai !== null && $selesai <= $mulai) {
            return [
                'berhasil' => false,
                'pesan' => 'Jam selesai harus lebih besar dari jam mulai.',
                'field' => 'waktu_selesai',
            ];
        }

        return DB::transaction(function () use (
            $data,
            $adminId,
            $ujian,
            $jenisUjian,
            $tahun,
            $tanggalMulai,
            $tanggalSelesai,
            $mulai,
            $selesai,
        ): array {
            $atribut = [
                'kelas_mata_kuliah_id' => null,
                'judul' => $this->judul($jenisUjian, $tahun, $data['judul'] ?? null),
                'deskripsi' => $this->teks($data['deskripsi'] ?? null),
                'jenis' => JenisAgenda::Ujian->value,
                'jenis_ujian' => $jenisUjian->value,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'waktu_mulai' => $mulai === null ? null : $mulai.':00',
                'waktu_selesai' => $selesai === null ? null : $selesai.':00',
                'lokasi' => $this->lokasi($data['lokasi'] ?? null),
                'dipublikasikan_pada' => (bool) ($data['dipublikasikan'] ?? true) ? now() : null,
            ];

            if ($ujian === null) {
                $baru = new AgendaAkademik();
                $baru->forceFill($atribut + ['dibuat_oleh' => $adminId])->save();
                $pesan = 'Jadwal '.strtolower($jenisUjian->label()).' berhasil ditambahkan.';
            } else {
                $ujian->fill($atribut)->save();
                $pesan = 'Jadwal '.strtolower($jenisUjian->label()).' berhasil diperbarui.';
            }

            return ['berhasil' => true, 'pesan' => $pesan, 'field' => null];
        });
    }

    private function judul(JenisUjian $jenisUjian, TahunAkademik $tahun, mixed $value): string
    {
        $periode = $this->periodeLabel($tahun);
        $suffix = ' — '.$periode;
        $custom = is_string($value) ? trim($value) : '';

        $judul = $custom !== '' && str_contains(strtoupper($custom), $jenisUjian->label())
            ? $custom
            : $this->judulOtomatis($jenisUjian, $periode);

        $judul = preg_replace('/ — (?:Ganjil|Genap) \d{4}\/\d{4}\s*$/u', '', $judul) ?? $judul;
        $maksimal = self::JUDUL_MAKSIMAL - mb_strlen($suffix);

        if (mb_strlen($judul) > $maksimal) {
            $judul = rtrim(mb_substr($judul, 0, $maksimal));
        }

        return $judul.$suffix;
    }

    private function judulOtomatis(JenisUjian $jenisUjian, string $periode): string
    {
        return match ($jenisUjian) {
            JenisUjian::Uts => 'Jadwal Ujian Tengah Semester (UTS) — '.$periode,
            JenisUjian::Uas => 'Jadwal Ujian Akhir Semester (UAS) — '.$periode,
        };
    }

    private function periodeLabel(TahunAkademik $tahun): string
    {
        return ucfirst($tahun->semester->value).' '.$tahun->tahun_mulai.'/'.$tahun->tahun_selesai;
    }

    private function tahunDariJudul(string $judul): ?TahunAkademik
    {
        if (preg_match('/—\s+(Ganjil|Genap)\s+(\d{4})\/(\d{4})\s*$/u', $judul, $matches) !== 1) {
            return null;
        }

        return TahunAkademik::query()
            ->where('tahun_mulai', (int) $matches[2])
            ->where('tahun_selesai', (int) $matches[3])
            ->where('semester', strtolower($matches[1]))
            ->first();
    }

    private function jam(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $nilai = trim($nilai);
        if ($nilai === '') {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}$/', $nilai) === 1 ? $nilai : substr($nilai, 0, 5);
    }

    private function lokasi(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $lokasi = trim($nilai);

        return $lokasi === '' ? null : $lokasi;
    }

    private function teks(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);

        return $teks === '' ? null : $teks;
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(AgendaAkademik $item): array
    {
        $jenisUjian = $item->jenis_ujian;
        $tahun = $this->tahunDariJudul((string) $item->judul);
        $mulai = $item->tanggal_mulai;
        $selesai = $item->tanggal_selesai;

        $tanggalLabel = $mulai === null
            ? '-'
            : ($selesai !== null && $selesai->greaterThan($mulai)
                ? $mulai->translatedFormat('d M Y').' – '.$selesai->translatedFormat('d M Y')
                : $mulai->translatedFormat('D, d M Y'));

        return [
            'id' => $item->getKey(),
            'judul' => $item->judul,
            'jenis_ujian' => $jenisUjian?->value ?? '',
            'jenis_label' => $jenisUjian?->label() ?? 'Ujian',
            'tahun' => $tahun?->label() ?? '-',
            'semester' => $tahun?->semester
                ? ucfirst($tahun->semester->value)
                : '-',
            'tanggal' => $mulai?->toDateString() ?? '',
            'tanggal_label' => $tanggalLabel,
            'waktu_mulai' => $this->jamLabel($item->waktu_mulai),
            'waktu_selesai' => $this->jamLabel($item->waktu_selesai),
            'lokasi' => $item->lokasi,
            'deskripsi' => $item->deskripsi,
            'dipublikasikan' => $item->dipublikasikan_pada !== null,
            'publikasi_label' => $item->dipublikasikan_pada?->translatedFormat('d M Y H:i') ?? 'Draft',
        ];
    }

    private function jamLabel(mixed $value): string
    {
        if (! is_string($value)) {
            return '-';
        }

        $jam = substr($value, 0, 5);

        return $jam === '' ? '-' : $jam;
    }
}
