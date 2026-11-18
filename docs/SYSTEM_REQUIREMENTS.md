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

Ini adalah rancangan yang sudah diimplementasikan dalam migration. Namun, selalu periksa file migration dan model aktual sebelum mengimplementasikan fitur.

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
