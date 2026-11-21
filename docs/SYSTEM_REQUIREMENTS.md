# System Requirements — Student Academic Planner & E-Learning System

## 1. Role
Aplikasi memiliki tiga role:
- `mahasiswa`
- `dosen`
- `admin`

Tidak tersedia registrasi publik. Admin membuat akun mahasiswa dan dosen. Pengguna dapat login, logout, mengganti password, dan menggunakan reset password yang aman.

## 2. Fitur mahasiswa
1. Dashboard.
2. Profil mahasiswa.
3. Kalender akademik (Calendar View + Timeline View).
4. Mata kuliah.
5. Materi pembelajaran.
6. Tugas dan pengumpulan.
7. Jadwal ujian (UTS dan UAS).
8. Target belajar.
9. Nilai (hanya melihat).
10. Absensi (hanya mengisi/melihat absensi sendiri sesuai aturan).
11. Notifikasi dalam aplikasi.

Mahasiswa hanya boleh mengakses data dirinya dan kelas yang diikutinya.

## 3. Fitur dosen
1. Dashboard.
2. Profil dosen.
3. Mata kuliah saya.
4. Materi pembelajaran.
5. Manajemen tugas.
6. Pengumpulan tugas.
7. Jadwal ujian (UTS dan UAS).
8. Progres belajar mahasiswa.
9. Data nilai (mengelola nilai kelas yang diajar).
10. Absensi (melihat/mengelola kelas yang diajar).
11. Pengumuman.

Dosen hanya boleh mengelola kelas yang menjadi tanggung jawabnya.

## 4. Fitur admin
1. Dashboard.
2. Manajemen pengguna.
3. Data mahasiswa.
4. Data dosen.
5. Data mata kuliah.
6. Tahun akademik dan semester.
7. Penjadwalan kuliah.
8. Kalender akademik institusi.
9. Jadwal ujian UTS/UAS (CRUD penuh; satu-satunya role yang dapat menulis).

Admin mengelola data dan agenda sesuai kewenangannya.

## 5. Aturan bisnis penting
- Tidak ada registrasi publik.
- Mahasiswa hanya melihat nilai; dosen mengelola nilai.
- Mahasiswa tidak boleh mengubah absensi mahasiswa lain.
- Dosen dapat mengelola absensi kelas yang diajar.
- Notifikasi akademik bersifat in-app; tidak perlu push notification/FCM.
- Email reset password boleh digunakan dan tidak sama dengan notifikasi akademik dalam aplikasi.
- Tugas memiliki dua tanggal yang wajib dibedakan: `dipublikasikan_pada` (kapan tugas mulai terlihat dan bisa dikerjakan mahasiswa) dan `deadline` (batas waktu pengumpulan). Jangan menggabungkan atau menghilangkan salah satunya.
- Materi diunggah dosen untuk kelas tertentu; progres baca dicatat per mahasiswa.
- Pengingat deadline tugas dan ujian dapat muncul di notifikasi aplikasi.
- UTS (Ujian Tengah Semester) dan UAS (Ujian Akhir Semester) adalah dua kategori ujian yang berbeda dan harus ditampilkan dengan label yang jelas.
- Jadwal UTS/UAS ditulis hanya oleh admin. Dosen dan mahasiswa bersifat **read-only** dan tidak memiliki route POST/PUT/PATCH/DELETE untuk jadwal ujian.
- Jadwal ujian memakai satu sumber data: `agenda_akademik` dengan `jenis = ujian`, `jenis_ujian = uts|uas`, dan `kelas_mata_kuliah_id = NULL`. Tidak ada tabel/event kalender kedua atau penulisan agenda duplikat.
- Periode jadwal ujian disimpan relasional lewat `tahun_akademik_id` (FK nullable). Filter tidak bergantung pada parsing suffix judul.
- Validasi bentrok jadwal: lokasi yang sama (normalisasi case/trim) dengan tanggal yang tumpang tindih dan jam yang tumpang tindih ditolak. Jika salah satu agenda tanpa jam, tanggal yang tumpang tindih pada lokasi yang sama tetap ditolak. Lokasi berbeda, waktu berbeda, dan lokasi kosong tidak dianggap bentrok.
- Pemeriksaan bentrok berjalan dalam transaksi dengan `GET_LOCK` per lokasi dan `lockForUpdate()` untuk mengurangi race condition; mekanisme ini bukan jaminan mutlak terhadap konkurensi.
- Nilai UTS dan UAS ditulis oleh dosen; nilai tugas bersumber dari `pengumpulan_tugas.nilai` dan tidak ditulis lewat form nilai.
- Nilai akhir dihitung dengan bobot UTS 35%, UAS 55%, dan tugas 10% bila komponen tugas dipakai. Bila tidak dipakai, UTS/UAS dinormalisasi dengan rasio eksak 35/90 dan 55/90.
- Nilai akhir dibulatkan dua angka desimal. Nilai 0 valid bila memang diberikan; nilai di luar rentang 0–100 ditolak.
- Konversi huruf/bobot mutu: A 80–100 (4.00), B 70–<80 (3.00), C 60–<70 (2.00), D 50–<60 (1.00), E 0–<50 (0.00). Nilai E tetap masuk denominator IP/IPK.
- IP semester memakai nilai pengambilan pada semester itu. IPK memakai satu nilai terbaik per `mata_kuliah_id` dari seluruh riwayat, sehingga SKS tidak dihitung ganda. Riwayat pengambilan tidak dihapus. IP/IPK dihitung dinamis, bukan disimpan permanen.

## 6. Konsep data/entitas
Rancangan yang sudah diimplementasikan dalam migration (periksa file migration aktual untuk schema definitif):

| No | Tabel | Keterangan |
|---|---|---|
| 1 | `users` | Pengguna (mahasiswa, dosen, admin) |
| 2 | `tahun_akademik` | Tahun akademik dan semester |
| 3 | `mata_kuliah` | Data induk mata kuliah (dikelola admin) |
| 4 | `kelas_mata_kuliah` | Kelas pengampu per mata kuliah, tahun akademik, dan dosen |
| 5 | `peserta_mata_kuliah` | Mahasiswa yang terdaftar di kelas |
| 6 | `jadwal_kuliah` | Jadwal kuliah per kelas (hari, jam, ruang) |
| 7 | `materi_pembelajaran` | Materi yang diunggah dosen |
| 8 | `progres_materi` | Progres baca materi per mahasiswa |
| 9 | `tugas` | Tugas dengan `dipublikasikan_pada` dan `deadline` |
| 10 | `pengumpulan_tugas` | Pengumpulan tugas oleh mahasiswa |
| 11 | `agenda_akademik` | Agenda: akademik, ujian (UTS/UAS lewat `jenis_ujian`, periode lewat `tahun_akademik_id`), pengumuman |
| 12 | `target_belajar` | Target belajar mahasiswa |
| 13 | `notifikasi` | Notifikasi in-app |
| 14 | `nilai` | Nilai per mahasiswa per komponen |
| 15 | `sesi_absensi` | Sesi absensi yang dibuat dosen |
| 16 | `absensi` | Catatan kehadiran mahasiswa per sesi |
| 17 | `riwayat_perubahan_nilai` | Audit pembuatan/perubahan UTS/UAS dan konfigurasi komponen tugas |
| 18 | `riwayat_perubahan_absensi` | Audit koreksi absensi (status lama, status baru, alasan, actor, waktu) |
| 19 | `aktivitas_materi` | Catatan kejadian buka/unduh materi per peserta (terpisah dari penyelesaian) |

Ini adalah rancangan yang sudah diimplementasikan dalam migration. Namun, selalu periksa file migration dan model aktual sebelum mengimplementasikan fitur. Migration `2026_11_19_000004_create_riwayat_perubahan_absensi_table.php` sudah dijalankan (`Ran`, batch 5) ke database utama XAMPP pada tahap Batch B. Migration `2026_11_19_000005_add_batch_c_target_dan_progres_materi.php` sudah dijalankan (`Ran`, batch 6) ke database utama XAMPP pada tahap Batch C. Migration `2026_11_19_000006_add_tahun_akademik_id_to_agenda_akademik_table.php` sudah dijalankan (`Ran`, batch 7) ke database utama XAMPP; seluruh pengujian otomatis memakai SQLite `:memory:` melalui `RefreshDatabase`.

## 7. Catatan schema aktual (gap yang perlu diperhatikan)

### UTS vs UAS di agenda_akademik
- Enum `JenisAgenda` tetap memiliki nilai: `akademik`, `ujian`, `pengumuman` — **tidak diubah**.
- Pembedaan UTS/UAS memakai enum terpisah `JenisUjian` (`uts|uas`) pada kolom `jenis_ujian`, yang ditambahkan migration `2026_11_19_000001_add_jenis_ujian_to_agenda_akademik_table.php` (sudah `Ran`).
- Periode jadwal ujian disimpan lewat kolom `tahun_akademik_id` (FK nullable ke `tahun_akademik`, dengan index) dari migration `2026_11_19_000006_add_tahun_akademik_id_to_agenda_akademik_table.php` (sudah `Ran`, batch 7). Filter form dan query memakai relasi `tahunAkademik()`; suffix pada `judul` tidak lagi dijadikan sumber filter.
- Label dan warna kategori dipusatkan di `CalendarEventFactory::agendaCategory()` agar konsisten di seluruh panel kalender. Token `UJIAN` (umum) berbeda dari `UTS`/`UAS`.
- Tidak ada unique constraint pada jadwal ujian; bentrok dicegah oleh validasi service, bukan oleh schema.

### Jadwal kuliah dan ujian
- Tabel `jadwal_kuliah` menyimpan jadwal reguler kuliah/praktikum.
- Jadwal ujian (UTS/UAS) disimpan di `agenda_akademik` dengan `jenis = ujian`, `jenis_ujian = uts|uas`, dan `kelas_mata_kuliah_id = NULL`.
- Jadwal kuliah **bukan** sumber bentrok untuk pengumuman ujian institusional; keduanya diberi label yang jelas di UI dan tidak dicampur.

## 8. Data induk mata kuliah, kelas pengampu, dan kunci gabung

### Alur data
- Admin mengelola **data induk** di `mata_kuliah` (kode, nama, SKS, deskripsi, program studi, semester kurikuler, status aktif) dan `tahun_akademik`.
- Dosen memilih mata kuliah induk yang sudah ada, lalu membuat **kelas pengampu** di `kelas_mata_kuliah` untuk tahun akademik tertentu.
- Satu mata kuliah boleh memiliki beberapa kelas pada tahun akademik dan semester yang sama (misalnya A, B, C, D). Kode kelas unik dalam kombinasi `(mata_kuliah_id, tahun_akademik_id, kode_kelas)`.
- `mata_kuliah.semester` adalah semester kurikuler (angka), sedangkan `tahun_akademik.semester` adalah ganjil/genap. Keduanya bukan field yang sama.
- Mahasiswa hanya dapat mengakses kelas yang berhasil diikuti melalui `peserta_mata_kuliah` dengan status `aktif`.

### Kunci gabung kelas
- Setiap kelas memiliki **satu kunci aktif**. Kunci **tidak memiliki masa kedaluwarsa otomatis**; kunci tetap berlaku sampai dosen atau admin menggantinya secara eksplisit.
- Pembuatan key hanya dilakukan ketika kelas belum memiliki key. Rotasi key hanya dilakukan atas tindakan eksplisit dosen atau admin.
- Setelah rotasi, hash lama ditimpa dan kunci lama langsung tidak berlaku. Kunci baru yang ditampilkan adalah kunci yang benar-benar tersimpan pada transaksi rotasi yang berhasil.
- Menutup pendaftaran **tidak menghapus dan tidak mengganti** kunci. Membuka pendaftaran kembali memakai kunci yang sama. Mengganti kunci tidak menghapus peserta yang sudah terdaftar.
- Kunci asli dibuat dengan generator kriptografis (`random_bytes`, minimal 128 bit entropi) dan hanya dikembalikan sekali oleh operasi pembuatan atau rotasi untuk ditampilkan kepada dosen. Tidak ada mekanisme untuk mengambil kembali kunci asli dari database.
- Database **tidak pernah menyimpan kunci asli**. Kolom `kode_gabung_hash` menyimpan SHA-256 dari kunci yang sudah dinormalisasi (trim + uppercase). Nilainya nullable dan unique.
- Verifikasi selalu membaca hash terbaru dari database berdasarkan ID kelas, lalu membandingkannya dengan `hash_equals()`. Instance Eloquent yang stale tidak dapat menerima kunci lama.
- Rotasi dilakukan dalam transaksi dengan `lockForUpdate()` pada baris kelas agar dua rotasi bersamaan diproses berurutan dan tidak mengembalikan kunci yang sudah tertimpa.
- Collision ditangani hanya ketika benar-benar terjadi pelanggaran unique pada `kode_gabung_hash`; pelanggaran integrity constraint lain tetap dilempar sebagai exception.
- Hash tidak boleh ditampilkan sebagai pengganti kunci asli, tidak dikembalikan dari endpoint daftar kelas, dan tidak diekspos melalui serialisasi model (`toArray()`, `toJson()`, `jsonSerialize()`).

### Status pendaftaran dan kapasitas
- `kelas_mata_kuliah.status_pendaftaran` bernilai `buka` atau `tutup` (enum `StatusPendaftaranKelas`). Default dan kondisi awal seluruh kelas yang sudah ada adalah `tutup`.
- `kapasitas = null` berarti **tidak terbatas**. Jika berisi angka, kapasitas membatasi jumlah peserta dengan status `aktif`.
- Peserta berstatus `nonaktif` atau `mengulang` tidak dihitung dalam batas kapasitas.
- Service/mekanisme key dan status ini merupakan fondasi. Halaman CRUD dosen, form gabung mahasiswa, route, dan notifikasi pendaftaran belum termasuk dalam tahap fondasi ini.

## 9. Batasan fitur
- Jangan menambahkan pembayaran, chat real-time, forum, push notification, atau fitur lain yang belum diminta.
- Jangan menambahkan registrasi mandiri.
- Jangan menganggap mahasiswa boleh mengubah nilai.
- Jangan menganggap mahasiswa boleh mengelola kelas atau agenda institusi.
- Jangan menampilkan angka statistik tanpa sumber query nyata.
- Jika ada fitur baru yang diperlukan, jelaskan manfaat, dampak database, dan dampak akses sebelum implementasi.

## 10. Sistem penilaian akademik

### Migration pendukung
- `2026_11_19_000002_add_gunakan_tugas_to_kelas_mata_kuliah_table.php`: kolom `gunakan_tugas` nullable. `NULL` berarti dosen belum menentukan penggunaan komponen tugas; nilai akhir belum dihitung. `1` berarti tugas dipakai, `0` berarti tidak dipakai.
- `2026_11_19_000003_create_riwayat_perubahan_nilai_table.php`: tabel audit untuk nilai UTS/UAS (`komponen = uts|uas`) dan perubahan konfigurasi (`komponen = konfigurasi_tugas`). Baris audit tidak pernah dihapus atau ditimpa.
- Kedua file migration sudah dibuat dan **sudah dijalankan (`Ran`) ke database utama XAMPP** pada tahap Batch A; pemeriksaan otomatis tetap memakai SQLite `:memory:` melalui `RefreshDatabase`.

### Alur dosen
- Route: `dosen.nilai.index`, `dosen.nilai.kelas`, `dosen.nilai.konfigurasi`, `dosen.nilai.simpan`, `dosen.nilai.riwayat`.
- Setiap aksi memakai scoped lookup kelas berdasarkan `dosen_id`. Peserta harus benar-benar terdaftar di kelas tersebut. Kelas atau peserta milik dosen lain menghasilkan 404.
- Hanya `uts` dan `uas` yang dapat ditulis. FormRequest, route constraint, dan service menolak komponen lain termasuk `tugas`.
- Nilai UTS/UAS disimpan dengan `updateOrCreate` yang diawali `lockForUpdate()` pada transaksi yang sama dengan penulisan audit. Nilai 0 tidak dianggap sebagai data kosong.
- Rekap kelas menampilkan UTS, UAS, rata-rata tugas, nilai akhir, huruf, bobot mutu, dan status kelengkapan. Peserta nonaktif tetap ditampilkan bila memiliki nilai atau pengumpulan.

### Alur mahasiswa
- Route: `mahasiswa.nilai`, `mahasiswa.nilai.show`.
- Mahasiswa hanya dapat melihat nilai dan riwayat pengambilan miliknya. ID mata kuliah pada URL divalidasi terhadap kelas yang benar-benar diikuti.
- Halaman menampilkan IP semester, IPK, nilai per mata kuliah, konversi huruf/bobot, dan riwayat pengambilan dengan penanda nilai yang dipakai untuk IPK.
- Tie-breaker IPK: nilai akhir tertinggi → periode paling awal (`tahun_mulai`, ganjil sebelum genap) → `peserta_id` terkecil.

### Perhitungan
- `NilaiAkhirService`: rumus, pembulatan integer cents, agregasi tugas, konversi huruf, dan bobot mutu. Tidak menulis database.
- `PenilaianService`: otorisasi scoped, penyimpanan UTS/UAS, agregasi tugas, dan audit transaksional.
- `IndeksPrestasiService`: IP semester, IPK nilai terbaik, dan riwayat mata kuliah. Tidak menyimpan IP/IPK permanen.

## 11. Sistem absensi

### Migration pendukung
- `2026_11_17_000015_create_sesi_absensi_table.php`: sesi per kelas dengan `pertemuan_ke` unik per kelas, tanggal, topik, waktu mulai/selesai (WIB), dan status `dibuka`/`ditutup`.
- `2026_11_17_000016_create_absensi_table.php`: satu catatan per peserta per sesi (`absensi_unik`), dengan status `hadir|izin|sakit|alfa`, keterangan, metode `mandiri|otomatis|manual`, `dicatat_pada`, dan `dicatat_oleh` nullable.
- `2026_11_19_000004_create_riwayat_perubahan_absensi_table.php`: tabel audit koreksi. Status lama dan baru, alasan, actor, serta waktu perubahan disimpan permanen; relasi memakai `nullOnDelete` agar riwayat tetap terbaca. Sudah dijalankan (`Ran`, batch 5) ke database utama XAMPP.

### Sesi
- Zona waktu yang dipakai untuk tanggal, waktu mulai, waktu selesai, dan jendela pengisian adalah `Asia/Jakarta` (WIB), meski konfigurasi timezone aplikasi adalah `UTC`.
- Tanggal, waktu mulai, dan waktu selesai wajib diisi; waktu selesai harus lebih besar dari waktu mulai.
- Status sesi hanya `dibuka` atau `ditutup`. Sesi yang sudah ditutup **tidak dapat dibuka kembali**.
- Sesi yang sudah memiliki catatan absensi tidak dihapus sembarangan; hapus hanya untuk sesi tanpa catatan.
- Nomor pertemuan unik per kelas dan divalidasi ulang oleh service ketika membuat/mengubah sesi.

### Hak akses dan pengisian
- Dosen hanya mengelola sesi, peserta, dan rekap pada kelas yang benar-benar diajar (`dosen_id`). ID dari URL divalidasi ulang lewat scoped lookup; kelas milik dosen lain menghasilkan 404.
- Mahasiswa hanya melihat dan mengisi absensi pada kelas yang benar-benar diikuti (`peserta_mata_kuliah`). ID sesi dari URL diverifikasi terhadap daftar kelasnya; sesi milih kelas lain menghasilkan 403.
- Peserta berstatus `aktif` dan `mengulang` boleh absensi; `nonaktif` tidak boleh. Riwayat lama tidak dihapus saat status peserta berubah.
- Pengisian mandiri hanya dapat dilakukan ketika sesi `dibuka` **dan** waktu sekarang berada di dalam rentang `waktu_mulai`–`waktu_selesai` (inklusif).
- Mahasiswa hanya dapat mengirim status `hadir` melalui metode `mandiri`. Nilai `izin`, `sakit`, `alfa`, dan identitas apa pun yang dikirim browser tidak dipercaya; service menetapkan status, peserta, dan pencatat dari data server.
- Satu catatan per peserta per sesi. Duplikasi dicegah oleh kunci unik database, kunci baris saat transaksi, dan pengecekan eksistensi di service.

### Penutupan otomatis
- Command: `php artisan absensi:tutup-otomatis`. Hanya sesi `dibuka` yang waktu selesainya sudah tercapai yang diproses.
- Scheduler terdaftar di `routes/console.php`: `everyMinute()`, `timezone('Asia/Jakarta')`, `withoutOverlapping()`. Di XAMPP, jalankan satu entry cron Windows yang memanggil `php artisan schedule:run` setiap menit dari direktori proyek.
- Penutupan bersifat idempotent dan transaksional: sesi dikunci dengan `lockForUpdate()`, peserta berhak tanpa catatan mendapat `alfa` otomatis (`metode = otomatis`, `dicatat_oleh = null`), sedangkan catatan `hadir`/`izin`/`sakit` tidak diubah.
- Pemanggilan ulang command tidak menghasilkan catatan baru. Duplikasi karena request bersamaan ditangani oleh kunci unik dan diabaikan dengan aman.

### Koreksi dan audit
- Koreksi dosen hanya tersedia setelah sesi ditutup, wajib menyertakan alasan, dan hanya untuk peserta di kelasnya sendiri.
- Setiap koreksi ditulis dalam satu transaksi ke `absensi` dan `riwayat_perubahan_absensi`, menyimpan status sebelum/sesudah, alasan, actor, dan waktu. Mahasiswa tidak memiliki jalur koreksi.

### Rekap
- Rekap menghitung `hadir`, `izin`, `sakit`, `alfa`, dan jumlah sesi yang sudah ditutup. Sesi yang belum ditutup tidak dihitung.
- Peserta yang masuk rekap hanya yang berstatus `aktif` atau `mengulang`.
- Persentase kehadiran = `hadir / jumlah sesi ditutup × 100%`, dibulatkan 2 angka desimal. Jika belum ada sesi ditutup, persentase bernilai `0`.

### Route utama
- Dosen: `dosen.absensi.index`, `dosen.absensi.kelas`, `dosen.absensi.sesi.create|store|show|edit|update|destroy|buka|tutup`, `dosen.absensi.koreksi.form|koreksi`, `dosen.absensi.riwayat`, `dosen.absensi.rekap`.
- Mahasiswa: `mahasiswa.absensi.index|riwayat|rekap|simpan`.
- Panel kanan dashboard dosen menampilkan ringkasan absensi yang bersumber dari query kelas milik dosen yang login.

## 12. Sistem target belajar dan progres materi (Batch C)

### Migration pendukung
- `2026_11_19_000005_add_batch_c_target_dan_progres_materi.php`: migration **additive** pada tabel `target_belajar` (menambah `mata_kuliah_id` nullable dengan `nullOnDelete`, `satuan`, `target_nilai`, `capaian` default 0, `tanggal_mulai`, `deadline`, serta indeks `(mahasiswa_id, status)` dan `(mahasiswa_id, deadline)`) dan membuat tabel `aktivitas_materi` (`materi_pembelajaran_id` dan `peserta_mata_kuliah_id` dengan `cascadeOnDelete`, `jenis`, `timestamps`, indeks ringkas dan indeks waktu).
- Tidak ada kolom lama yang dihapus atau diubah tipenya. Nilai enum lama `belum_selesai` tetap dipertahankan sebagai status "Belum dimulai" agar data existing tetap terbaca.
- Sudah dijalankan (`Ran`, batch 6) ke database utama XAMPP. Seluruh pengujian otomatis memakai SQLite `:memory:` melalui `RefreshDatabase`.
- Semua nama constraint dan index sudah diaudit (maksimal 47 karakter, di bawah batas 64 karakter MySQL/MariaDB) dan DDL sudah dikompilasi dengan `php artisan migrate --pretend --no-interaction` tanpa dijalankan.

### Target belajar (pribadi, manual)
- Route: `mahasiswa.target.index|create|store|show|edit|update|destroy|capaian|selesai|batalkan-selesai|batalkan`.
- Target bersifat pribadi: seluruh query dan aksi dibatasi oleh `mahasiswa_id` dari auth user. Target milik mahasiswa lain menghasilkan 404 (atau 403 pada FormRequest otorisasi).
- `TargetBelajarRequest` dan turunannya menolak `status`, `progres`, `selesai_pada`, dan `mahasiswa_id` dari browser. Mata kuliah wajib berasal dari kelas aktif yang benar-benar diikuti mahasiswa.
- Validasi: judul, satuan, dan nilai target wajib; nilai target harus positif; capaian tidak boleh negatif atau melebihi target; `deadline` tidak boleh mendahului `tanggal_mulai`.
- Status efektif dihitung server-side di `TargetBelajarService`, bukan dipercaya dari request: `belum_selesai` (Belum dimulai), `berlangsung`, `selesai`, `terlambat`, dan `dibatalkan`. Capaian yang mencapai/melampaui target menghasilkan `selesai`; deadline terlewat dan belum selesai menghasilkan `terlambat`.
- Persentase progres = `min(100, capaian / target_nilai × 100)` dibulatkan ke integer. Tanpa `target_nilai` yang valid, persentase bernilai 0.
- `tandaiSelesai` adalah penanda manual dan **tidak mengubah capaian** (riwayat capaian tetap jujur). Pembatalan status selesai hanya tersedia untuk penyelesaian manual; status `selesai` yang tercapai otomatis dari capaian tidak dapat dibatalkan tanpa menurunkan capaian.
- `batalkan` menandai target sebagai `dibatalkan` dan tidak dapat diaktifkan kembali; `destroy` menghapus baris secara permanen. Keduanya memerlukan konfirmasi di UI.
- Semua penulisan target memakai transaksi database. Penyimpanan dan pembaruan menerapkan status efektif sebelum `save()`.

### Target otomatis (progres materi)
- Route: `mahasiswa.progres-materi.index`, `mahasiswa.materi.show`, `mahasiswa.materi.progres.selesai`, `mahasiswa.materi.progres.batalkan`.
- Target otomatis **tidak disalin** ke tabel `target_belajar`. Angkanya dihitung langsung dari `materi_pembelajaran` yang sudah dipublikasikan pada kelas aktif yang diikuti mahasiswa dan `progres_materi`.
- Materi draft (`dipublikasikan_pada = NULL`) dan materi yang dijadwalkan belum terbit tidak dihitung.
- Ringkasan disajikan per mata kuliah/kelas (total materi, selesai, belum selesai, persentase) dan agregat global. Persentase = `selesai / total × 100`, dibulatkan 2 angka desimal; tanpa materi bernilai 0.
- `ProgresMateriService` memvalidasi ulang Peserta aktif + kelas + publikasi lewat scoped lookup sebelum setiap aksi; pelanggaran menghasilkan 404.
- Menandai selesai memakai satu baris `progres_materi` per `(materi_pembelajaran_id, peserta_mata_kuliah_id)` (kunci unik existing). Permintaan berulang idempotent dan tidak membuat baris ganda.
- Pembatalan penyelesaian **mempertahankan baris progres** sebagai riwayat, mengembalikan status ke `belum_dimulai`, progres 0, dan `selesai_pada = NULL`.
- Query daftar dan ringkasan memakai eager loading/agregasi untuk menghindari N+1.

### Aktivitas baca/unduh
- Tabel `aktivitas_materi` menyimpan satu baris per kejadian sukses dengan `jenis = dibuka|diunduh` (enum `JenisAktivitasMateri`).
- Membuka detail materi dicatat di `ProgresMateriService::detail()` hanya setelah otorisasi, kecocokan kelas, dan publikasi lolos.
- Mengunduh dicatat di `MateriPembelajaranService::unduh()` hanya setelah otorisasi, kecocokan kelas, publikasi, path, dan eksistensi file lolos.
- Akses gagal (kelas salah, mahasiswa lain, materi draft, berkas tidak ada) **tidak** menghasilkan catatan aktivitas.
- Aktivitas buka/unduh **tidak pernah menandai materi selesai** dan tidak mengubah `progres_materi`. Detail materi menampilkan jumlah dan waktu terakhir dibuka/diunduh.

### Hak akses dan integritas data
- Seluruh route Batch C berada di grup middleware `auth`, `active`, `role:mahasiswa`.
- FormRequest (`TargetBelajar*Request`, `PerbaruiCapaianTargetRequest`, `ProgresMateriRequest`) memeriksa role dan kepemilikan; service melakukan scoped lookup ulang sebagai pertahanan kedua.
- Identitas mahasiswa, status, capaian dari pihak browser tidak dipercaya. Nilai yang dikirim browser divalidasi, lalu status dan progres dihitung ulang oleh server.
- Semua aksi tulis memakai transaksi. Tidak ada perubahan pada autentikasi, `.env`, `phpunit.xml`, dependency, atau `package.json`.

### Tampilan dan dashboard
- Menu sidebar `Target Belajar` dan `Progres Materi` diaktifkan; breadcrumb topbar diperbarui untuk route baru.
- Halaman target: daftar dengan filter status + mata kuliah dan pagination, tambah, edit, detail dengan pembaruan capaian serta aksi status.
- Halaman progres materi: ringkasan target otomatis per kelas, daftar materi belum selesai, dan tautan ke detail materi.
- Detail materi menampilkan status/progres, aktivitas baca/unduh, tautan unduh, dan aksi tandai selesai/batalkan.
- Dashboard mahasiswa menambah ringkasan Target Belajar dan Progres Materi Otomatis tanpa menggeser kalender akademik sebagai elemen dominan. Angka yang ditampilkan bersumber dari query nyata.
- State yang tersedia: loading (progres bar), empty state, validasi, pesan sukses (`status`), dan pesan error (`error`).

### Pengujian
- `tests/Feature/MahasiswaTargetBelajarTest.php`: 15 test (CRUD, otorisasi, validasi, status, capaian, UI).
- `tests/Feature/MahasiswaProgresMateriTest.php`: 13 test (aktivitas buka/unduh, akses gagal, publikasi, idempotensi, target otomatis, dashboard).
- Helper bersama: `tests/Concerns/MembuatDataBatchC.php`.
- Regresi: `MahasiswaMateriPembelajaranTest` dan `MahasiswaDashboardTest` tetap lolos setelah integrasi.
- Pemeriksaan yang dijalankan: `php artisan test`, `php artisan route:list`, `php artisan view:cache`, `npm run build`, `git diff --check`, dan PHP lint untuk file PHP baru/diubah.

## 13. Jadwal ujian UTS/UAS (kalender akademik)

### Migration pendukung
- `2026_11_19_000006_add_tahun_akademik_id_to_agenda_akademik_table.php`: migration **additive** yang menambah `tahun_akademik_id` (FK nullable ke `tahun_akademik`, `restrictOnDelete`) dan index pada `agenda_akademik`.
- Backfill aman: baris lama dengan kelas terhubung mengambil periode dari relasi kelas bila pasti; baris institusional mengambil periode dari judul hanya bila cocok dengan data `tahun_akademik`; selain itu dibiarkan `NULL`. Suffix judul ganda hasil backfill dirapikan. Tidak ada kolom lama yang dihapus atau diubah tipenya, dan tidak ada unique constraint baru.
- Sudah dijalankan (`Ran`, batch 7) ke database utama XAMPP. Seluruh pengujian otomatis memakai SQLite `:memory:` melalui `RefreshDatabase`.

### Alur admin (CRUD)
- Route: `admin.jadwal-ujian.index|create|store|edit|update|destroy`.
- `JadwalUjianService` menyimpan satu baris `agenda_akademik` per jadwal ujian dengan `jenis = ujian`, `jenis_ujian = uts|uas`, `kelas_mata_kuliah_id = NULL`, dan `tahun_akademik_id` dari relasi periode.
- FormRequest memvalidasi enum ujian, tahun akademik, semester yang konsisten dengan tahun, tanggal, jam, lokasi, dan status publikasi. Identitas admin berasal dari auth, bukan dari browser.
- Validasi bentrok memakai `whereDate()` agar perbandingan tanggal konsisten pada SQLite dan MySQL/MariaDB, mengecualikan record yang sedang diedit, dan berjalan di dalam transaksi dengan `GET_LOCK` per lokasi + `lockForUpdate()`.
- Halaman admin menyediakan tab Kalender/Timeline, filter UTS/UAS dan periode, empty state, serta tautan kelola jadwal dari dashboard.

### Alur dosen dan mahasiswa (read-only)
- Route GET-only: `dosen.jadwal-ujian` dan `mahasiswa.jadwal-ujian`. Tidak ada route turunan store/update/destroy; method tulis menghasilkan 405, dan akses ke endpoint admin menghasilkan 403.
- `JadwalUjianPageService` bersama membatasi data pada agenda institusional plus kelas yang diajar (dosen) atau diikuti (mahasiswa), lalu menerapkan filter `jenis_ujian` dan `tahun_akademik`.
- Halaman memakai komponen kalender yang sudah ada, menampilkan empty state, label/warna UTS/UAS yang konsisten, breadcrumb dan sidebar aktif yang benar, serta tampilan responsif.

### Pengujian
- `tests/Feature/AdminJadwalUjianTest.php`: CRUD, otorisasi, validasi periode, dan matriks bentrok (lokasi sama/waktu berbeda, lokasi berbeda, overlap sebagian, multi-hari, case-insensitive, agenda tanpa jam, pengecualian record sendiri).
- `tests/Feature/KalenderUjianTest.php`: scoping per role, filter, event ID kanonik `agenda-{id}`, label/warna, empty state, dan jalur read-only.
- Regresi: `MahasiswaDashboardTest` tetap lolos setelah sidebar `Jadwal Ujian` diaktifkan.

## 14. Pengumuman akademik dan notifikasi otomatis (Batch D)

### Sumber data dan status
- Pengumuman memakai **satu sumber data**: `agenda_akademik` dengan `jenis = pengumuman`. Tidak ada tabel pengumuman terpisah.
- `kelas_mata_kuliah_id = NULL` berarti pengumuman akademik/global; nilai non-null berarti pengumuman kelas tertentu.
- Status diturunkan dari `dipublikasikan_pada` lewat enum `StatusPublikasi` (`draft`, `terbit`, `terjadwal`):
  - `NULL` = draft,
  - waktu lampau = terbit,
  - waktu depan = terjadwal.
- Tidak ada kolom status baru dan tidak ada migration baru pada Batch D. Status migration `000006` adalah `Ran` (batch 7).
- Pengumuman `terjadwal` belum memiliki scheduler aktif; notifikasi terjadi saat transaksi/status menjadi terbit oleh aksi pengguna.

### Alur dan hak akses
- Admin: `admin.pengumuman.index|create|store|show|edit|update|destroy` untuk pengumuman global maupun kelas.
- Dosen: `dosen.pengumuman.index|create|store|show|edit|update|destroy`, dibatasi hanya pada kelas yang diajar. `PengumumanRequest` dosen memvalidasi `kelas_mata_kuliah_id` melalui `MataKuliahService::milikDosen()` sehingga kelas dosen lain menghasilkan 404, lalu service melakukan scoped lookup ulang.
- Mahasiswa: `mahasiswa.pengumuman.index|show` bersifat read-only, hanya menampilkan pengumuman terbit yang global atau dari kelas yang diikuti dengan status peserta `aktif`. Draft, pengumuman terjadwal, dan pengumuman kelas lain menghasilkan 404 atau tidak ditampilkan.
- `PengumumanService` menjadi penulis tunggal pengumuman; seluruh aksi tulis memakai transaksi dan `DB::afterCommit()` untuk efek notifikasi.
- `FormRequest` (`App\Http\Requests\Admin\PengumumanRequest` dan `App\Http\Requests\Dosen\PengumumanRequest`) memvalidasi judul, isi, target kelas, status, tanggal publikasi, dan periode agenda.

### Notifikasi in-app
- Tabel kustom `notifikasi` tetap dipertahankan. Aplikasi **tidak** memakai Laravel Database Notifications / tabel `notifications` / event-listener duplikat. Trait `Notifiable` hanya untuk reset password dan notifikasi email bawaan.
- `App\Enums\JenisNotifikasi`: `pengumuman`, `tugas`, `ujian`, `nilai`.
- `App\Services\Notifikasi\NotifikasiService` adalah penulis tunggal notifikasi. Pemilik (`user_id`), status baca, dan tautan ditentukan server-side dengan `forceFill()`.
- Penerima dibatasi pada mahasiswa aktif. Untuk notifikasi kelas, hanya peserta dengan status `aktif` yang dipilih; mahasiswa dengan status lain tidak menerima notifikasi.
- Dedup berdasarkan pasangan `user_id` + `tipe` + `data->sumber`. Kunci sumber stabil untuk setiap peristiwa, misalnya `pengumuman:{id}`, `tugas:{id}:dipublikasikan`, `tugas:{id}:deadline:{waktu}`, `ujian:{id}:{tanda}`, dan `nilai:{id}:{komponen}:{waktu}`.
- Tautan hanya path internal `/mahasiswa` atau `/mahasiswa/...` yang divalidasi `tautanAman()`; tautan eksternal, protocol-relative, traversal, dan obfuskasi ditolak saat penulisan.
- Notifikasi ditulis setelah transaksi sumber berhasil di-commit. Karena itu draft, kegagalan transaksi, dan aksi yang tidak mengubah data tidak menghasilkan notifikasi.

### Pemicu notifikasi
- Pengumuman menjadi terbit (global atau kelas) → notifikasi `pengumuman`.
- Tugas pertama kali dipublikasikan atau berubah dari draft menjadi terbit → notifikasi `tugas` ke peserta aktif kelas.
- Deadline tugas berubah pada tugas yang sudah terbit → notifikasi `tugas` dengan kunci sumber baru.
- Jadwal UTS/UAS institusional dibuat atau diubah secara relevan → notifikasi `ujian` ke seluruh mahasiswa aktif.
- Nilai UTS/UAS baru atau berubah dipublikasikan → notifikasi `nilai` hanya kepada mahasiswa pemilik nilai.

### Yang sengaja tidak memicu notifikasi
- Pengumuman draft, pengumuman terjadwal yang belum terbit, dan pengumuman yang diubah tanpa transisi status terbit.
- Simpan ulang tanpa perubahan data, perubahan judul tugas saja, serta perubahan jadwal ujian yang tidak relevan.
- Submit tugas, tandai materi selesai/batalkan, aktivitas baca materi, dan unduh materi/lampiran.
- Target belajar, progres materi, aktivitas administrasi bukan akademik, dan notifikasi untuk peran selain mahasiswa melalui writer ini.

### Halaman notifikasi mahasiswa
- Route: `mahasiswa.notifikasi`, `mahasiswa.notifikasi.baca`, `mahasiswa.notifikasi.baca-semua`.
- Daftar memakai pagination (10 per halaman) dengan filter status dan tipe, serta empty state.
- `NotifikasiController` hanya mengubah baris milik `user_id` yang sedang login; IDOR dicegah di backend.
- Tombol “Tandai semua dibaca” memakai `NotifikasiService::tandaiSemuaDibaca()` pada transaksi terscoped.
- `unreadCount` pada application shell dan dashboard dihitung dari query yang sama sehingga tetap konsisten setelah aksi baca.

### Integrasi tampilan
- Sidebar dan breadcrumb topbar menampilkan menu Pengumuman untuk ketiga role; mahasiswa juga memiliki tautan Notifikasi.
- View baru: `admin/pengumuman/{index,form,show}`, `dosen/pengumuman/{index,form,show}`, `mahasiswa/pengumuman/{index,show}`.
- Halaman notifikasi mahasiswa memakai komponen/view yang sudah ada dengan Label enum `JenisNotifikasi`, pagination, dan state kosong.
- Dashboard tidak ditambahi kartu statistik baru; kalender tetap menjadi elemen dominan. Tema Light Lavender, Plus Jakarta Sans, dan komponen existing dipertahankan.

### Pengujian
- `tests/Feature/PengumumanBatchDTest.php`: matriks akses backend, scoped lookup dosen, draft/terbit, target aktif, dedup, filter, pagination, empty state, dan mark-read.
- `tests/Feature/NotifikasiOtomatisBatchDTest.php`: pemicu tugas, deadline, UTS/UAS, nilai, dedup, serta negatif untuk baca/submit/unduh.
- Helper bersama: `tests/Concerns/BatchDData.php`.
- Regresi yang dijalankan: `AdminJadwalUjianTest`, `MahasiswaPengumpulanTugasTest`, `MahasiswaDashboardTest`, dan `ModelHardeningTest`.
- Pemeriksaan akhir: `php artisan test` (473 test, 3270 assertion, semua lolos), `php artisan route:list` (164 route), `php artisan view:cache`, `php artisan view:clear`, `npm run build`, `git diff --check`, dan PHP lint untuk file PHP yang diubah tidak menemukan masalah.
