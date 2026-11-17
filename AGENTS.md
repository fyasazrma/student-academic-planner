# AGENTS.md — Student Academic Planner & E-Learning System

Dokumen ini adalah instruksi utama untuk AI coding agent (OpenCode dan Antigravity) saat bekerja di repositori ini. Baca dokumen ini dan dokumen terkait sebelum mengubah kode.

## 1. Konteks proyek
Bangun aplikasi web **Student Academic Planner & E-Learning System** menggunakan Laravel, Blade, Tailwind CSS, dan MySQL. Aplikasi memiliki tiga role: `mahasiswa`, `dosen`, dan `admin`.

Tujuan produk:
- Membantu mahasiswa mengatur aktivitas akademik dan tugas.
- Membantu dosen mengelola kelas, materi, tugas, pengumpulan, nilai, dan absensi.
- Membantu admin mengelola data dan agenda akademik.

Tidak ada pendaftaran akun publik. Akun mahasiswa dan dosen dibuat oleh admin.

## 2. Sumber kebenaran dan prioritas
Sebelum bekerja:
1. Periksa struktur proyek dan teknologi yang benar-benar terpasang.
2. Baca `docs/SYSTEM_REQUIREMENTS.md`.
3. Baca `docs/UI_UX_GUIDELINES.md` dan `docs/DESIGN_SYSTEM.md` untuk pekerjaan antarmuka.
4. Periksa implementasi yang sudah ada sebelum membuat file atau komponen baru.

Jika instruksi bertentangan, ikuti urutan:
1. Permintaan terbaru pengguna yang eksplisit.
2. Aturan keamanan, otorisasi, dan integritas data dalam dokumen ini.
3. `docs/SYSTEM_REQUIREMENTS.md`.
4. `docs/UI_UX_GUIDELINES.md` dan `docs/DESIGN_SYSTEM.md`.
5. Pola kode yang sudah ada di proyek.

Jangan menganggap contoh atau rancangan sebagai bukti bahwa fitur sudah tersedia. Verifikasi kode aktual.

## 3. Aturan kerja wajib
- Jangan menebak struktur folder, route, nama model, kolom database, package, atau versi framework. Periksa dahulu.
- Jangan menimpa atau menghapus pekerjaan yang sudah ada tanpa alasan dan persetujuan yang jelas.
- Jangan mengganti arsitektur, package, sistem autentikasi, atau konfigurasi besar hanya demi mempermudah satu halaman.
- Kerjakan perubahan sekecil mungkin dan sesuai ruang lingkup permintaan.
- Sebelum mengubah banyak file, jelaskan rencana singkat dan file yang terdampak.
- Jika ada keputusan penting yang belum jelas, tanyakan satu pertanyaan paling penting. Untuk detail kecil, pilih solusi sederhana yang konsisten dan jelaskan asumsi.
- Jangan membuat tombol, menu, filter, form, atau aksi palsu. Jika belum terhubung, tandai sebagai belum aktif atau implementasikan alur yang benar.
- Jangan mengisi aplikasi dengan data contoh permanen. Data dummy hanya untuk seeder/demo dan harus diberi label.
- Jangan mengubah database/backend saat pengguna hanya meminta perubahan visual, kecuali perubahan itu benar-benar diperlukan dan disetujui.
- Setelah perubahan, jalankan pemeriksaan yang relevan (route, migration status, test, build, atau lint sesuai tool yang tersedia). Laporkan apa yang berhasil dan apa yang belum diuji.
- Jangan menyatakan pekerjaan selesai jika belum diverifikasi.

## 4. Aturan teknologi dan struktur
- Utamakan Laravel conventions, Blade components, named routes, Form Request validation, middleware/policies, Eloquent relationships, dan migration.
- Gunakan komponen Blade reusable untuk elemen yang berulang: sidebar, topbar, breadcrumb, page header, stat card, button, form field, modal, table, badge, empty state, dan calendar event.
- Gunakan Tailwind CSS sesuai versi yang sudah terpasang. Jangan mengasumsikan konfigurasi Tailwind versi tertentu.
- Gunakan ikon dari satu library yang sudah terpasang. Jangan mencampur emoji, SVG acak, dan beberapa library ikon tanpa alasan.
- Pertahankan Plus Jakarta Sans jika sudah tersedia/ditetapkan; jangan menambah font eksternal baru tanpa kebutuhan.
- Gunakan nama route, variabel, dan komponen yang jelas dan konsisten.
- Hindari duplikasi markup dan inline style yang tidak perlu.
- Pastikan layout tetap usable pada desktop, tablet, dan mobile.

## 5. Hak akses dan keamanan
- Semua route privat harus membutuhkan autentikasi.
- Otorisasi wajib dilakukan di backend, bukan hanya menyembunyikan menu di frontend.
- Mahasiswa hanya boleh melihat/mengelola data miliknya sendiri sesuai fitur.
- Dosen hanya boleh mengelola kelas yang menjadi tanggung jawabnya.
- Admin memiliki akses pengelolaan sistem sesuai kebutuhan.
- Jangan mempercayai `user_id`, `mahasiswa_id`, `dosen_id`, role, nilai, atau status yang dikirim dari browser tanpa validasi server.
- Gunakan CSRF protection, validasi input, mass-assignment protection, dan validasi file upload.
- Batasi tipe dan ukuran file upload serta simpan file melalui mekanisme Laravel Storage.
- Jangan pernah menaruh password, API key, token, atau kredensial di source code atau file yang dikomit.
- Gunakan hashing password bawaan Laravel.
- Reset password melalui mekanisme aman; email reset password berbeda dari notifikasi akademik dalam aplikasi.

## 6. Aturan kalender akademik — fitur inti
Kalender akademik adalah komponen paling penting pada dashboard mahasiswa, dosen, dan admin. Jangan memperlakukannya sebagai widget dekoratif.

Kalender setidaknya mendukung:
- Tampilan bulanan dan mingguan; tampilan harian dapat ditambahkan bila bermanfaat.
- Navigasi periode sebelumnya/berikutnya dan tombol kembali ke hari ini.
- Legenda warna dan filter jenis agenda.
- Detail agenda saat item dipilih.
- Empty state ketika tidak ada agenda.
- Responsif pada layar kecil; jangan memaksa kalender desktop mengecil hingga sulit dibaca.

Jenis agenda yang perlu dibedakan:
- Tugas: tampilkan tanggal mulai/dibuat dan deadline secara terpisah; visualisasikan rentang pengerjaan jika sesuai.
- Ujian: tanggal dan jam ujian.
- Jadwal kuliah: hari, jam, kelas, dan mata kuliah.
- Agenda akademik: tanggal mulai dan selesai.
- Pengumuman: tanggal publikasi, bukan dianggap sebagai deadline.

Tanggal tugas dibuat dan deadline adalah dua data berbeda. Jangan menyamakan atau menghilangkan salah satunya. Jika data backend belum mendukung rentang tugas, laporkan kebutuhan perubahan data sebelum mengubah schema.

Kalender harus menggunakan data yang relevan untuk role:
- Mahasiswa: agenda akademik, kelas yang diikuti, jadwalnya, tugas dan deadline miliknya.
- Dosen: kelas yang diajar, jadwal mengajar, tugas yang dibuat, deadline, ujian, dan agenda relevan.
- Admin: kalender akademik institusi dan jadwal lintas kelas sesuai hak akses.

Gunakan zona waktu aplikasi yang konsisten. Jangan mengarang tanggal, agenda, atau status.

## 7. Dashboard per role
Dashboard harus punya kerangka visual yang konsisten, tetapi informasi dan tindakan disesuaikan dengan role.

### Mahasiswa
Prioritas: kalender akademik, deadline terdekat, tugas belum dikumpulkan, jadwal kuliah/ujian, progres materi, ringkasan nilai/absensi, notifikasi.

### Dosen
Prioritas: kalender, jadwal mengajar, tugas yang dibuat, status/jumlah pengumpulan, ujian, ringkasan absensi, progres mahasiswa, notifikasi.

### Admin
Prioritas: kalender akademik institusi, statistik pengguna, mahasiswa/dosen, mata kuliah, tahun akademik/semester, jadwal, dan agenda.

Jangan menampilkan statistik yang tidak didukung query/data nyata. Jangan menampilkan data role lain tanpa alasan dan otorisasi.

## 8. Aturan UI/UX
- Ikuti `docs/UI_UX_GUIDELINES.md` dan `docs/DESIGN_SYSTEM.md`.
- Arah visual: dark charcoal dengan aksen indigo/ungu, bersih, modern, profesional, dan mudah dibaca.
- Gunakan Plus Jakarta Sans jika sudah tersedia.
- Gunakan sidebar kiri dan topbar sebagai pola utama aplikasi.
- Kalender harus menjadi elemen dominan dashboard, bukan kartu kecil yang tenggelam.
- Gunakan spacing, radius, ukuran heading, warna, dan state komponen secara konsisten.
- Hindari gradient berlebihan, glow, glassmorphism berat, dekorasi yang tidak membantu, dan terlalu banyak kartu statistik.
- Utamakan kontras teks, keterbacaan, fokus keyboard, label form, dan feedback aksi.
- Jangan menyalin mentah gambar referensi; adaptasikan ke kebutuhan dan data aplikasi.
- Jangan mengubah tema, struktur navigasi, atau pola komponen yang sudah disetujui tanpa menjelaskan alasan dan meminta persetujuan.

## 9. Proses implementasi halaman
Untuk setiap halaman:
1. Identifikasi role dan tujuan pengguna.
2. Identifikasi data yang dibutuhkan dan sumber datanya.
3. Tentukan hierarki konten dan tindakan utama.
4. Reuse komponen yang tersedia.
5. Implementasikan loading, empty, success, error, dan validation states yang relevan.
6. Pastikan responsif dan aksesibel.
7. Periksa otorisasi backend untuk semua aksi.
8. Uji interaksi utama dan laporkan hasilnya.

## 10. Format laporan setelah bekerja
Sampaikan secara ringkas:
- Apa yang diubah.
- File utama yang dibuat/diubah.
- Cara menjalankan atau melihat hasil.
- Pemeriksaan yang dijalankan dan hasilnya.
- Kekurangan atau keputusan yang masih menunggu persetujuan.

Jangan mengklaim sudah menjalankan perintah atau test jika tidak benar-benar dijalankan.
