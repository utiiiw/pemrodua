# KrakatauPulse

Dashboard sentimen masyarakat tentang abu vulkanik Anak Krakatau. Tampilan menampilkan ringkasan sentimen, kategori topik, pencarian komentar, filter, uji sentimen, dan ekspor CSV.

## Menjalankan

Gunakan PHP 8.1 atau lebih baru dengan OpenSSL dan opsi allow_url_fopen aktif. Dari folder proyek:

```sh
php -S 127.0.0.1:8765 router.php
```

Buka `http://127.0.0.1:8765`. Router hanya melayani dashboard dan API; berkas konfigurasi serta direktori Git tidak dapat diakses lewat server ini.

`index.html` juga bisa dibuka untuk pratinjau statis. Pengambilan data Apify memerlukan server PHP; GitHub Pages hanya dapat menampilkan pratinjau.

## Konfigurasi Apify

Jika `.env` belum ada, salin `.env.example` menjadi `.env`. Isi di server:

```dotenv
APIFY_TOKEN=TOKEN_ANDA
APIFY_DATASET_ID=ID_DATASET_ANDA
```

Token dibaca oleh `api.php` dan dikirim ke Apify melalui header autentikasi. Token tidak dikirim ke browser. `.env` dikecualikan dari Git; `.env.example` hanya berisi nama variabel. Pada Apache, aktifkan dukungan `.htaccess` agar berkas tersembunyi tetap terlindungi.

Pilih **Sumber data → Ambil data Apify** untuk memuat hingga 8 komentar. API membaca dataset yang sudah ada dan tidak menjalankan Actor.

Field komentar yang didukung: `text`, `comment`, `commentText`, atau `caption`. Tautan dibaca dari `commentUrl`, `sourceUrl`, `url`, atau `postUrl`. Penulis bisa berasal dari `authorName`, `username`, `ownerUsername`, `channelName`, atau objek `author`. Data video ditandai sebagai **unggahan video**, bukan komentar penonton.

## Data dan sentimen

Dataset awal berisi 8 komentar **simulasi**, bukan hasil pengambilan media sosial. Tautan asli yang belum tersedia ditandai pada kartu komentar.

Sentimen dari Apify digunakan jika tersedia. Jika tidak, dashboard memakai perkiraan kata kunci; metode ini tidak memahami konteks dan negasi, dan bukan integrasi Gemini.

Komentar uji hanya tersimpan di memori halaman. Ekspor CSV sebelum menutup atau memuat ulang halaman. CSV menyertakan komentar, tautan sumber, dan jenis data; nilai yang bisa dibaca sebagai formula dinetralkan.

Grafik menggunakan Chart.js dan font dari Google Fonts melalui CDN. Saat grafik tidak tersedia, ringkasan angka dan kategori tetap bisa dibaca.

## Pemeriksaan

Tanpa memasang dependensi proyek tambahan:

```sh
node tests/check.cjs
php tests/check.php
php -l api.php
php -l router.php
```

Token yang pernah masuk riwayat Git tetap harus dicabut dan diganti di akun Apify. Memindahkannya ke `.env` hanya menghapusnya dari versi terbaru.
