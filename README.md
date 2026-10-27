# SIA SMK Malaka — Kelompok 4: Modul Guru

Sistem Informasi Akademik Sederhana SMK Malaka berbasis PHP 8+, PDO (MySQL / MariaDB & SQLite), HTML5, CSS3, dan JavaScript vanilla tanpa dependensi framework eksternal yang berat.

Modul ini berfokus pada **Kelompok 4: Entitas Guru** sesuai dengan Diagram Entitas Relasi (ERD) dan spesifikasi PRD.

---

## 1. Ruang Lingkup dan ERD

Sesuai diagram ERD, entitas Guru memiliki 3 atribut utama:

| Atribut | Tipe Data | Deskripsi / Fungsi |
|---|---|---|
| `KODE_GURU` | `VARCHAR(20)` | **Primary Key** — Identitas unik guru (contoh: `G001`, `G002`) |
| `NAMA` | `VARCHAR(100)` | Nama lengkap guru beserta gelar |
| `FOTOGURU` | `MEDIUMBLOB` | Berkas foto guru disimpan langsung dalam basis data |

ERD juga mendefinisikan relasi **`Diampu`** antara Guru dan Pelajaran dengan atribut:
- `KODE_GURU` (Foreign Key ke tabel `guru`)
- `KD_PELAJARAN` (Foreign Key ke tabel `pelajaran`)
- `HARI` (Hari mengajar)

> **Catatan Batas Ruang Lingkup:**<br>
> Atribut `KD_PELAJARAN` dan `HARI` adalah bagian dari relasi `Diampu`, bukan kolom tabel `guru`. Modul Guru tidak menambahkan kolom non-standar (seperti email, alamat, telepon, atau jenis kelamin) guna menjaga integritas rancangan basis data awal.

---

## 2. Fitur Modul Guru

1. **Daftar Guru (Read & Pagination):** Menampilkan daftar guru dari basis data nyata dengan penomoran, kode guru, nama, foto thumbnail, dan tombol aksi.
2. **Pencarian Data (Search):** Pencarian real-time aman berdasarkan `KODE_GURU` atau `NAMA` menggunakan prepared statements dan escaping wildcard.
3. **Tambah Guru (Create Modal):** Form input kode guru, nama, dan unggah foto dengan validasi kelengkapan serta pratinjau gambar langsung.
4. **Validasi Kode Guru Unik:** Mencegah kode duplikat dengan penanganan error yang jelas bagi pengguna.
5. **Edit Guru (Update Modal):** Form pembaruan nama guru. Primary key `KODE_GURU` dikunci (readonly) untuk menjaga integritas foreign key.
6. **Manajemen Foto yang Fleksibel:** Foto lama tetap dipertahankan jika tidak memilih foto pengganti. Pengguna dapat memilih berkas baru untuk mengganti foto.
7. **Hapus Guru dengan Konfirmasi:** Dialog konfirmasi penghapusan menampilkan identitas guru. Dilengkapi perlindungan foreign key constraint terhadap relasi `Diampu`.
8. **Halaman Detail Guru:** Tampilan detail satu guru dengan foto berukuran besar, identitas lengkap, tombol navigasi kembali, dan aksi cepat.
9. **Antarmuka Responsif (Desktop & Mobile Android):** Tata letak otomatis menyesuaikan ke mode kartu kompak pada ponsel pintar Android dan layar sempit.
10. **Penanganan Error dan Status Kosong:** Penanganan kondisi data kosong (empty state) serta pesan ramah ketika terjadi kendala koneksi basis data.

---

## 3. Struktur Berkas Repositori

```text
tugaskelompok4/
├── api/
│   └── index.php                 # Serverless router untuk deployment Vercel
├── assets/
│   ├── css/
│   │   └── style.css             # Desain antarmuka (Navy sidebar, Royal Blue, Card modern)
│   ├── img/
│   │   ├── admin-avatar.svg      # Avatar profil Admin
│   │   ├── avatar-placeholder.svg# Siluet placeholder guru tanpa foto
│   │   ├── favicon.png           # Favicon PNG
│   │   ├── favicon.svg           # Favicon SVG
│   │   ├── logo.svg              # Logo akademik SMK Malaka
│   │   └── school-hero.svg       # Ilustrasi gedung sekolah untuk dashboard
│   └── js/
│       └── app.js                # Interaktivitas modal, mobile drawer, dan pratinjau foto
├── config/
│   ├── aiven-ca.pem              # Sertifikat CA TLS MySQL Aiven
│   └── database.example.php      # Contoh konfigurasi koneksi database
├── database/
│   ├── schema_guru.sql           # Skema tabel guru (Kelompok 4)
│   └── schema_sia_kelompok4.sql  # Skema lengkap (Murid, Pelajaran, Guru, Mempelajari, Diampu)
├── includes/
│   ├── app.php                   # Bootstrap sesi, CSRF, flash message, dan exception handler
│   ├── controller.php            # Router dan handler tampilan halaman
│   ├── db.php                    # Pengelola koneksi PDO MySQL TLS & SQLite
│   ├── guru.php                  # Model operasi data entitas guru
│   ├── layout.php                # Templating sidebar, topbar, dan footer responsif
│   └── validation.php            # Validasi input teks dan berkas foto
├── tests/
│   ├── database_smoke.php        # Uji end-to-end operasi database real, CRUD, dan foreign key
│   └── validation_test.php       # Uji unit validasi kode, nama, dan berkas foto
├── .gitignore                    # Berkas yang diabaikan Git (kredensial, sqlite, artefak)
├── detail_guru.php               # Halaman akses langsung detail guru
├── guru.php                      # Halaman akses langsung modul guru
├── index.php                     # Titik masuk utama aplikasi (Dashboard & Modul)
├── README.md                     # Dokumentasi teknis proyek
└── vercel.json                   # Konfigurasi deployment ke Vercel
```

---

## 4. Konfigurasi Basis Data

### Opsi A: MySQL / MariaDB (Lokal via XAMPP)
1. Buat basis data `sia_malaka_kelompok4` di phpMyAdmin atau MySQL CLI.
2. Impor skema:
   ```sql
   source database/schema_sia_kelompok4.sql;
   ```
3. Salin berkas `config/database.example.php` menjadi `config/database.php`.
4. Sesuaikan `host`, `user`, dan `password` lokal Anda.

### Opsi B: MySQL di Cloud Aiven (TLS)
1. Buat layanan MySQL di konsol Aiven.
2. Unduh sertifikat CA ke `config/aiven-ca.pem`.
3. Jalankan `database/schema_sia_kelompok4.sql`.
4. Atur `config/database.php` dengan `ssl => true` dan path CA yang sesuai.

### Opsi C: SQLite Otomatis (Untuk Pengujian & Mandiri)
Jika `DB_DRIVER=sqlite` diaktifkan atau server MySQL lokal belum aktif pada `127.0.0.1:3306`, aplikasi secara cerdas beralih ke basis data SQLite relasional di `database/database.sqlite` sehingga pengujian dan demonstrasi tetap berjalan dengan basis data nyata tanpa hambatan.

---

## 5. Cara Menjalankan Aplikasi

### Melalui PHP Built-in Server
Pastikan PHP 8.1+ telah terpasang di sistem:
```powershell
# Jalankan web server lokal pada root proyek
php -S 127.0.0.1:8000
```
Buka browser dan akses:
- **Modul Guru:** `http://127.0.0.1:8000/guru.php`
- **Dashboard:** `http://127.0.0.1:8000/index.php?page=dashboard`

### Melalui XAMPP
1. Salin seluruh folder `tugaskelompok4` ke folder `C:\xampp\htdocs\tugaskelompok4`.
2. Nyalakan modul Apache dan MySQL pada XAMPP Control Panel.
3. Akses `http://localhost/tugaskelompok4/guru.php`.

---

## 6. Deploy ke Vercel

1. Hubungkan repositori GitHub ini ke dashboard Vercel.
2. Di menu **Settings > Environment Variables**, tambahkan:
   - `DB_HOST`: Host database MySQL dari penyedia (misal Aiven)
   - `DB_PORT`: Port MySQL (misal `26036` atau `3306`)
   - `DB_NAME`: `sia_malaka_kelompok4`
   - `DB_USER`: Pengguna basis data
   - `DB_PASSWORD`: Kata sandi basis data (tandai sebagai Secret)
   - `DB_SSL`: `1`
   - `DB_SSL_CA`: `config/aiven-ca.pem`
3. Vercel akan otomatis menyajikan aset statis dan mengeksekusi PHP melalui serverless function `api/index.php`. Foto tersimpan aman sebagai BLOB di database sehingga tidak terhapus saat instance serverless berganti.

---

## 7. Keamanan dan Validasi

- **Pencegahan SQL Injection:** 100% kueri database menggunakan PDO Prepared Statements dengan parameter binding terpisah.
- **Pencegahan Cross-Site Scripting (XSS):** Semua output dinamis disanitasi menggunakan `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **Perlindungan CSRF:** Setiap formulir POST dilindungi dengan token CSRF unik per sesi yang divalidasi via `hash_equals()`.
- **Validasi Unggahan Berkas:**
  - Batas ukuran berkas maksimal **3 MB**.
  - Pemeriksaan ekstensi whitelist: `.jpg`, `.jpeg`, `.png`, `.webp`.
  - Pemeriksaan MIME type dan integritas konten gambar menggunakan `getimagesize()`.
  - Pencegahan pengunggahan berkas executable atau script berbahaya.
- **Integritas Relasi:** Dilengkapi penanganan `FOREIGN KEY` constraint `ON DELETE RESTRICT` pada relasi `Diampu` agar guru yang memiliki jadwal mengajar tidak terhapus secara tidak disengaja.

---

## 8. Menjalankan Pengujian Mandiri

Repositori ini menyertakan suite pengujian otomatis untuk memverifikasi fungsionalitas dan keamanan:

```powershell
# 1. Pemeriksaan sintaks seluruh berkas PHP
Get-ChildItem -Recurse -Filter '*.php' | ForEach-Object { php -l $_.FullName }

# 2. Uji unit validasi data teks dan berkas gambar (19 pengujian)
php tests/validation_test.php

# 3. Uji smoke test database relasional nyata (21 pengujian)
php tests/database_smoke.php
```

Semua pengujian dirancang untuk berjalan otomatis dan memverifikasi integritas sistem tanpa memerlukan dependensi pihak ketiga.
