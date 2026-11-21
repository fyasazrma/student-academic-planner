# Design System — Student Academic Planner

Dokumen ini menetapkan token dan pola komponen yang digunakan di seluruh aplikasi. Sesuaikan implementasinya dengan versi Tailwind dan struktur proyek yang benar-benar tersedia.

> **Tailwind versi aktual:** Tailwind CSS v4 (`@tailwindcss/vite`). Token didefinisikan via `@theme {}` di `resources/css/app.css`, bukan di `tailwind.config.js`.

> **Revisi arah visual (disetujui pengguna, 21 Nov 2026):** aplikasi mengikuti gambar referensi **Eduplex Dashboard** di `docs/reference/eduplex-dashboard.png`. Tema lama (lavender pucat `#F0E9FF`, card ber-border, radius 16px, topbar berlatar) **digantikan** oleh dokumen ini. Warna di bawah diambil langsung dari piksel gambar referensi.

## 0. Prinsip kemiripan dengan referensi
1. **Tiru gaya, bukan konten.** Warna, bentuk, radius, bayangan, tipografi, ritme spasi, dan pola komponen ditiru sedekat mungkin. Konten referensi (nama kursus, rating bintang, "Go Premium", "Download mobile app", angka jam) **tidak** disalin; semua diganti data nyata aplikasi (lihat Bagian 7).
2. **Bentuk sebelum warna.** Ciri khas referensi: bingkai aplikasi mengambang dengan sudut sangat bulat, sidebar navy yang juga mengambang, card putih tanpa border, tombol/pil `rounded-full`, ubin ikon pastel, dan aksen lime hanya untuk hal yang aktif/utama.
3. **Kalender tetap elemen dominan** (aturan produk di `AGENTS.md` §6). Gaya kalender mengikuti referensi, tetapi ukurannya tidak ikut mengecil seperti kalender mini di gambar.
4. **Interaktif tetapi ringan** (lihat Bagian 8): CSS-first, tanpa library animasi/grafik berat.

## 1. Design tokens

Gunakan token terpusat melalui CSS custom properties (`@theme {}` di `resources/css/app.css`). Jangan menyebarkan nilai warna acak di banyak file.

### Warna permukaan dan teks

| Token CSS | Nilai | Keterangan |
|---|---|---|
| `--color-page-bg` | `#C2A5F9` | Latar luar lavender (jenuh, sesuai referensi) |
| `--color-surface` | `#F8F8F8` | Bingkai/permukaan aplikasi utama |
| `--color-card` | `#FFFFFF` | Card dan panel |
| `--color-soft` | `#F4F4F4` | Latar lunak: pil hari kalender, tombol ikon, input |
| `--color-border` | `#ECECF2` | Garis halus: gridline chart, pemisah, tombol outline |
| `--color-sidebar` | `#201F31` | Sidebar & kartu fitur gelap (navy) |
| `--color-sidebar-hover` | `#2E2C45` | Hover item sidebar |
| `--color-sidebar-text` | `#C9C8DA` | Teks menu non-aktif di sidebar (kontras 9,8:1) |
| `--color-text` | `#201F31` | Teks utama (navy-charcoal) |
| `--color-muted` | `#686980` | Teks sekunder. **Menggantikan `#77788A`** yang hanya 4,34:1 di atas putih (gagal AA); `#686980` = 5,36:1 |

### Aksen dan semantik

| Token CSS | Nilai | Keterangan |
|---|---|---|
| `--color-primary` | `#DAF17C` | Lime — menu aktif, CTA, hari ini, tombol "+" bulat |
| `--color-primary-hover` | `#C9E35F` | Hover lime |
| `--color-primary-text` | `#1A2200` | Teks di atas lime (wajib gelap) |
| `--color-success` | `#42B883` | Grafik/ikon sukses (bukan untuk teks kecil) |
| `--color-success-text` | `#1F7A55` | Teks sukses di atas putih (5,29:1), mis. "+3%" |
| `--color-progress` | `#9CCB3B` | Cincin progres (selalu disertai angka persen) |
| `--color-warning` | `#E9B94F` | Perhatian / mendekati deadline |
| `--color-danger` | `#D96C75` | Bahaya / terlambat |
| `--color-info` | `#5D9CEC` | Informasi |
| `--color-badge` | `#F9D3BE` | Latar badge hitung notifikasi (peach) |
| `--color-badge-text` | `#7A3512` | Teks badge (6,43:1) |

### Warna ubin ikon dan pil status (pastel)

| Token CSS | Nilai | Dipakai untuk |
|---|---|---|
| `--color-tile-peach` | `#FFE5DE` | Ubin ikon / pil "Akan datang" (`#F9E2D4` pada pil) |
| `--color-tile-lime` | `#F1F8CC` | Ubin ikon / pil "Selesai" (`#F0F8C5` pada pil) |
| `--color-tile-lavender` | `#DFDDF3` | Ubin ikon / pil "Sedang dikerjakan" |
| `--color-tile-sand` | `#FCF1D5` | Ubin ikon |
| `--color-tile-sky` | `#DCEBFB` | Ubin ikon (tambahan, tidak ada di referensi, agar variasi cukup) |
| `--color-tile-rose` | `#F7D9DC` | Pil "Terlambat" (tambahan) |

Pasangan teks pada pil (semua lolos AA): Sedang dikerjakan `#3F3A8C` di `#DFDDF3` (7,2:1); Selesai `#3B4A00` di `#F0F8C5` (8,8:1); Akan datang `#8A3F17` di `#F9E2D4` (6,0:1); Terlambat `#8E2A36` di `#F7D9DC` (6,3:1); Belum dikumpulkan `#4B4C60` di `#EDEDF3` (7,2:1).

### Warna agenda kalender (fungsional, tidak berubah)

| Token CSS | Nilai | Jenis agenda |
|---|---|---|
| `--color-agenda-tugas` | `#9A78E8` | Tugas |
| `--color-agenda-uts` | `#76A9E8` | UTS |
| `--color-agenda-uas` | `#5B8FD0` | UAS |
| `--color-agenda-akademik` | `#E78DB7` | Agenda akademik institusi |
| `--color-agenda-kuliah` | `#8DBE83` | Jadwal kuliah |
| `--color-agenda-pengumuman` | `#E9B94F` | Pengumuman |
| `--color-agenda-terlambat` | `#D96C75` | Status terlambat |

Warna agenda dipakai sebagai dot/penanda kecil dan strip kiri event, **selalu** disertai label teks atau ikon.

### Radius, bayangan, motion

| Token CSS | Nilai | Utility | Dipakai untuk |
|---|---|---|---|
| `--radius-frame` | `2rem` (32px) | `rounded-frame` | Bingkai aplikasi (desktop) |
| `--radius-panel` | `1.5rem` (24px) | `rounded-panel` | Sidebar, drawer |
| `--radius-card` | `1.25rem` (20px) | `rounded-card` | Semua card/panel |
| `--radius-tile` | `0.875rem` (14px) | `rounded-tile` | Ubin ikon |
| `--radius-control` | `0.75rem` (12px) | `rounded-control` | Input, tombol ikon persegi |
| — | penuh | `rounded-full` | Tombol, pil, badge, avatar, item menu aktif |

| Token | Nilai | Dipakai untuk |
|---|---|---|
| `--shadow-frame` | `0 24px 60px -12px rgb(32 31 49 / 0.28)` | Bingkai aplikasi |
| `--shadow-card` | `0 1px 2px rgb(32 31 49 / 0.04), 0 6px 20px rgb(32 31 49 / 0.05)` | Card default (menggantikan border) |
| `--shadow-card-hover` | `0 2px 4px rgb(32 31 49 / 0.05), 0 12px 28px rgb(32 31 49 / 0.09)` | Card yang bisa diklik, saat hover |

### Contoh deklarasi di `resources/css/app.css`

```css
@import 'tailwindcss';

@theme {
    --font-sans: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;

    /* Surface */
    --color-page-bg:        #C2A5F9;
    --color-surface:        #F8F8F8;
    --color-card:           #FFFFFF;
    --color-soft:           #F4F4F4;
    --color-border:         #ECECF2;

    /* Sidebar & dark card */
    --color-sidebar:        #201F31;
    --color-sidebar-hover:  #2E2C45;
    --color-sidebar-text:   #C9C8DA;

    /* Primary (lime) */
    --color-primary:        #DAF17C;
    --color-primary-hover:  #C9E35F;
    --color-primary-text:   #1A2200;

    /* Text */
    --color-text:           #201F31;
    --color-muted:          #686980;

    /* Semantic */
    --color-success:        #42B883;
    --color-success-text:   #1F7A55;
    --color-progress:       #9CCB3B;
    --color-warning:        #E9B94F;
    --color-danger:         #D96C75;
    --color-info:           #5D9CEC;
    --color-badge:          #F9D3BE;
    --color-badge-text:     #7A3512;

    /* Pastel tiles & pills */
    --color-tile-peach:     #FFE5DE;
    --color-tile-lime:      #F1F8CC;
    --color-tile-lavender:  #DFDDF3;
    --color-tile-sand:      #FCF1D5;
    --color-tile-sky:       #DCEBFB;
    --color-tile-rose:      #F7D9DC;

    /* Agenda */
    --color-agenda-tugas:        #9A78E8;
    --color-agenda-uts:          #76A9E8;
    --color-agenda-uas:          #5B8FD0;
    --color-agenda-akademik:     #E78DB7;
    --color-agenda-kuliah:       #8DBE83;
    --color-agenda-pengumuman:   #E9B94F;
    --color-agenda-terlambat:    #D96C75;

    /* Radius */
    --radius-frame:   2rem;
    --radius-panel:   1.5rem;
    --radius-card:    1.25rem;
    --radius-tile:    0.875rem;
    --radius-control: 0.75rem;

    /* Shadow */
    --shadow-frame:      0 24px 60px -12px rgb(32 31 49 / 0.28);
    --shadow-card:       0 1px 2px rgb(32 31 49 / 0.04), 0 6px 20px rgb(32 31 49 / 0.05);
    --shadow-card-hover: 0 2px 4px rgb(32 31 49 / 0.05), 0 12px 28px rgb(32 31 49 / 0.09);

    /* Motion */
    --animate-bar-grow: bar-grow 450ms cubic-bezier(0.22, 1, 0.36, 1) both;
    @keyframes bar-grow {
        from { transform: scaleY(0); }
        to   { transform: scaleY(1); }
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

Teks di atas lime wajib `--color-primary-text`, bukan putih. Teks di atas pil pastel memakai pasangan gelap di atas, bukan `--color-muted`. Warna `--color-success`, `--color-progress`, dan lime **tidak boleh** menjadi satu-satunya pembawa informasi pada teks/grafik (kontras terhadap putih rendah); selalu sertakan angka, label, atau tooltip.

## 2. Tipografi
- Font: **Plus Jakarta Sans** (tetap; sudah dikonfigurasi via `bunny()` di `vite.config.js`, weight 400/500/600/700). Bentuk geometris-nya paling mendekati font referensi. Tidak perlu menambah font atau mengubah `vite.config.js`.
- Gunakan maksimal 4 weight agar ringan.

| Peran | Kelas Tailwind | Catatan |
|---|---|---|
| Sapaan halaman | `text-2xl lg:text-3xl font-bold tracking-tight text-text` | "Halo, Taylor 👋" |
| Judul section | `text-lg font-bold text-text` | "Kalender Akademik", "Tugas" |
| Judul card/baris | `text-[0.9375rem] font-semibold` | Nama mata kuliah/tugas |
| Isi | `text-sm font-medium` | |
| Meta label | `text-xs font-medium text-muted` | "Sisa", "Tipe" |
| Meta nilai | `text-xs font-semibold text-text` | |
| Pil/badge | `text-xs font-semibold` | |
| Angka besar | `text-2xl font-bold` | Hanya jika angka nyata |
| Sumbu chart | `text-xs text-muted` | |

Minimum 12px untuk informasi penting. Satu emoji sapaan (👋) pada judul halaman adalah satu-satunya emoji yang diizinkan di UI.

## 3. Spacing dan ukuran
- Skala spacing Tailwind: gap antar card `gap-5` (20px); padding card `p-5`; padding bingkai `p-4`.
- Tinggi kontrol: tombol/pil/input `h-10`; kontrol kecil (dropdown pil, tombol ikon) `h-8`; item sidebar `h-11`.
- Ubin ikon: `size-11 rounded-tile`; ikon `size-5`, stroke tipis (1.5–1.75), satu library saja.
- Tombol ikon bulat (chevron, panah): `size-8 rounded-full bg-soft` atau `border border-border`.
- Tombol "+" bulat: `size-8 rounded-full bg-primary text-primary-text`.
- Batasi lebar konten form panjang.

## 4. Pola layout

### Application Shell (bingkai mengambang)
```
[page-bg lavender, padding luar]
  └─ [Bingkai surface #F8F8F8, rounded-frame, shadow-frame, p-4]
       ├─ [Sidebar navy mengambang, rounded-panel, lebar 232px, tinggi penuh bingkai]
       ├─ [Area utama: header sapaan + konten]
       └─ [Panel kanan 300px (dashboard saja)]
```
- **Desktop ≥1280px:** `p-6 xl:p-10` pada pembungkus luar; bingkai `grid xl:grid-cols-[232px_minmax(0,1fr)_300px] gap-5`. Tinggi bingkai mengikuti konten (tidak dipaksa 100vh); sidebar `sticky top` bila konten panjang.
- **Tablet 1024–1279px:** sidebar tetap; panel kanan turun ke bawah konten utama (grid 2 kolom bila muat).
- **Mobile <1024px:** padding luar dihapus, bingkai `rounded-none shadow-none` (penuh layar), sidebar menjadi drawer `rounded-r-panel`, satu kolom; panel kanan masuk ke aliran konten setelah kalender/agenda.
- **Tidak ada topbar berlatar.** "Topbar" adalah baris header di dalam area utama: sapaan/judul di kiri; pencarian (jika ada), lonceng notifikasi, dan avatar di kanan. Tanpa shadow, tanpa garis bawah, tanpa latar.

### Komponen layout Blade yang wajib reusable
- `<x-sidebar>`, `<x-topbar>` (header tanpa latar), `<x-app-layout>` (bingkai + grid), `<x-page-header>`, `<x-notification-panel>`.
- Tambahan: `<x-card>`, `<x-section-header>`, `<x-icon-tile>`, `<x-pill>`, `<x-icon-button>`, `<x-bar-chart>`, `<x-progress-ring>`, `<x-mini-calendar>`, `<x-feature-card>`.

## 5. Pola komponen

### Sidebar (navy mengambang)
- Latar `bg-sidebar`, `rounded-panel`, `p-4`; logo + nama aplikasi di atas.
- Item: `h-11 rounded-full px-4 gap-3 text-sm font-medium text-sidebar-text`, ikon outline `size-5`. Hover `hover:bg-sidebar-hover hover:text-white`.
- **Aktif:** `bg-primary text-primary-text font-semibold` berbentuk pil penuh. Bukan teks putih.
- Badge hitung (mis. notifikasi belum dibaca) di kanan item: `size-5 rounded-full bg-badge text-badge-text text-xs font-semibold`; hanya tampil bila angka > 0 dan berasal dari query nyata.
- Bagian bawah: **kartu lime** `bg-primary rounded-card p-4` dengan tombol bulat putih berpanah (`size-11 rounded-full bg-white`) yang menimpa tepi atas kartu, berisi **pintasan nyata per role** (mahasiswa: Target Belajar aktif; dosen: Buat sesi absensi; admin: Kelola jadwal ujian). Jika tidak ada pintasan nyata, hilangkan kartu; jangan isi dengan "Download mobile app" palsu. Profil dan logout tetap tersedia (di bawah kartu atau di menu avatar).

### Card
- `bg-card rounded-card shadow-card p-5`. **Tanpa border** (border hanya untuk tombol outline, input, dan pemisah dalam). 
- Card yang dapat diklik: `transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-card-hover` + `focus-visible:ring-2 ring-primary-hover`.
- Jangan menaruh setiap baris dalam card terpisah tanpa alasan; daftar pendek (Jadwal, Tugas, Mata Kuliah) boleh berupa baris putih `rounded-card` berdampingan seperti referensi **hanya** bila setiap baris adalah tautan.

### Header section
Judul `text-lg font-bold` di kiri; di kanan salah satu: tautan "Lihat semua" (`text-xs font-medium text-muted underline underline-offset-2`), dropdown pil, atau tombol "+" bulat (hanya bila ada aksi nyata).

### Ubin ikon (icon tile)
`size-11 rounded-tile grid place-items-center` dengan latar pastel (`tile-*`). Warna dipilih deterministik dari ID entitas (`id % 5`) agar mata kuliah yang sama selalu berwarna sama. Untuk mata kuliah: ikon tunggal konsisten atau inisial kode mata kuliah; jangan mengimpor ikon acak.

### Baris daftar (Jadwal, Tugas, Mata Kuliah)
`flex items-center gap-3`: ubin ikon → judul (`font-semibold`) + subjudul muted → di kanan tombol chevron bulat (`size-8 rounded-full bg-soft`) atau pil status. Seluruh baris adalah satu `<a>` ber-`href` nyata.

### Pil status (badge)
`h-7 rounded-full px-3 text-xs font-semibold`. Label eksplisit: "Sedang dikerjakan" (lavender), "Terkumpul"/"Selesai" (lime muda), "Akan datang" (peach), "Terlambat" (rose), "Belum dikumpulkan" (abu). Pasangan warna di Bagian 1.

### Tombol
- **Primary:** `h-10 rounded-full px-5 bg-primary text-primary-text font-semibold hover:bg-primary-hover`, focus ring primary. Satu per area.
- **Secondary:** `rounded-full border border-border bg-card text-text`.
- **Ghost:** tanpa latar/border.
- **Danger:** latar rose/merah, teks kontras.
- **Dropdown pil** ("Mingguan ⌄"): `h-8 rounded-full border border-border px-3 text-xs font-medium`.
- Semua tombol punya state hover, `focus-visible`, disabled, dan loading bila relevan.

### Input & pencarian
Input `h-10 rounded-control bg-soft` (atau `border`), label tampil. Pencarian di header berbentuk pil `h-10 rounded-full bg-card shadow-card` dengan ikon kaca pembesar. **Hanya tampilkan bila route pencarian nyata sudah ada**; jika belum, hilangkan.

### Avatar
`size-10 rounded-full`; foto bila tersedia, jika tidak inisial nama di atas latar pastel. Jangan mengasumsikan fitur unggah foto sudah ada; periksa kode.

### Grafik batang (Aktivitas Belajar)
Dibangun **server-side dari HTML/CSS (atau SVG inline)**; tanpa Chart.js/ApexCharts.
- Header: judul; baris delta `[kotak ikon panah-serong size-7 rounded-lg bg-soft] +3% dibanding minggu lalu` (angka `text-success-text font-semibold`, hanya bila minggu lalu > 0); di kanan dropdown pil "Mingguan".
- Area plot: label sumbu-Y kiri (5 tick, `text-xs text-muted`), gridline horizontal 1px `border-border`, label sumbu-X di bawah (Sen…Min).
- Batang: lebar ±10px, `rounded-full`, warna `bg-sidebar` (navy). Batang **hari ini** (default) atau yang sedang di-hover/focus berwarna `bg-primary` (lime) dan label hari-nya `font-bold`.
- Tooltip: `bg-sidebar text-white rounded-control px-3 py-2 text-xs`, dua baris (nilai tebal + tanggal), dengan segitiga kecil; muncul saat hover **dan** focus (`group-hover` / `group-focus-visible`), bukan hanya hover.
- Aksesibilitas: setiap batang `tabindex="0"` dengan `aria-label="Senin 17 Nov: 5 aktivitas"`. Karena lime di atas putih hanya 1,25:1, penyorotan **tidak boleh** bergantung warna: sertakan tooltip dan label sumbu tebal.
- Animasi masuk: `animate-bar-grow` (`transform-origin: bottom`), delay bertahap via `style="animation-delay: calc(var(--i) * 40ms)"`.
- Empty state bila seluruh nilai 0: teks "Belum ada aktivitas minggu ini", tanpa batang palsu.

### Cincin progres
SVG 36px: lingkaran track `stroke-border`, lingkaran indikator `stroke-progress`, `stroke-linecap: round`, `stroke-dasharray` dari persen; di sebelahnya angka `text-sm font-semibold` ("45%"). Transisi `stroke-dashoffset` 500ms saat muat.

### Mini kalender (navigator, panel kanan)
- Header: tombol bulat `‹` `›` (`size-8 rounded-full border`) mengapit nama bulan `font-semibold` (Bahasa Indonesia, mis. "November 2026").
- Baris nama hari dalam pil `bg-soft rounded-control`; minggu mulai **Senin**: Sen Sel Rab Kam Jum Sab Min (2–3 huruf; satu huruf ambigu dalam Bahasa Indonesia).
- Tanggal: sel `size-8 rounded-full text-sm`; bulan lain `text-muted/60`; **hari ini** `bg-primary text-primary-text font-semibold`; tanggal terpilih cincin navy 2px; tanggal berisi agenda diberi dot 4px di bawah angka berwarna kategori (maksimal 3 dot).
- Fungsi: **navigator** untuk kalender utama (klik tanggal → kalender utama berpindah ke tanggal itu, progressive enhancement lewat tautan `?tanggal=YYYY-MM-DD`). Bukan kalender kedua yang berdiri sendiri.

### Kartu fitur gelap (panel kanan, pengganti "Go Premium")
`bg-sidebar text-white rounded-card p-5`; judul `text-xl font-bold`, deskripsi `text-sm` warna `sidebar-text`, tombol primary lime berbentuk pil. Isi: Target Belajar aktif (real) untuk mahasiswa; pintasan relevan untuk role lain. Ilustrasi dekoratif opsional: SVG inline buatan sendiri < 3 KB, **bukan** salinan ilustrasi referensi; tanpa gambar bitmap.

### Kalender — Calendar View (area dominan)
- Dibungkus card besar (`rounded-card shadow-card p-5`), lebar penuh area utama, tinggi minimal ±520px; ini card terbesar di dashboard.
- Toolbar: judul periode (`text-lg font-bold`), tombol bulat `‹` `›`, tombol "Hari ini" (pil secondary), tab pil Bulan/Minggu/Agenda (tab aktif `bg-sidebar text-white` atau `bg-primary text-primary-text`), filter jenis agenda (pil), legenda warna.
- Sel hari: `rounded-control`, hari ini diberi lingkaran lime pada angkanya; event berupa pil kecil dengan strip warna kategori + label; jika >2 event tampilkan "+N lagi".
- Tugas menampilkan tanggal dipublikasikan dan deadline secara berbeda; UTS/UAS berlabel eksplisit dan berwarna berbeda.
- Klik event membuka drawer/modal detail. Versi agenda/list untuk mobile. Empty state saat tidak ada agenda.

### Kalender — Timeline View
Tetap seperti aturan sebelumnya (bar horizontal publikasi → deadline, titik publikasi hollow, deadline diamond, ujian sebagai marker). Gaya: bar `rounded-full` tinggi 28px, label di dalam/di samping bar, sumbu tanggal `text-xs text-muted`, garis tanggal `border-border`. Warna bar: default ungu (`agenda-tugas`), terlambat rose, selesai hijau.

### Modal dan Drawer
Judul dan tujuan jelas; `rounded-card` (modal) / `rounded-l-panel` (drawer); overlay `bg-sidebar/40` tanpa blur. Aksi utama (primary lime) dan batal (ghost) mudah dibedakan.

### Table
Header `bg-soft text-muted uppercase tracking-wide text-xs`, baris dengan spacing cukup, pemisah `border-border`, empty state dan pagination bila perlu; mobile: overflow-x scroll atau list/card.


### Modal dan Drawer
- Modal selalu tampil di tengah viewport secara horizontal dan vertikal, bukan menempel di pojok kiri atas atau mengikuti posisi elemen pemicu.
- Gunakan overlay yang menutupi viewport, dengan latar `bg-sidebar/40` tanpa blur.
- Modal memiliki `rounded-card`, lebar responsif, batas tinggi viewport, dan konten yang dapat di-scroll jika diperlukan.
- Animasi masuk: opacity 0 ke 1 dan scale 0.96 ke 1.
- Animasi keluar: opacity 1 ke 0 dan scale 1 ke 0.96.
- Durasi animasi 150–200ms, menggunakan transform dan opacity.
- Tutup modal melalui tombol tutup, tombol batal, atau Escape jika sesuai. Klik overlay boleh menutup modal jika tidak berisiko kehilangan data.
- Drawer digunakan untuk navigasi atau detail samping, bukan menggantikan modal konfirmasi.
- Hormati `prefers-reduced-motion`.

## 6. Warna semantik untuk status tugas/absensi

| Status | Pil | Token teks/latar |
|---|---|---|
| Selesai / Terkumpul | Lime muda | `#3B4A00` / `tile-lime` |
| Sedang dikerjakan | Lavender | `#3F3A8C` / `tile-lavender` |
| Belum dikumpulkan | Abu | `#4B4C60` / `#EDEDF3` |
| Akan datang / Belum dimulai | Peach | `#8A3F17` / `#F9E2D4` |
| Mendekati deadline | Amber | teks gelap / `warning` 20% |
| Terlambat | Rose | `#8E2A36` / `tile-rose` |
| Hadir | Lime muda | seperti Selesai |
| Tidak hadir (alfa) | Rose | seperti Terlambat |
| Izin/Sakit | Amber | seperti Mendekati deadline |

## 7. Pemetaan widget referensi → aplikasi (hanya data nyata)

| Widget di gambar referensi | Padanan di aplikasi | Sumber data | Catatan |
|---|---|---|---|
| Sapaan "Welcome back Taylor 👋" | "Halo, {nama} 👋" + NIM · tahun akademik · semester aktif | `users`, `tahun_akademik` | Persis gaya referensi |
| Pencarian + avatar | Pencarian (jika ada route nyata) + avatar/inisial | — | Pencarian disembunyikan jika belum ada |
| 3 kartu "New Courses" (ikon, judul, Rate, Type) | 3 kartu ringkas: Tugas aktif, Deadline terdekat, Ujian mendatang (ikon, judul, 2 meta) | `tugas`, `pengumpulan_tugas`, `agenda_akademik` | Tanpa rating bintang |
| "Hours Activity" (bar mingguan + tooltip) | **Aktivitas Belajar**: jumlah aktivitas per hari (buka/unduh materi + pengumpulan tugas) | `aktivitas_materi`, `pengumpulan_tugas` | **Skema saat ini tidak mencatat jam belajar**, jadi satuannya "aktivitas", bukan jam. Butuh query baru → minta persetujuan (lihat `AGENTS.md` §9) |
| "Daily Schedule" | **Jadwal Hari Ini** (mata kuliah, jenis · ruang · jam) | `jadwal_kuliah` | Maks 4 baris + "Lihat semua" |
| Kalender mini (Agustus) | Mini kalender sebagai **navigator** kalender utama | `agenda_akademik`, `tugas`, `jadwal_kuliah` | Kalender utama tetap dominan |
| "Go Premium" (kartu gelap) | Kartu fitur gelap: **Target Belajar** aktif | `target_belajar` | Pembayaran/premium dilarang (`SYSTEM_REQUIREMENTS` §9) |
| "Assignments" + pil status | **Tugas** + pil status | `tugas`, `pengumpulan_tugas` | "+" hanya untuk dosen (route buat tugas) |
| "Course You're Taking" + cincin % | **Mata Kuliah Diikuti**: dosen, sisa materi, cincin % | `peserta_mata_kuliah`, `progres_materi` | "Sisa" dalam materi, bukan jam; tombol "+" disembunyikan (form gabung kelas mahasiswa belum ada) |
| "Download our mobile app" | Pintasan nyata per role (lihat Sidebar) | — | Tidak ada aplikasi mobile |
| Badge "2" pada Notifications | Badge belum dibaca | `notifikasi` | Hanya bila > 0 |

Susunan dashboard mahasiswa (desktop): Header → 3 kartu ringkas → **Kalender Akademik (card terbesar, tab Calendar/Timeline)** → [Aktivitas Belajar | Jadwal Hari Ini] → Mata Kuliah Diikuti. Panel kanan: kartu fitur gelap → mini kalender → Tugas & Deadline → Notifikasi terbaru. Dosen dan admin memakai kerangka sama dengan isi sesuai `AGENTS.md` §7.

## 8. Interaksi dan performa (interaktif tapi ringan)

**Prinsip:** CSS dulu, JavaScript seperlunya, server-render dulu.

| Interaksi | Implementasi |
|---|---|
| Hover/fokus card dan baris | CSS: `transition-[transform,box-shadow] duration-200`, `hover:-translate-y-0.5` |
| Tooltip chart | CSS `group-hover` + `group-focus-visible`; tanpa JS |
| Batang chart dan cincin progres | CSS animation/transition saat muat (`bar-grow`, `stroke-dashoffset`) |
| Tab Calendar/Timeline, dropdown, drawer, modal, filter | Gunakan Alpine.js **hanya jika sudah terpasang**; bila tidak, satu modul vanilla `resources/js/ui.js` (event delegation, < 3 KB) |
| Mini kalender → kalender utama | Tautan `?tanggal=` (bekerja tanpa JS); JS hanya meningkatkan agar tanpa reload penuh |
| Dropdown "Mingguan/Bulanan" | Parameter query server-side atau toggle dua dataset yang sudah ada di halaman |
| Menandai notifikasi dibaca, tandai progres | Form POST biasa; boleh `fetch` kecil dengan fallback form |

**Anggaran performa:**
- Dilarang menambah library animasi/grafik/UI (GSAP, Framer Motion, Chart.js, ApexCharts, jQuery UI, dsb.) tanpa persetujuan. Tambahan JS per halaman ideal < 15 KB gzip.
- Animasikan hanya `transform` dan `opacity`; durasi 150–250 ms (intro chart maksimal 450 ms); easing ease-out.
- Hormati `prefers-reduced-motion` (blok CSS pada Bagian 1).
- Tanpa `backdrop-filter`, `filter: blur`, glow, atau animasi `infinite` dekoratif. `animate-pulse` hanya untuk loading nyata.
- Shadow card hanya dua lapis halus (token `shadow-card`); jangan menumpuk shadow besar pada puluhan elemen.
- Ikon: satu library, impor per ikon/inline SVG; tanpa bitmap ilustrasi. Gambar di bawah lipatan `loading="lazy"`.
- Kalender bulanan maksimal 42 sel; hindari DOM berlebihan per sel (tampilkan "+N lagi"). Card di bawah lipatan boleh `content-visibility: auto`.
- Hindari N+1 (eager loading/agregasi) pada seluruh widget dashboard.
- Verifikasi: `npm run build` dan laporkan ukuran bundle CSS/JS sebelum dan sesudah perubahan.

## 9. Daftar periksa kemiripan dengan referensi
Bandingkan hasil dengan `docs/reference/eduplex-dashboard.png` pada lebar 1440 px dan 390 px. Semua harus terpenuhi:
1. Latar luar lavender jenuh `#C2A5F9`; bingkai off-white `rounded-frame` dengan bayangan lembut dan jarak ke tepi layar.
2. Sidebar navy **mengambang** di dalam bingkai (bukan menempel ke tepi layar), `rounded-panel`.
3. Item sidebar aktif berbentuk pil lime penuh; ikon outline tipis; badge notifikasi peach.
4. Tidak ada topbar berlatar; sapaan besar di kiri, pencarian/avatar di kanan.
5. Card putih **tanpa border**, radius ±20px, bayangan sangat halus.
6. Ubin ikon pastel `rounded-tile` pada setiap baris/kartu ringkas.
7. Tombol, pil, tab, dropdown, dan avatar `rounded-full`; CTA lime dengan teks gelap.
8. Grafik batang navy ber-sudut bulat, satu batang lime, tooltip gelap, gridline tipis, label hari di bawah.
9. Pil status pastel (lavender/lime muda/peach/rose) dengan label teks.
10. Cincin progres + persen di baris mata kuliah.
11. Mini kalender: tombol bulat, pil nama hari, hari ini lingkaran lime.
12. Kartu fitur gelap berisi tombol lime di panel kanan.
13. Kalender utama tetap menjadi card terbesar di dashboard.
14. Tidak ada elemen palsu: tidak ada rating, premium, "download app", angka jam, atau tombol tanpa aksi.
15. Mobile 390 px: bingkai penuh layar, sidebar drawer, satu kolom, kalender dalam bentuk agenda/list.

## 10. Konsistensi lintas role
Satu shell dan satu set komponen untuk mahasiswa, dosen, dan admin. Perbedaan hanya pada menu, data, aksi, dan ringkasan dashboard. Jangan membuat tiga sistem desain terpisah.

