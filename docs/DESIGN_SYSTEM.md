# Design System — Student Academic Planner

Dokumen ini menetapkan token dan pola komponen awal. Sesuaikan implementasinya dengan versi Tailwind dan struktur proyek yang benar-benar tersedia.

## 1. Design tokens

Gunakan token terpusat melalui CSS variables atau konfigurasi theme yang sesuai dengan versi Tailwind proyek. Jangan menyebarkan nilai warna acak di banyak file.

| Token | Arah nilai |
|---|---|
| `--color-bg` | Charcoal sangat gelap |
| `--color-surface` | Abu-abu gelap untuk panel |
| `--color-surface-elevated` | Panel sedikit lebih terang |
| `--color-border` | Border gelap dengan kontras cukup |
| `--color-text` | Putih lembut |
| `--color-text-muted` | Abu-abu terang yang tetap terbaca |
| `--color-primary` | Indigo/ungu |
| `--color-success` | Hijau |
| `--color-warning` | Amber |
| `--color-danger` | Merah |
| `--color-info` | Biru |

Palet awal yang boleh dijadikan titik awal, bukan nilai yang harus dipaksakan:
- Background: `#111218`
- Surface: `#191B24`
- Elevated surface: `#222532`
- Border: `#303342`
- Primary: `#8875E8`
- Primary hover: `#7562D5`
- Text: `#F4F4F8`
- Muted text: `#A6A8B8`
- Success: `#42B883`
- Warning: `#E9AD55`
- Danger: `#E36D78`
- Info: `#5D9CEC`

Pastikan kontras diuji. Jangan memakai warna muted untuk teks utama atau informasi penting.

## 2. Tipografi
- Font utama: Plus Jakarta Sans.
- Page title: jelas dan paling menonjol di area konten.
- Section title: lebih kecil dari page title.
- Body: nyaman dibaca.
- Metadata: boleh lebih kecil, tetapi tetap terbaca.
- Gunakan bobot font secara konsisten; jangan semua teks dibuat bold.

## 3. Spacing, radius, dan ukuran
- Gunakan skala spacing Tailwind secara konsisten.
- Gunakan satu radius utama untuk card dan satu radius lebih kecil untuk kontrol.
- Tinggi tombol dan input harus konsisten dalam kelompok yang sama.
- Sidebar memiliki lebar stabil pada desktop dan menjadi drawer pada mobile.
- Batasi lebar konten form panjang agar tidak melebar berlebihan.

## 4. Pola layout
- Sidebar: navigasi role, identitas aplikasi, item aktif, dan area profil bila diperlukan.
- Topbar: judul/konteks, notifikasi, dan menu pengguna.
- Main content: page header, konten prioritas, lalu konten pendukung.
- Gunakan komponen layout yang sama pada semua role.

## 5. Pola komponen
### Button
- Primary: aksi utama satu per area.
- Secondary: aksi pendukung.
- Danger: aksi destruktif.
- Ghost: aksi ringan seperti navigasi atau kontrol.
- Semua tombol memiliki state hover, focus, disabled, dan loading bila relevan.

### Card
- Surface konsisten, border halus, padding seragam.
- Jangan menaruh setiap baris informasi dalam card terpisah tanpa alasan.

### Badge
- Gunakan untuk status, bukan dekorasi.
- Label status harus eksplisit: "Belum dikumpulkan", "Terkumpul", "Terlambat", "Selesai".

### Table
- Header mudah dibedakan.
- Baris memiliki spacing yang cukup.
- Sediakan empty state dan pagination bila diperlukan.
- Pada mobile, gunakan overflow yang disengaja atau ubah menjadi list/card.

### Calendar
- Toolbar konsisten: periode, navigasi, mode bulan/minggu, filter.
- Event punya warna dan label berdasarkan jenis.
- Tugas menampilkan tanggal mulai/dibuat serta deadline secara berbeda.
- Klik event membuka detail yang relevan.
- Sediakan versi agenda/list untuk mobile.

### Modal dan drawer
- Judul dan tujuan jelas.
- Aksi utama dan batal mudah dibedakan.
- Modal tidak terlalu besar untuk tugas sederhana.
- Drawer cocok untuk detail agenda atau navigasi mobile.

## 6. Konsistensi lintas role
Gunakan satu shell aplikasi dan komponen bersama. Perbedaan role sebaiknya berupa:
- Menu navigasi.
- Data yang ditampilkan.
- Aksi yang tersedia.
- Ringkasan dashboard.

Jangan membuat tiga sistem desain yang terpisah untuk mahasiswa, dosen, dan admin.
