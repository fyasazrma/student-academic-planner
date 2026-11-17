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
3. Kalender akademik.
4. Mata kuliah.
5. Materi pembelajaran.
6. Tugas dan pengumpulan.
7. Jadwal ujian.
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
7. Jadwal ujian.
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
8. Kalender akademik.

Admin mengelola data dan agenda sesuai kewenangannya.

## 5. Aturan bisnis penting
- Tidak ada registrasi publik.
- Mahasiswa hanya melihat nilai; dosen mengelola nilai.
- Mahasiswa tidak boleh mengubah absensi mahasiswa lain.
- Dosen dapat mengelola absensi kelas yang diajar.
- Notifikasi akademik bersifat in-app; tidak perlu push notification/FCM.
- Email reset password boleh digunakan dan tidak sama dengan notifikasi akademik.
- Tugas memiliki tanggal dibuat/publikasi dan deadline yang terpisah.
- Materi diunggah dosen untuk kelas tertentu; progres baca dicatat per mahasiswa.
- Pengingat deadline tugas dan ujian dapat muncul di notifikasi aplikasi.

## 6. Konsep data/entitas
Rancangan ERD ringkas yang pernah disepakati berisi:
1. `pengguna`
2. `tahun_akademik`
3. `mata_kuliah`
4. `kelas_mata_kuliah`
5. `peserta_mata_kuliah`
6. `jadwal_kuliah`
7. `materi_pembelajaran`
8. `progres_materi`
9. `tugas`
10. `pengumpulan_tugas`
11. `agenda_akademik`
12. `target_belajar`
13. `notifikasi`
14. `nilai`
15. `sesi_absensi`
16. `absensi`

Ini adalah rancangan konseptual, bukan bukti bahwa migration/model sudah dibuat. Periksa schema aktual sebelum mengimplementasikan.

## 7. Batasan fitur
- Jangan menambahkan pembayaran, chat real-time, forum, push notification, atau fitur lain yang belum diminta.
- Jangan menambahkan registrasi mandiri.
- Jangan menganggap mahasiswa boleh mengubah nilai.
- Jangan menganggap mahasiswa boleh mengelola kelas atau agenda institusi.
- Jika ada fitur baru yang diperlukan, jelaskan manfaat, dampak database, dan dampak akses sebelum implementasi.
