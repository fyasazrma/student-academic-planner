# UI/UX Guidelines — Student Academic Planner

## 1. Tujuan pengalaman pengguna
Antarmuka harus membuat pengguna cepat memahami:
- Apa yang harus dilakukan.
- Kapan tugas atau agenda berlangsung.
- Apa yang sudah selesai dan belum selesai.
- Ke mana pengguna harus pergi untuk tindakan berikutnya.

Desain bukan sekadar dekorasi. Kejelasan informasi, konsistensi, dan kemudahan penggunaan lebih penting daripada efek visual.

## 2. Arah visual
Tema utama aplikasi adalah **Light Lavender Academic Dashboard**: modern, bersih, profesional, dan berorientasi produktivitas dengan nuansa lavender yang hangat namun tidak berat.

- Latar halaman (page background): lavender terang — memberi identitas aplikasi dan memisahkan browser chrome dari konten.
- Permukaan aplikasi (application surface): putih/off-white — area konten utama yang bersih dan mudah dibaca.
- Sidebar: navy gelap — memberikan kontras tegas dan struktur navigasi yang jelas.
- Aksen utama: lime — digunakan untuk menu aktif pada sidebar; memberikan identitas visual yang kuat tanpa berlebihan.
- Card: putih dengan border halus dan rounded corners.
- Warna status: gunakan warna semantik secara konsisten (merah/rose untuk terlambat/urgent, amber untuk perhatian, hijau untuk selesai/sukses, biru untuk informasi).
- Font: Plus Jakarta Sans (sudah dikonfigurasi via Bunny Fonts di vite.config.js).
- Ikon: satu keluarga ikon konsisten yang sudah ada di proyek.
- Sudut komponen: rounded sedang; jangan setiap elemen memakai radius berbeda.
- Bayangan: halus dan seperlunya; hindari glow yang berlebihan.

Jangan menetapkan nilai hex final secara terpisah di setiap halaman. Nilai warna final didefinisikan terpusat di `DESIGN_SYSTEM.md` atau konfigurasi theme proyek (`resources/css/app.css`).

## 3. Kerangka aplikasi (Application Shell)
Gunakan **satu shell aplikasi** untuk ketiga role (mahasiswa, dosen, admin). Perbedaan role hanya pada isi menu dan data yang ditampilkan.

### Desktop
- Sidebar kiri berisi navigasi utama (navy gelap, item aktif lime).
- Topbar di bagian atas berisi judul halaman, breadcrumb jika diperlukan, notifikasi, dan profil pengguna.
- Area konten utama (putih/off-white) di tengah dengan lebar yang nyaman dan spacing konsisten.
- Panel kanan (opsional, untuk dashboard) menampilkan notifikasi dan informasi penting.
- Background luar (lavender) terlihat sebagai bingkai visual keseluruhan.
- Konten utama tidak boleh tertutup sidebar atau topbar.

### Sidebar
- Logo dan nama aplikasi di bagian atas.
- Kelompok menu berdasarkan role.
- Item menu aktif menggunakan aksen lime (teks gelap pada background lime).
- Item menu tidak aktif menggunakan teks putih/off-white pada background navy.
- Hover state: warna latar sidebar hover (navy sedikit lebih terang).
- Ikon konsisten dari satu library.
- Area profil dan logout di bagian bawah sidebar.

### Topbar
- Judul halaman aktif.
- Breadcrumb jika membantu orientasi (halaman dalam, bukan dashboard).
- Ikon notifikasi dengan badge jumlah notifikasi belum dibaca.
- Avatar/nama pengguna dan menu dropdown (profil, logout).

### Mobile
- Sidebar berubah menjadi drawer yang bisa dibuka dan ditutup.
- Topbar tetap ringkas dengan hamburger menu.
- Main content menjadi satu kolom penuh.
- Panel kanan berpindah ke dalam aliran konten utama (di bawah konten prioritas).
- Kalender menggunakan tampilan agenda/list yang mudah dibaca.
- Tabel lebar menggunakan horizontal scroll atau diubah menjadi card/list.

## 4. Navigasi
- Menu disesuaikan dengan role.
- Menu aktif harus terlihat jelas (lime pada sidebar navy).
- Label menu menggunakan Bahasa Indonesia yang konsisten.
- Hindari menu terlalu banyak dalam satu kelompok; kelompokkan secara logis.
- Jangan menampilkan link yang tidak memiliki halaman/aksi nyata.
- Berikan judul halaman dan breadcrumb bila membantu orientasi.

## 5. Dashboard
Kalender akademik adalah fokus utama dashboard. Pada desktop, kalender harus mendapat area visual yang dominan dan mudah ditemukan tanpa menggulir terlalu jauh.

### Dashboard Mahasiswa — urutan prioritas konten
1. Greeting dan identitas (nama mahasiswa, NIM, tahun akademik, semester aktif).
2. Ringkasan cepat: tugas aktif, belum dikumpulkan, deadline terdekat, ujian mendatang — hanya angka yang didukung data nyata.
3. Kalender akademik berukuran besar (Calendar View dan Timeline View).
4. Jadwal kuliah terdekat.
5. Progres materi pembelajaran.
6. Panel notifikasi (notifikasi terbaru, deadline, ujian mendatang).

### Dashboard Dosen — urutan prioritas konten
1. Jadwal mengajar hari ini dan minggu ini.
2. Ringkasan pengumpulan tugas (berapa masuk, berapa belum dinilai).
3. Jadwal ujian yang dikelola.
4. Ringkasan absensi kelas yang diajar.
5. Progres mahasiswa secara ringkas.
6. Notifikasi akademik.

### Dashboard Admin — urutan prioritas konten
1. Angka statistik sistem (total mahasiswa, dosen, mata kuliah, kelas aktif) — hanya dari query nyata.
2. Tahun akademik dan semester aktif.
3. Kalender akademik institusi.
4. Agenda akademik mendatang.
5. Aktivitas administrasi terbaru.

Jangan memenuhi dashboard dengan terlalu banyak kartu statistik. Tampilkan angka yang membantu keputusan dan memiliki sumber data nyata. Jangan menampilkan angka palsu.

## 6. Kalender akademik

### 6a. Calendar View
Kontrol minimum:
- Tampilan bulanan dan mingguan. Tampilan harian opsional.
- Navigasi periode sebelumnya dan berikutnya.
- Tombol "Hari ini".
- Filter jenis agenda (Tugas, UTS, UAS, Jadwal Kuliah, Agenda Akademik, Pengumuman).
- Legenda warna.
- Detail agenda melalui modal, drawer, atau halaman detail.
- Empty state saat tidak ada agenda.

### 6b. Timeline View
Kontrol minimum:
- Bar horizontal mewakili rentang tugas (dari `dipublikasikan_pada` ke `deadline`).
- Sumbu waktu horizontal dengan label tanggal.
- Setiap bar: nama tugas, mata kuliah, penanda titik publikasi, penanda titik deadline, status pengumpulan.
- Ujian (UTS/UAS) tampil sebagai penanda titik (bukan bar), karena tidak memiliki rentang.
- Status warna bar mencerminkan kondisi: belum dikerjakan, sedang dikerjakan, sudah dikumpulkan, terlambat.
- Mode mobile: ringkasan list/agenda menggantikan timeline horizontal.

### 6c. Perlakuan jenis agenda

**TUGAS:**
- `dipublikasikan_pada` dan `deadline` **selalu dibedakan** — jangan tampilkan hanya salah satunya.
- Tampilkan: judul, mata kuliah/kelas, tanggal publikasi, deadline, status pengumpulan jika relevan.
- Rentang antara publikasi dan deadline divisualisasikan sebagai bar di Timeline View.

**UTS (Ujian Tengah Semester):**
- Tanggal, jam mulai, jam selesai, mata kuliah, lokasi/metode jika tersedia.
- Diberi label "UTS" secara eksplisit; jangan hanya ditulis "Ujian".
- Tampil sebagai penanda titik pada Timeline View.

**UAS (Ujian Akhir Semester):**
- Tanggal, jam mulai, jam selesai, mata kuliah, lokasi/metode jika tersedia.
- Diberi label "UAS" secara eksplisit; jangan hanya ditulis "Ujian".
- Tampil sebagai penanda titik pada Timeline View.

**JADWAL KULIAH:**
- Hari, jam, mata kuliah, kelas, ruangan atau link.

**AGENDA AKADEMIK:**
- Judul, keterangan, tanggal mulai, tanggal selesai.

**PENGUMUMAN:**
- Tanggal publikasi — bukan deadline. Label harus jelas bahwa ini pengumuman, bukan tugas.

Jangan hanya mengandalkan warna untuk membedakan agenda. Sertakan label, ikon yang konsisten, atau teks status agar tetap jelas bagi pengguna dengan gangguan persepsi warna.

Pada tampilan bulanan, jangan menjejalkan terlalu banyak teks ke dalam satu sel. Jika agenda banyak, tampilkan ringkasan jumlah item dan sediakan detail saat dipilih.

## 7. Komponen dan state
Komponen berulang harus reusable:
- Button (primary/lime, secondary, danger, ghost).
- Input, select, textarea, date/time picker.
- Card dan stat card.
- Table dan pagination.
- Badge/status (gunakan label teks eksplisit: "Belum dikumpulkan", "Terkumpul", "Terlambat", "Selesai").
- Modal/drawer.
- Dropdown.
- Tabs (Calendar View / Timeline View).
- Alert/toast.
- Empty state.
- Skeleton/loading state.
- Calendar event dan calendar toolbar.
- Timeline bar dan timeline marker.

Setiap komponen interaktif harus memiliki state yang jelas:
- Default.
- Hover.
- Focus-visible.
- Active/pressed.
- Disabled.
- Loading.
- Error/validation jika relevan.

## 8. Form dan feedback
- Label harus terlihat; jangan hanya mengandalkan placeholder.
- Validasi ditampilkan dekat field terkait.
- Pertahankan input pengguna ketika validasi gagal.
- Berikan konfirmasi untuk aksi destruktif.
- Setelah aksi berhasil, tampilkan feedback yang jelas.
- Jangan membuat tombol tanpa aksi atau aksi yang tidak memberi feedback.

## 9. Aksesibilitas dan responsivitas
- Kontras teks harus memadai (WCAG AA minimal). Periksa kontras teks di atas background lavender dan surface putih.
- Gunakan semantic HTML.
- Semua kontrol dapat diakses dengan keyboard sejauh memungkinkan.
- Fokus keyboard harus terlihat.
- Tombol memiliki label yang jelas (aria-label jika perlu).
- Jangan menggunakan ukuran teks terlalu kecil untuk informasi penting.
- Hindari layout yang hanya bekerja pada satu ukuran layar.
- Uji setidaknya desktop dan ukuran mobile yang umum.

## 10. Hal yang harus dihindari
- Dashboard penuh kartu tanpa prioritas.
- Warna aksen berbeda di setiap halaman.
- Ikon dari banyak gaya yang tidak seragam.
- Animasi dan gradient yang tidak menambah pemahaman.
- Teks abu-abu terlalu redup di atas background terang (kontras kurang).
- Kalender hanya sebagai gambar statis.
- Tombol dekoratif yang tidak berfungsi.
- Data dummy yang terlihat seperti data produksi.
- Mengubah desain yang sudah disepakati tanpa alasan.
- Menampilkan angka statistik tanpa sumber data nyata.
- Menyamakan tanggal publikasi tugas dengan deadline.
- Menyebut UTS/UAS hanya sebagai "Ujian" tanpa label yang jelas.
