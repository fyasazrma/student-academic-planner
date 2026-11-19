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
| 11 | `agenda_akademik` | Agenda: akademik, ujian (UTS/UAS), pengumuman |
| 12 | `target_belajar` | Target belajar mahasiswa |
| 13 | `notifikasi` | Notifikasi in-app |
| 14 | `nilai` | Nilai per mahasiswa per komponen |
| 15 | `sesi_absensi` | Sesi absensi yang dibuat dosen |
| 16 | `absensi` | Catatan kehadiran mahasiswa per sesi |
| 17 | `riwayat_perubahan_nilai` | Audit pembuatan/perubahan UTS/UAS dan konfigurasi komponen tugas |
| 18 | `riwayat_perubahan_absensi` | Audit koreksi absensi (status lama, status baru, alasan, actor, waktu) |

Ini adalah rancangan yang sudah diimplementasikan dalam migration. Namun, selalu periksa file migration dan model aktual sebelum mengimplementasikan fitur. Tabel `riwayat_perubahan_absensi` masih berupa **migration file Pending** dan belum dijalankan ke database utama XAMPP; seluruh pengujian otomatis memakai SQLite `:memory:` melalui `RefreshDatabase`.

## 7. Catatan schema aktual (gap yang perlu diperhatikan)

### UTS vs UAS di agenda_akademik
- Enum `JenisAgenda` saat ini memiliki nilai: `akademik`, `ujian`, `pengumuman`.
- UTS dan UAS **belum dibedakan secara eksplisit** di level enum — keduanya menggunakan `jenis = ujian`.
- Implementasi UI saat ini menggunakan field `judul` untuk membedakan UTS dari UAS.
- **Jika dibutuhkan pembedaan di level schema** (contoh: menambahkan nilai enum `uts` dan `uas`), laporkan kebutuhan ini sebelum mengubah migration atau enum. Jangan mengubah schema tanpa persetujuan eksplisit.

### Jadwal kuliah dan ujian
- Tabel `jadwal_kuliah` menyimpan jadwal reguler kuliah/praktikum.
- Jadwal ujian (UTS/UAS) disimpan di `agenda_akademik` dengan `jenis = ujian`.
- Pastikan implementasi UI tidak mencampur dua sumber data ini tanpa label yang jelas.

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
- `2026_11_19_000004_create_riwayat_perubahan_absensi_table.php`: tabel audit koreksi. Status lama dan baru, alasan, actor, serta waktu perubahan disimpan permanen; relasi memakai `nullOnDelete` agar riwayat tetap terbaca. **Masih Pending** pada database utama XAMPP dan menunggu persetujuan untuk dijalankan.

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
