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
- Gunakan komponen Blade reusable untuk elemen yang berulang: sidebar, topbar, breadcrumb, page header, stat card, button, form field, modal, table, badge, empty state, calendar event, dan timeline bar.
- Gunakan Tailwind CSS sesuai versi yang sudah terpasang (Tailwind v4 dengan @tailwindcss/vite). Jangan mengasumsikan konfigurasi versi lain.
- Gunakan ikon dari satu library yang sudah terpasang. Jangan mencampur emoji, SVG acak, dan beberapa library ikon tanpa alasan. Satu-satunya pengecualian emoji: 👋 pada sapaan dashboard.
- Pertahankan Plus Jakarta Sans; font ini sudah dikonfigurasi via Bunny Fonts di vite.config.js. Jangan menambah font eksternal baru tanpa kebutuhan.
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
Kalender akademik adalah komponen paling penting pada dashboard mahasiswa, dosen, dan admin. Jangan memperlakukannya sebagai widget dekoratif. Kalender harus mendapat area visual yang dominan di dashboard.

Kalender memiliki dua perspektif yang saling melengkapi:

### 6a. Calendar View
- Tampilan bulanan dan mingguan; tampilan harian dapat ditambahkan bila bermanfaat.
- Tampilan agenda/list sebagai alternatif, terutama untuk mobile.
- Navigasi periode sebelumnya/berikutnya dan tombol kembali ke hari ini.
- Legenda warna dan filter jenis agenda.
- Detail agenda saat item dipilih (modal atau drawer).
- Empty state ketika tidak ada agenda.
- Responsif pada layar kecil; pada mobile gunakan agenda/list yang mudah dibaca.

### 6b. Timeline View
- Bar horizontal menampilkan rentang pengerjaan tugas dari tanggal dipublikasikan hingga deadline.
- Sumbu horizontal adalah skala tanggal.
- Setiap bar tugas menampilkan: nama tugas, mata kuliah, tanggal dipublikasikan, deadline, dan status.
- Penanda visual membedakan titik publikasi dan titik deadline secara eksplisit.
- Status tugas (belum dikerjakan, sedang dikerjakan, sudah dikumpulkan, terlambat) tercermin pada warna/ikon bar.
- Ujian (UTS/UAS) tampil sebagai penanda titik pada timeline, bukan bar rentang.

### 6c. Jenis agenda yang dibedakan

**TUGAS:**
- `dipublikasikan_pada`: kapan tugas mulai dapat dilihat dan dikerjakan mahasiswa.
- `deadline`: batas waktu pengumpulan.
- Kedua tanggal ini **wajib dibedakan secara visual** dan tidak boleh digabungkan atau disamakan.
- Informasi tambahan: judul, mata kuliah/kelas, status pengumpulan mahasiswa.
- Data bersumber dari tabel `tugas` dan `pengumpulan_tugas`.

**UTS (Ujian Tengah Semester):**
- Tanggal ujian, jam mulai, jam selesai.
- Mata kuliah, lokasi atau metode (online/offline) jika tersedia di schema.
- Saat ini disimpan di `agenda_akademik` dengan `jenis = ujian`; label UTS dibedakan via field `judul`.
- **Gap schema:** Enum `JenisAgenda` belum membedakan UTS dan UAS secara eksplisit. Jika diperlukan pembedaan level schema, laporkan sebagai kebutuhan perubahan sebelum mengubah migration.

**UAS (Ujian Akhir Semester):**
- Tanggal ujian, jam mulai, jam selesai.
- Mata kuliah, lokasi atau metode (online/offline) jika tersedia di schema.
- Saat ini disimpan di `agenda_akademik` dengan `jenis = ujian`; label UAS dibedakan via field `judul`.

**Catatan penilaian UTS/UAS:** jadwal ujian institusional tetap bersumber dari `agenda_akademik`, sedangkan nilai UTS/UAS mahasiswa disimpan di tabel `nilai` dengan `komponen = uts|uas`. Gap enum `JenisAgenda` di atas tidak diubah oleh fitur penilaian; jangan menyamakan jadwal ujian dengan nilai ujian.

**JADWAL KULIAH:**
- Data dari tabel `jadwal_kuliah`.
- Hari dan jam, mata kuliah dan kelas, ruangan atau link jika tersedia.
- Jenis: kuliah, praktikum, atau lainnya (sesuai `JenisJadwal` enum: `kuliah`, `praktikum`, `lainnya`).

**AGENDA AKADEMIK INSTITUSI:**
- Judul, keterangan, tanggal mulai, tanggal selesai.
- Agenda institusi ditandai dengan `kelas_mata_kuliah_id = NULL` di tabel `agenda_akademik`.

**PENGUMUMAN:**
- Data dari `agenda_akademik` dengan `jenis = pengumuman`.
- Tanggal dipublikasikan adalah tanggal publikasi, bukan deadline.
- Jangan memperlakukan tanggal publikasi pengumuman sebagai deadline.

### 6d. Data per role
- **Mahasiswa:** agenda akademik institusi, kelas yang diikuti (via `peserta_mata_kuliah`), jadwal kuliah, tugas dengan `dipublikasikan_pada` dan `deadline` miliknya, ujian relevan.
- **Dosen:** kelas yang diajar (via `kelas_mata_kuliah`), jadwal mengajar, tugas yang dibuat, deadline, ujian, agenda relevan.
- **Admin:** kalender akademik institusi dan jadwal lintas kelas sesuai hak akses.

Gunakan zona waktu aplikasi yang konsisten. Jangan mengarang tanggal, agenda, atau status.

## 7. Dashboard per role
Dashboard harus punya kerangka visual yang konsisten menggunakan satu application shell, tetapi informasi dan tindakan disesuaikan dengan role.

### Mahasiswa
Susunan prioritas konten:
1. Greeting dan identitas mahasiswa (nama, NIM).
2. Informasi tahun akademik dan semester aktif.
3. Ringkasan tugas aktif dan belum dikumpulkan (hanya angka yang didukung data nyata).
4. Deadline terdekat.
5. Ringkasan jadwal ujian (UTS/UAS) mendatang.
6. Kalender akademik berukuran besar di area tengah — Calendar View dan Timeline View tersedia.
7. Panel notifikasi di kanan: notifikasi terbaru, deadline terdekat, ujian mendatang.
8. Jadwal kuliah terdekat.
9. Progres materi pembelajaran.

Jangan memenuhi dashboard dengan terlalu banyak kartu statistik. Tampilkan hanya indikator yang benar-benar membantu keputusan mahasiswa dan didukung data nyata.

### Dosen
Susunan prioritas konten:
- Jadwal mengajar hari ini dan minggu ini.
- Tugas yang dibuat: status pengumpulan, berapa yang sudah masuk dan belum dinilai.
- Jadwal ujian yang dikelola.
- Ringkasan absensi kelas yang diajar.
- Progres mahasiswa secara ringkas.
- Notifikasi akademik.

Jangan menampilkan data kelas lain yang tidak menjadi tanggung jawab dosen.

### Admin
Susunan prioritas konten:
- Total mahasiswa aktif, total dosen, mata kuliah, kelas aktif (hanya angka didukung query nyata).
- Tahun akademik dan semester aktif.
- Kalender akademik institusi.
- Agenda akademik mendatang.
- Aktivitas administrasi terbaru.

Jangan menampilkan angka statistik palsu. Jangan menampilkan data role lain tanpa alasan dan otorisasi.

## 8. Panel notifikasi dashboard
Panel kanan dashboard menampilkan:
- Notifikasi terbaru dari tabel `notifikasi`: judul, kategori, waktu, status baca/belum baca, link ke detail.
- Deadline tugas terdekat.
- Ujian mendatang.
- Agenda penting.

Semua item harus bersumber dari data aktual. Jangan menampilkan notifikasi contoh yang terlihat seperti data produksi.

## 9. Aturan UI/UX
- Ikuti `docs/UI_UX_GUIDELINES.md` dan `docs/DESIGN_SYSTEM.md`.
- **Arah visual: Lavender Floating Dashboard, mengikuti gambar referensi `docs/reference/eduplex-dashboard.png`.** Revisi ini disetujui eksplisit oleh pengguna (21 Nov 2026) dan menggantikan tema "Light Lavender" sebelumnya. Ringkasan:
  - Latar luar lavender jenuh (`#C2A5F9`); bingkai aplikasi off-white (`#F8F8F8`) mengambang, `rounded-frame` (32px), bayangan lembut.
  - Sidebar navy (`#201F31`) mengambang di dalam bingkai; item aktif pil lime (`#DAF17C`) dengan teks gelap (`#1A2200`).
  - Tanpa topbar berlatar; sapaan besar di kiri, pencarian (bila nyata)/notifikasi/avatar di kanan.
  - Card putih tanpa border, radius 20px, bayangan halus; ubin ikon dan pil status pastel; tombol/pil/tab/avatar `rounded-full`.
  - Grafik batang navy dengan satu batang lime dan tooltip gelap; cincin progres; mini kalender dengan hari ini lime.
  - Font Plus Jakarta Sans (tidak berubah).
- **Kemiripan dengan referensi adalah target utama visual.** Tiru warna, bentuk, radius, bayangan, tipografi, spasi, dan pola komponen sedekat mungkin, lalu verifikasi dengan daftar periksa di `DESIGN_SYSTEM.md` §9 (bandingkan pada 1440 px dan 390 px).
- **Tiru gaya, bukan konten.** Jangan menampilkan rating, "Go Premium", "Download mobile app", angka jam, atau nama kursus dari gambar sebagai data. Ganti dengan padanan data nyata sesuai `DESIGN_SYSTEM.md` §7; jika tidak ada data nyata, widget tidak ditampilkan.
- Widget referensi yang membutuhkan query/service baru (mis. Aktivitas Belajar dari `aktivitas_materi` + `pengumpulan_tugas`) hanya dibuat setelah rencana singkat disampaikan dan disetujui. Jangan mengubah skema database demi widget visual.
- Gunakan satu application shell untuk ketiga role. Sidebar kiri, area utama dengan header (tanpa latar), dan panel kanan khusus dashboard.
- Kalender harus tetap menjadi elemen dominan dashboard: card terbesar di area utama. Mini kalender di panel kanan berfungsi sebagai navigator kalender utama, bukan kalender kedua.
- **Interaktif tapi ringan:** CSS dulu (hover, tooltip chart, animasi masuk), JavaScript seperlunya (Alpine.js hanya bila sudah terpasang, atau satu modul vanilla kecil). Dilarang menambah library animasi/grafik/UI tanpa persetujuan. Hormati `prefers-reduced-motion`. Ikuti anggaran performa di `DESIGN_SYSTEM.md` §8 dan laporkan ukuran bundle hasil `npm run build`.
- Hindari gradient berlebihan, glow, glassmorphism/blur, animasi `infinite` dekoratif, dan terlalu banyak kartu statistik.
- Utamakan kontras teks (WCAG AA), keterbacaan, fokus keyboard, label form, dan feedback aksi. Jangan menjadikan lime di atas putih sebagai satu-satunya penanda; sertakan label/tooltip.
- Gunakan spacing, radius, ukuran heading, warna, dan state komponen secara konsisten sesuai token di `DESIGN_SYSTEM.md`.
- Jangan mengubah tema, struktur navigasi, atau pola komponen yang sudah disetujui di luar revisi ini tanpa menjelaskan alasan dan meminta persetujuan.

## 10. Proses implementasi halaman
Untuk setiap halaman:
1. Identifikasi role dan tujuan pengguna.
2. Identifikasi data yang dibutuhkan dan sumber datanya (tabel, model, relasi).
3. Tentukan hierarki konten dan tindakan utama.
4. Reuse komponen yang tersedia.
5. Implementasikan loading, empty, success, error, dan validation states yang relevan.
6. Pastikan responsif dan aksesibel.
7. Periksa otorisasi backend untuk semua aksi.
8. Uji interaksi utama dan laporkan hasilnya.

## 11. Format laporan setelah bekerja
Sampaikan secara ringkas:
- Apa yang diubah.
- File utama yang dibuat/diubah.
- Cara menjalankan atau melihat hasil.
- Pemeriksaan yang dijalankan dan hasilnya.
- Kekurangan atau keputusan yang masih menunggu persetujuan.

Jangan mengklaim sudah menjalankan perintah atau test jika tidak benar-benar dijalankan.
