# UI/UX Guidelines — Student Academic Planner

## 1. Tujuan pengalaman pengguna
Antarmuka harus membuat pengguna cepat memahami:
- Apa yang harus dilakukan.
- Kapan tugas atau agenda berlangsung.
- Apa yang sudah selesai dan belum selesai.
- Ke mana pengguna harus pergi untuk tindakan berikutnya.

Desain bukan sekadar dekorasi. Kejelasan informasi, konsistensi, dan kemudahan penggunaan lebih penting daripada efek visual.

## 2. Arah visual
- Gaya: modern, minimal, profesional, berorientasi produktivitas.
- Tema utama: dark mode.
- Warna dasar: charcoal/near-black, bukan hitam pekat di semua permukaan.
- Aksen utama: indigo/ungu.
- Warna status: gunakan warna semantik secara konsisten (merah untuk terlambat/urgent, amber untuk perhatian, hijau untuk selesai/sukses, biru untuk informasi).
- Font: Plus Jakarta Sans, jika tersedia di proyek.
- Ikon: satu keluarga ikon konsisten yang sudah ada di proyek.
- Sudut komponen: rounded sedang; jangan setiap elemen memakai radius berbeda.
- Bayangan: halus dan seperlunya; hindari glow yang berlebihan.

Jangan menetapkan nilai hex final secara terpisah di setiap halaman. Nilai warna final didefinisikan terpusat di `DESIGN_SYSTEM.md` atau konfigurasi theme proyek.

## 3. Kerangka aplikasi
Desktop:
- Sidebar kiri untuk navigasi utama.
- Topbar untuk judul halaman/konteks, notifikasi, dan profil.
- Area konten dengan lebar yang nyaman dan spacing konsisten.
- Konten utama tidak boleh tertutup sidebar atau topbar.

Mobile:
- Sidebar berubah menjadi drawer/menu yang mudah dibuka.
- Topbar tetap ringkas.
- Tabel lebar dapat memakai horizontal scroll atau layout alternatif yang lebih tepat.
- Kalender menggunakan tampilan agenda/list jika grid bulanan tidak lagi terbaca.

## 4. Navigasi
- Menu disesuaikan dengan role.
- Menu aktif harus terlihat jelas.
- Label menu menggunakan Bahasa Indonesia yang konsisten.
- Hindari menu terlalu banyak dalam satu kelompok; kelompokkan secara logis.
- Jangan menampilkan link yang tidak memiliki halaman/aksi nyata.
- Berikan judul halaman dan breadcrumb bila membantu orientasi.

## 5. Dashboard
Kalender akademik adalah fokus utama dashboard. Pada desktop, kalender harus mendapat area visual yang dominan dan mudah ditemukan tanpa menggulir terlalu jauh.

Urutan prioritas yang disarankan:
1. Header halaman dan konteks semester.
2. Kalender akademik.
3. Deadline/agenda terdekat.
4. Ringkasan yang relevan dengan role.
5. Notifikasi atau aktivitas terbaru.

Urutan ini dapat disesuaikan berdasarkan kebutuhan role, tetapi kalender tetap menjadi komponen inti.

Jangan memenuhi dashboard dengan terlalu banyak kartu statistik. Tampilkan angka yang membantu keputusan dan memiliki sumber data nyata.

## 6. Kalender akademik
Kontrol minimum:
- Bulan/minggu aktif.
- Navigasi sebelumnya dan berikutnya.
- Tombol "Hari ini".
- Filter jenis agenda.
- Legenda warna.
- Detail agenda melalui modal, drawer, atau halaman detail.

Perlakuan jenis agenda:
- Tugas: tanggal dibuat dan deadline dibedakan. Tampilkan judul, mata kuliah/kelas, deadline, dan status pengumpulan jika relevan.
- Ujian: tanggal, jam, mata kuliah, lokasi/metode bila tersedia.
- Kuliah: hari, jam, mata kuliah, kelas, ruang/link bila tersedia.
- Agenda akademik: rentang mulai-selesai.
- Pengumuman: tanggal publikasi dan ringkasan.

Jangan hanya mengandalkan warna untuk membedakan agenda. Sertakan label, ikon yang konsisten, atau teks status agar tetap jelas bagi pengguna dengan gangguan persepsi warna.

Pada tampilan bulanan, jangan menjejalkan terlalu banyak teks ke dalam satu sel. Jika agenda banyak, tampilkan ringkasan jumlah item dan sediakan detail saat dipilih. Pada mobile, pertimbangkan agenda list berdasarkan tanggal.

## 7. Komponen dan state
Komponen berulang harus reusable:
- Button (primary, secondary, danger, ghost).
- Input, select, textarea, date/time picker.
- Card dan stat card.
- Table dan pagination.
- Badge/status.
- Modal/drawer.
- Dropdown.
- Tabs.
- Alert/toast.
- Empty state.
- Skeleton/loading state.
- Calendar event dan calendar toolbar.

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
- Kontras teks harus memadai.
- Gunakan semantic HTML.
- Semua kontrol dapat diakses dengan keyboard sejauh memungkinkan.
- Fokus keyboard harus terlihat.
- Tombol memiliki label yang jelas.
- Jangan menggunakan ukuran teks terlalu kecil untuk informasi penting.
- Hindari layout yang hanya bekerja pada satu ukuran layar.
- Uji setidaknya desktop dan ukuran mobile yang umum.

## 10. Hal yang harus dihindari
- Dashboard penuh kartu tanpa prioritas.
- Warna aksen berbeda di setiap halaman.
- Ikon dari banyak gaya yang tidak seragam.
- Animasi dan gradient yang tidak menambah pemahaman.
- Teks abu-abu terlalu redup di atas background gelap.
- Kalender hanya sebagai gambar statis.
- Tombol dekoratif yang tidak berfungsi.
- Data dummy yang terlihat seperti data produksi.
- Mengubah desain yang sudah disepakati tanpa alasan.
