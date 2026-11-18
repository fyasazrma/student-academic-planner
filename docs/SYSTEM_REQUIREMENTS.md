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
| 3 | `mata_kuliah` | Data mata kuliah |
| 4 | `kelas_mata_kuliah` | Kelas per mata kuliah dan dosen |
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

## 8. Batasan fitur
- Jangan menambahkan pembayaran, chat real-time, forum, push notification, atau fitur lain yang belum diminta.
- Jangan menambahkan registrasi mandiri.
- Jangan menganggap mahasiswa boleh mengubah nilai.
- Jangan menganggap mahasiswa boleh mengelola kelas atau agenda institusi.
- Jangan menampilkan angka statistik tanpa sumber query nyata.
- Jika ada fitur baru yang diperlukan, jelaskan manfaat, dampak database, dan dampak akses sebelum implementasi.
