# UI/UX Guidelines — Student Academic Planner

## 1. Tujuan pengalaman pengguna
Antarmuka harus membuat pengguna cepat memahami:
- Apa yang harus dilakukan.
- Kapan tugas atau agenda berlangsung.
- Apa yang sudah selesai dan belum selesai.
- Ke mana pengguna harus pergi untuk tindakan berikutnya.

Desain bukan sekadar dekorasi. Kejelasan informasi, konsistensi, dan kemudahan penggunaan lebih penting daripada efek visual.

## 2. Arah visual
Tema utama aplikasi adalah **Lavender Floating Dashboard**, mengikuti gambar referensi Eduplex (`docs/reference/eduplex-dashboard.png`, disetujui pengguna 21 Nov 2026): modern, bersih, ramah, dan berorientasi produktivitas. Nilai warna, radius, dan bayangan final ada di `docs/DESIGN_SYSTEM.md`.

- **Latar halaman:** lavender jenuh (`#C2A5F9`) sebagai bingkai identitas aplikasi.
- **Bingkai aplikasi:** off-white (`#F8F8F8`) dengan sudut sangat bulat (32px) dan bayangan lembut, mengambang di atas lavender dengan jarak ke tepi layar.
- **Sidebar:** navy gelap (`#201F31`) yang juga mengambang di dalam bingkai (sudut 24px), bukan menempel ke tepi layar.
- **Aksen:** lime (`#DAF17C`) untuk item menu aktif (pil penuh), CTA utama, hari ini di kalender, dan tombol "+" bulat. Teks di atas lime selalu gelap.
- **Card:** putih, **tanpa border**, radius ±20px, bayangan sangat halus.
- **Ubin ikon dan pil status:** pastel (peach, lime muda, lavender, sand) agar daftar terasa hangat dan mudah dipindai.
- **Bentuk kontrol:** tombol, pil, tab, dropdown, dan avatar berbentuk `rounded-full`.
- **Grafik:** batang navy ber-sudut bulat dengan satu batang lime sebagai sorotan dan tooltip gelap.
- **Font:** Plus Jakarta Sans (sudah dikonfigurasi via Bunny Fonts di `vite.config.js`); hierarki lewat ukuran dan weight, bukan lewat banyak font.
- **Ikon:** satu keluarga ikon outline tipis yang sudah ada di proyek.
- **Bayangan:** halus dan berlapis dua; tanpa glow.

**Tiru gaya, bukan konten.** Rating bintang, "Go Premium", "Download mobile app", dan angka jam dari gambar referensi tidak boleh muncul sebagai data palsu; ganti dengan padanan data nyata (pemetaan di `DESIGN_SYSTEM.md` §7).

Jangan menetapkan nilai hex final secara terpisah di setiap halaman; semuanya terpusat di `resources/css/app.css`.

## 3. Kerangka aplikasi (Application Shell)
Gunakan **satu shell aplikasi** untuk ketiga role (mahasiswa, dosen, admin). Perbedaan role hanya pada isi menu dan data yang ditampilkan.

### Desktop
- Bingkai off-white mengambang di atas latar lavender, dengan jarak ke tepi layar dan bayangan lembut.
- Di dalam bingkai: sidebar navy mengambang (232px) di kiri, area utama di tengah, panel kanan (300px) khusus dashboard.
- **Tidak ada topbar berlatar.** Baris header berada di dalam area utama: sapaan atau judul halaman di kiri; pencarian (bila nyata), lonceng notifikasi dengan badge, dan avatar di kanan.
- Konten utama tidak boleh tertutup sidebar atau header.

### Sidebar
- Logo dan nama aplikasi di bagian atas.
- Kelompok menu berdasarkan role; ikon outline tipis dari satu library.
- Item aktif: pil lime penuh dengan teks gelap. Item tidak aktif: teks abu-terang pada navy. Hover: navy sedikit lebih terang.
- Badge hitung notifikasi berwarna peach pada item Notifikasi, hanya bila angka > 0 dari data nyata.
- Bagian bawah: kartu lime berisi pintasan nyata sesuai role (bila tidak ada pintasan nyata, kartu dihilangkan). Profil dan logout tetap mudah dijangkau.

### Header halaman
- Dashboard: sapaan besar ("Halo, {nama} 👋") dengan sub-baris NIM/tahun akademik/semester.
- Halaman dalam: judul halaman + breadcrumb ringkas di atasnya bila membantu orientasi.
- Menu avatar (dropdown): profil, logout.

### Mobile
- Bingkai penuh layar tanpa margin luar dan tanpa sudut bulat; sidebar menjadi drawer (buka/tutup via hamburger).
- Header ringkas; konten satu kolom.
- Panel kanan berpindah ke aliran konten utama (setelah konten prioritas).
- Kalender memakai tampilan agenda/list; tabel lebar memakai scroll horizontal atau diubah menjadi card/list.

## 4. Navigasi
- Menu disesuaikan dengan role.
- Menu aktif harus terlihat jelas (lime pada sidebar navy).
- Label menu menggunakan Bahasa Indonesia yang konsisten.
- Hindari menu terlalu banyak dalam satu kelompok; kelompokkan secara logis.
- Jangan menampilkan link yang tidak memiliki halaman/aksi nyata.
- Berikan judul halaman dan breadcrumb bila membantu orientasi.

## 5. Dashboard
Kalender akademik adalah fokus utama dashboard. Pada desktop, kalender harus mendapat area visual yang dominan dan mudah ditemukan tanpa menggulir terlalu jauh.

**Susunan visual (mengikuti referensi, kalender tetap dominan):** header sapaan → 3 kartu ringkas → kalender akademik sebagai card terbesar → baris dua card (Aktivitas Belajar | Jadwal Hari Ini) → Mata Kuliah Diikuti. Panel kanan: kartu fitur gelap → mini kalender (navigator) → Tugas & Deadline → Notifikasi terbaru. Detail ada di `DESIGN_SYSTEM.md` §7.

### Dashboard Mahasiswa — urutan prioritas konten
1. Greeting dan identitas (nama mahasiswa, NIM, tahun akademik, semester aktif).
2. Ringkasan cepat: tugas aktif, belum dikumpulkan, deadline terdekat, ujian mendatang — hanya angka yang didukung data nyata.
3. Kalender akademik berukuran besar (Calendar View dan Timeline View) dengan panel notifikasi di kanan: notifikasi terbaru, deadline terdekat, ujian mendatang, agenda penting, dan jadwal kuliah terdekat.
4. Ringkasan belajar: Target Belajar dan Progres Materi Otomatis dengan angka yang bersumber dari query yang sama seperti halaman detail.

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
- Validasi ditampilkan dekat field terkait dengan `text-danger`, `role="alert"`, dan `aria-live="assertive"`; ringkasan error memakai `border-danger/30 bg-danger/10`.
- Field dengan error menambahkan `aria-invalid="true"` dan `aria-describedby` yang mengarah ke pesan terkait.
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
- Card ber-border tebal, topbar berlatar, atau sidebar yang menempel ke tepi layar (menyimpang dari referensi).
- Radius campur-aduk di luar skala token (frame 32 / panel 24 / card 20 / tile 14 / control 12 / full).
- Menyalin konten palsu dari gambar referensi (rating, premium, download app, angka jam).
- Library grafik/animasi berat, animasi `infinite` dekoratif, blur, atau glow.
- Penyorotan yang hanya bergantung pada warna lime di atas putih (kontras rendah); selalu tambah tooltip/label.

## 11. Interaksi dan performa
Antarmuka harus terasa hidup tetapi tetap ringan di perangkat biasa.
- Hover/fokus card dan baris: angkat 2px + bayangan sedikit lebih tegas (150–250 ms).
- Grafik batang dan cincin progres beranimasi sekali saat muat; tooltip chart muncul saat hover **dan** fokus keyboard.
- Mini kalender mengendalikan kalender utama; tab, dropdown, drawer, dan modal bekerja tanpa reload penuh bila memungkinkan, dengan fallback server-side.
- Hormati `prefers-reduced-motion`.
- Tidak menambah library animasi/grafik tanpa persetujuan; CSS dulu, JavaScript seperlunya, server-render dulu.
- Aturan lengkap, anggaran performa, dan daftar periksa kemiripan: `DESIGN_SYSTEM.md` §8 dan §9.
