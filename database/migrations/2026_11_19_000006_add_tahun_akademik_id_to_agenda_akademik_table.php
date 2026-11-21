<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Periode UTS/UAS disimpan relasional agar filter tidak bergantung pada
     * suffix judul. Kolom sengaja nullable untuk data lama yang periodenya
     * tidak dapat dipastikan dari sumber mana pun.
     */
    public function up(): void
    {
        Schema::table('agenda_akademik', function (Blueprint $table): void {
            $table->foreignId('tahun_akademik_id')
                ->nullable()
                ->after('kelas_mata_kuliah_id')
                ->index('agenda_akademik_tahun_akademik_id_index')
                ->constrained('tahun_akademik')
                ->restrictOnDelete();

            $table->index(
                ['tahun_akademik_id', 'jenis_ujian'],
                'agenda_akademik_tahun_jenis_ujian_index',
            );
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('agenda_akademik', function (Blueprint $table): void {
            $table->dropForeign(['tahun_akademik_id']);
            $table->dropColumn('tahun_akademik_id');
        });
    }

    /**
     * Backfill hanya memakai hubungan yang dapat dibuktikan:
     * 1. kelasMataKuliah.tahun_akademik_id untuk agenda per kelas;
     * 2. periode yang tertulis di judul dan cocok persis dengan tahun_akademik.
     *
     * Baris yang tidak cocok dibiarkan nullable. Judul hanya dirapikan bila
     * suffix periode ganda ditemukan dan periode tersebut pasti.
     */
    private function backfill(): void
    {
        $periode = DB::table('tahun_akademik')
            ->get(['id', 'semester', 'tahun_mulai', 'tahun_selesai'])
            ->keyBy(fn (object $item): string => $this->kunciPeriode(
                (string) $item->semester,
                (int) $item->tahun_mulai,
                (int) $item->tahun_selesai,
            ));

        $agendas = DB::table('agenda_akademik')
            ->where('jenis', 'ujian')
            ->get(['id', 'judul', 'kelas_mata_kuliah_id', 'tahun_akademik_id']);

        foreach ($agendas as $agenda) {
            if ($agenda->tahun_akademik_id !== null) {
                continue;
            }

            $tahunId = null;
            if ($agenda->kelas_mata_kuliah_id !== null) {
                $tahunId = DB::table('kelas_mata_kuliah')
                    ->where('id', $agenda->kelas_mata_kuliah_id)
                    ->value('tahun_akademik_id');
            }

            $dariJudul = $this->periodeDariJudul((string) $agenda->judul);
            if ($tahunId === null && $dariJudul !== null) {
                $tahunId = $periode->get($this->kunciPeriode(
                    $dariJudul['semester'],
                    $dariJudul['tahun_mulai'],
                    $dariJudul['tahun_selesai'],
                ))?->id;
            }

            if ($tahunId === null) {
                continue;
            }

            DB::table('agenda_akademik')
                ->where('id', $agenda->id)
                ->update(['tahun_akademik_id' => $tahunId]);

            if ($agenda->kelas_mata_kuliah_id === null && $dariJudul !== null) {
                $this->rapikanSuffix((int) $agenda->id, (string) $agenda->judul, $dariJudul);
            }
        }
    }

    /**
     * @return array{semester: string, tahun_mulai: int, tahun_selesai: int}|null
     */
    private function periodeDariJudul(string $judul): ?array
    {
        if (preg_match('/\b(Ganjil|Genap)\s+(\d{4})\/(\d{4})\b/iu', $judul, $matches) !== 1) {
            return null;
        }

        return [
            'semester' => strtolower($matches[1]),
            'tahun_mulai' => (int) $matches[2],
            'tahun_selesai' => (int) $matches[3],
        ];
    }

    /**
     * @param array{semester: string, tahun_mulai: int, tahun_selesai: int} $periode
     */
    private function rapikanSuffix(int $id, string $judul, array $periode): void
    {
        $suffix = '— '.ucfirst($periode['semester']).' '.$periode['tahun_mulai'].'/'.$periode['tahun_selesai'];
        $pola = '/(?:\s*'.preg_quote($suffix, '/').'){2,}\s*$/u';
        $judulBersih = preg_replace($pola, ' '.$suffix, $judul);

        if ($judulBersih !== null && $judulBersih !== $judul) {
            DB::table('agenda_akademik')->where('id', $id)->update(['judul' => $judulBersih]);
        }
    }

    private function kunciPeriode(string $semester, int $mulai, int $selesai): string
    {
        return strtolower($semester).':'.$mulai.'/'.$selesai;
    }
};
