# Design System — Student Academic Planner

Dokumen ini menetapkan token dan pola komponen yang digunakan di seluruh aplikasi. Sesuaikan implementasinya dengan versi Tailwind dan struktur proyek yang benar-benar tersedia.

> **Tailwind versi aktual:** Tailwind CSS v4 (`@tailwindcss/vite`). Token warna didefinisikan via `@theme {}` di `resources/css/app.css`, bukan di `tailwind.config.js`.

## 1. Design tokens

Gunakan token terpusat melalui CSS custom properties (`@theme {}` di `resources/css/app.css`). Jangan menyebarkan nilai warna acak di banyak file.

### Warna utama

| Token CSS | Nilai | Keterangan |
|---|---|---|
| `--color-page-bg` | `#F0E9FF` | Latar halaman luar (lavender) |
| `--color-surface` | `#FBFBFA` | Permukaan aplikasi utama (off-white) |
| `--color-card` | `#FFFFFF` | Card dan panel putih |
| `--color-soft` | `#F5F6FA` | Permukaan lunak untuk latar alternatif |
| `--color-border` | `#E8E8EF` | Border komponen |
| `--color-sidebar` | `#242238` | Sidebar navy gelap |
| `--color-sidebar-hover` | `#34314B` | Hover item sidebar |
| `--color-primary` | `#D8F36A` | Aksen lime — menu aktif, primary action |
| `--color-primary-hover` | `#B9D94A` | Hover aksen lime |
| `--color-primary-text` | `#1A2200` | Teks di atas background primary (lime) untuk kontras |
| `--color-text` | `#242436` | Teks utama (charcoal gelap) |
| `--color-muted` | `#77788A` | Teks sekunder/muted |
| `--color-success` | `#42B883` | Sukses / selesai |
| `--color-warning` | `#E9B94F` | Peringatan / perhatian |
| `--color-danger` | `#D96C75` | Bahaya / terlambat / urgent |
| `--color-info` | `#5D9CEC` | Informasi |

### Warna agenda kalender

| Token CSS | Nilai | Jenis agenda |
|---|---|---|
| `--color-agenda-tugas` | `#9A78E8` | Tugas (ungu medium) |
| `--color-agenda-uts` | `#76A9E8` | UTS — Ujian Tengah Semester (biru) |
| `--color-agenda-uas` | `#5B8FD0` | UAS — Ujian Akhir Semester (biru lebih gelap) |
| `--color-agenda-akademik` | `#E78DB7` | Agenda akademik institusi (pink) |
| `--color-agenda-kuliah` | `#8DBE83` | Jadwal kuliah (hijau) |
| `--color-agenda-pengumuman` | `#E9B94F` | Pengumuman (amber) |
| `--color-agenda-terlambat` | `#D96C75` | Status terlambat (merah rose) |

Warna UTS dan UAS sengaja dibedakan agar mudah dibedakan secara visual di kalender.

### Contoh deklarasi di `resources/css/app.css`

```css
@import 'tailwindcss';

@theme {
    --font-sans: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;

    /* Application surface */
    --color-page-bg:        #F0E9FF;
    --color-surface:        #FBFBFA;
    --color-card:           #FFFFFF;
    --color-soft:           #F5F6FA;
    --color-border:         #E8E8EF;

    /* Sidebar */
    --color-sidebar:        #242238;
    --color-sidebar-hover:  #34314B;

    /* Primary (lime) */
    --color-primary:        #D8F36A;
    --color-primary-hover:  #B9D94A;
    --color-primary-text:   #1A2200;

    /* Text */
    --color-text:           #242436;
    --color-muted:          #77788A;

    /* Semantic */
    --color-success:        #42B883;
    --color-warning:        #E9B94F;
    --color-danger:         #D96C75;
    --color-info:           #5D9CEC;

    /* Agenda */
    --color-agenda-tugas:        #9A78E8;
    --color-agenda-uts:          #76A9E8;
    --color-agenda-uas:          #5B8FD0;
    --color-agenda-akademik:     #E78DB7;
    --color-agenda-kuliah:       #8DBE83;
    --color-agenda-pengumuman:   #E9B94F;
    --color-agenda-terlambat:    #D96C75;
}
```

Pastikan kontras diuji. Jangan memakai warna muted untuk teks utama atau informasi penting. Semua teks atas background berwarna harus memenuhi rasio kontras WCAG AA minimal (4.5:1 untuk teks normal).

**Catatan khusus warna primer lime (`#D8F36A`):** Warna ini digunakan sebagai background menu aktif pada sidebar navy. Teks di atasnya harus menggunakan `--color-primary-text` (`#1A2200`) bukan putih, karena lime adalah warna terang.

## 2. Tipografi
- Font utama: Plus Jakarta Sans (dikonfigurasi via `bunny()` di `vite.config.js`, weight 400/500/600/700).
- Page title: paling menonjol di area konten, font bold/semibold, ukuran besar.
- Section title: lebih kecil dari page title, semibold.
- Body: ukuran dasar, weight 400 atau 500, nyaman dibaca.
- Metadata: boleh lebih kecil, tetapi tetap terbaca (hindari di bawah 12px untuk informasi penting).
- Gunakan bobot font secara konsisten; jangan semua teks dibuat bold.

## 3. Spacing, radius, dan ukuran
- Gunakan skala spacing Tailwind secara konsisten (4, 6, 8, 12, 16, 24 px dst.).
- Radius utama untuk card dan panel: `rounded-2xl` (16px).
- Radius komponen kontrol (input, button, badge): `rounded-xl` (12px) atau `rounded-lg` (8px).
- Jangan memakai radius yang berbeda-beda tanpa alasan konsistensi.
- Tinggi tombol dan input harus konsisten dalam kelompok yang sama.
- Sidebar memiliki lebar stabil pada desktop dan menjadi drawer pada mobile.
- Batasi lebar konten form panjang agar tidak melebar berlebihan.

## 4. Pola layout

### Application Shell
Semua role menggunakan satu shell yang sama:
```
[Sidebar navy] | [Area konten utama: topbar + konten] | [Panel kanan (dashboard only)]
                  (background: surface off-white)
Semua dibungkus background lavender (page-bg)
```

- **Sidebar:** lebar tetap pada desktop (mis. 240–260px), drawer pada mobile.
- **Topbar:** tinggi tetap, latar putih/off-white, shadow tipis.
- **Main content:** padding konsisten, lebar konten yang nyaman.
- **Panel kanan:** lebar tetap pada desktop (mis. 280–320px), terlipat ke konten utama pada mobile.

### Komponen layout Blade yang wajib reusable
- `<x-sidebar>` — navigasi sidebar navy.
- `<x-topbar>` — topbar dengan judul, breadcrumb, notifikasi, profil.
- `<x-app-layout>` — shell utama yang menggabungkan sidebar + topbar + slot konten.
- `<x-page-header>` — judul halaman, breadcrumb, aksi utama halaman.
- `<x-notification-panel>` — panel kanan notifikasi dan agenda.

## 5. Pola komponen

### Button
- **Primary:** background lime (`--color-primary`), teks `--color-primary-text` (gelap). Aksi utama satu per area.
- **Secondary:** background card putih, border border, teks teks utama. Aksi pendukung.
- **Danger:** background merah/rose. Aksi destruktif.
- **Ghost:** tanpa background/border, untuk navigasi atau kontrol ringan.
- Semua tombol memiliki state hover, focus-visible, disabled, dan loading bila relevan.

### Card
- Background `--color-card` (putih), border `--color-border`, radius `rounded-2xl`, shadow halus.
- Padding konsisten (mis. `p-5` atau `p-6`).
- Jangan menaruh setiap baris informasi dalam card terpisah tanpa alasan.

### Badge / Status
- Gunakan untuk status, bukan dekorasi.
- Label status harus eksplisit: "Belum dikumpulkan", "Terkumpul", "Terlambat", "Selesai", "Menunggu".
- Gunakan warna semantik sesuai kondisi: danger untuk terlambat, success untuk selesai, warning untuk mendekati deadline.

### Table
- Header mudah dibedakan (background soft, teks muted, uppercase tracking).
- Baris memiliki spacing yang cukup.
- Sediakan empty state dan pagination bila diperlukan.
- Pada mobile, gunakan overflow scroll atau ubah menjadi list/card.

### Kalender — Calendar View
- Toolbar konsisten: periode aktif, navigasi kiri/kanan, tombol "Hari ini", selector mode (Bulan/Minggu/Agenda), filter jenis agenda.
- Event punya warna dot/badge berdasarkan jenis agenda (lihat tabel warna agenda di atas).
- Tugas menampilkan tanggal dipublikasikan dan deadline secara berbeda — jangan gabungkan.
- UTS dan UAS ditampilkan dengan label eksplisit dan warna berbeda.
- Klik event membuka detail yang relevan via modal atau drawer.
- Sediakan versi agenda/list untuk mobile.
- Empty state saat tidak ada agenda di periode aktif.

### Kalender — Timeline View
- Bar horizontal per tugas, panjang bar = rentang dipublikasikan → deadline.
- Sumbu horizontal = skala tanggal (hari atau minggu).
- Setiap bar: label tugas, mata kuliah, indikator titik awal (publikasi) dan titik akhir (deadline).
- Warna bar mencerminkan status: default (ungu), terlambat (rose/merah), selesai (hijau).
- Ujian (UTS/UAS) tampil sebagai marker titik/diamond di garis waktu, bukan bar.
- Tooltip/klik bar membuka detail tugas.

### Modal dan Drawer
- Judul dan tujuan jelas.
- Aksi utama dan batal mudah dibedakan.
- Modal tidak terlalu besar untuk tugas sederhana.
- Drawer cocok untuk detail agenda atau navigasi mobile.

### Sidebar
- Background: `--color-sidebar` (#242238).
- Teks menu: putih/off-white.
- Hover: `--color-sidebar-hover` (#34314B).
- Menu aktif: background `--color-primary` (#D8F36A), teks `--color-primary-text` (#1A2200), tidak memakai teks putih.
- Ikon dan teks harus konsisten dalam ukuran dan alignment.

## 6. Warna semantik untuk status tugas/absensi

| Status | Warna | Token |
|---|---|---|
| Selesai / Terkumpul | Hijau | `--color-success` |
| Belum dikumpulkan | Muted/abu | `--color-muted` |
| Mendekati deadline | Amber | `--color-warning` |
| Terlambat | Rose/merah | `--color-agenda-terlambat` |
| Hadir | Hijau | `--color-success` |
| Tidak hadir | Rose | `--color-danger` |
| Izin/Sakit | Amber | `--color-warning` |

## 7. Konsistensi lintas role
Gunakan satu shell aplikasi dan komponen bersama. Perbedaan role berupa:
- Menu navigasi (item berbeda per role).
- Data yang ditampilkan.
- Aksi yang tersedia.
- Ringkasan dashboard.

Jangan membuat tiga sistem desain yang terpisah untuk mahasiswa, dosen, dan admin.
