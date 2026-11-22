<div align="center">

# 🖼️ Lumina — Modern Image Gallery & Visual Collection

**Aplikasi Galeri Gambar Full-Stack dengan Arsitektur Single-Page Application (SPA-like), Desain Masonry Grid Modern, dan Sistem Penyimpanan Media Teroptimasi.**

[![PHP](https://img.shields.io/badge/PHP-8.x%20Native-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![GitHub Actions](https://img.shields.io/badge/CI%2FCD-GitHub_Actions-2088FF?style=for-the-badge&logo=github-actions&logoColor=white)](https://github.com/features/actions)

[Demo Aplikasi](http://pictures.site.je) • [Fitur Utama](#-fitur-utama) • [Teknologi](#-teknologi-yang-digunakan) • [Panduan Instalasi](#-panduan-instalasi--menjalankan-lokal) • [Skema Database](#-skema-database-sql)

</div>

---

## 📌 Tentang Proyek

**Lumina Gallery** adalah platform galeri gambar berbasis web yang terinspirasi dari antarmuka Pinterest. Dibangun menggunakan arsitektur **Native PHP** dan **Vanilla JavaScript (ES6+)**, aplikasi ini dirancang untuk memberikan pengalaman pengguna yang cepat dan mulus melalui navigasi berbasis seksi tanpa reload halaman penuh (*SPA-like experience*).

Aplikasi ini mengimplementasikan standar keamanan web modern, mulai dari proteksi **CSRF token**, *Prepared Statements* untuk mitigasi **SQL Injection**, hashing kata sandi berbasis **Bcrypt**, hingga *server hardening* dengan konfigurasi `.htaccess`.

---

## ✨ Fitur Utama

- 🎨 **Pinterest-Style Masonry Grid**: Tata letak kartu asimetris yang responsif, dilengkapi efek transisi halus, overlay interaktif saat *hover*, serta visualisasi gambar beresolusi proporsional.
- ⚡ **Navigasi Cepat (SPA-like Experience)**: Perpindahan antar-halaman (*Home*, *Profile*, *Favorites*, *Upload*, dan *Image Details*) dilakukan di sisi klien secara instan menggunakan Vanilla JS DOM handling tanpa refresh halaman.
- 🔐 **Autentikasi & Keamanan Berlapis**:
  - Registrasi dan login akun dengan enkripsi kata sandi menggunakan `password_hash()` (standar Bcrypt).
  - Manajemen sesi pengguna berbasis PHP Native Session.
  - Validasi **CSRF Token** manual pada setiap pengiriman form upload dan aksi POST API.
  - Semua query database menggunakan **Prepared Statements (`mysqli`)** untuk mencegah kerentanan SQL Injection.
  - Server Apache hardening melalui `.htaccess` untuk memblokir penjelajahan direktori (*directory listing*) dan proteksi file tersembunyi/sensitif.
- 📤 **Engine Upload & Streaming Media Teroptimasi**:
  - Gambar disimpan dalam bentuk *binary data* (`LONGBLOB`) langsung di database.
  - Pengiriman media ditangani oleh handler streaming khusus (`img_view.php`) yang menyematkan header HTTP `Cache-Control: public, max-age=86400, immutable` untuk mempercepat pemuatan aset dan menghemat *bandwidth*.
- ❤️ **Sistem Favorit & Bookmark Real-time**: Pengguna dapat menambahkan atau menghapus gambar favorit secara instan menggunakan Fetch API (AJAX) dengan indikator visual dan notifikasi toast dinamis.
- 🔍 **Pencarian Gambar Dinamis**: Pencarian real-time berdasarkan kata kunci judul maupun keterangan gambar.
- 👤 **Manajemen Galeri Pengguna**: Halaman profil pribadi untuk memantau koleksi foto yang diunggah, dengan hak akses pengeditan dan penghapusan foto secara aman.

---

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi / Alat | Keterangan |
| :--- | :--- | :--- |
| **Backend** | PHP 8.x Native (Procedural & OOP) | Tanpa framework pihak ketiga, menggunakan ekstensi `mysqli` |
| **Database** | MySQL / MariaDB | Relasional database dengan indexing optimal |
| **Frontend** | HTML5, Vanilla JavaScript (ES6+) | Fetch API (AJAX), Toast notifications, modal management |
| **Styling** | Tailwind CSS (Tailwind Play CDN) | Custom design token tema, forms & container query plugins |
| **Tipografi & Ikon** | Google Fonts (Inter) & Material Symbols | Variable web font & vector icon library |
| **Web Server** | Apache (Laragon / XAMPP) | Modul `mod_rewrite` dan `mod_authz_core` |
| **DevOps & CI/CD** | GitHub Actions | Automated FTP Deploy ke server hosting produksi |

---

## 📂 Struktur Direktori

```text
├── .github/
│   └── workflows/
│       └── deploy.yml        # Alur kerja CI/CD untuk otomatisasi deploy ke hosting
├── .gitignore                # Aturan pengabaian file sensitif, kredensial, dan cache
├── .htaccess                 # Konfigurasi keamanan server Apache
├── api.php                   # REST-style endpoint untuk aksi AJAX (favorit, detail gambar)
├── auth.php                  # Endpoint autentikasi (login, register, logout)
├── img_view.php              # Streamer media binary gambar dengan HTTP cache header
├── index.php                 # Halaman utama aplikasi (SPA container & layout)
├── koneksi.example.php       # Template konfigurasi koneksi database publik
├── optimize_db.php           # Script automasi pembuatan indeks database untuk performa
├── render_cards.php          # Helper perender markup kartu galeri untuk AJAX response
└── README.md                 # Dokumentasi proyek
```

---

## 🚀 Panduan Instalasi & Menjalankan Lokal

### Prasyarat Sistem
- Web Server lokal: **Laragon** (Sangat Direkomendasikan) atau **XAMPP**
- **PHP** versi 7.4 ke atas (Direkomendasikan PHP 8.1 / 8.2)
- **MySQL / MariaDB**
- Web browser modern (Chrome, Edge, Firefox)

### Langkah-langkah

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/GrayZeus20/tubes-praktikum.git
   cd tubes-praktikum
   ```

2. **Pindahkan Proyek ke Direktori Web Server**:
   * **Laragon**: letakkan folder di `C:\laragon\www\tubes-praktikum`
   * **XAMPP**: letakkan folder di `C:\xampp\htdocs\tubes-praktikum`

3. **Buat Database**:
   * Buka phpMyAdmin (`http://localhost/phpmyadmin`).
   * Buat database baru bernama `tubes`.
   * Buka tab **SQL**, salin dan jalankan script pada bagian [Skema Database](#-skema-database-sql) di bawah.

4. **Konfigurasi Koneksi Database**:
   * Salin file `koneksi.example.php` dan beri nama `koneksi.php`:
     ```bash
     cp koneksi.example.php koneksi.php
     ```
   * Sesuaikan kredensial di dalam `koneksi.php` jika Anda menggunakan password pada MySQL lokal:
     ```php
     $host     = 'localhost';
     $username = 'root';
     $password = '';      // Isi password database lokal jika ada
     $dbname   = 'tubes';
     ```

5. **Jalankan Aplikasi**:
   * Buka browser dan akses:
     ```text
     http://localhost/tubes-praktikum
     ```
     *(atau `http://tubes-praktikum.test` jika menggunakan fitur auto-virtualhost Laragon).*

---

## 🗄️ Skema Database (SQL)

Salin kode SQL berikut ke phpMyAdmin untuk membuat seluruh tabel dan relasi yang dibutuhkan:

```sql
-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS `tubes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tubes`;

-- 1. Tabel Admin (Pengguna)
CREATE TABLE IF NOT EXISTS `admin` (
  `id_user` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tabel Gambar
CREATE TABLE IF NOT EXISTS `tb_gambar` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `gambar` LONGBLOB NOT NULL,
  `username` VARCHAR(255) NOT NULL,
  `nama_gambar` VARCHAR(255) NOT NULL,
  `tipe_gambar` VARCHAR(255) NOT NULL,
  `ukuran_gambar` INT(11) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `keterangan` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_tb_gambar_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tabel Favorit
CREATE TABLE IF NOT EXISTS `favorit` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_user` INT(11) NOT NULL,
  `id_gambar` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_favorit_user` (`id_user`),
  INDEX `idx_favorit_gambar` (`id_gambar`),
  CONSTRAINT `fk_favorit_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_favorit_gambar` FOREIGN KEY (`id_gambar`) REFERENCES `tb_gambar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 🔒 Praktik Keamanan yang Diterapkan

- **Perlindungan Data Rahasia**: File koneksi database asli (`koneksi.php`) dan data sensitif diabaikan dari pelacakan git via `.gitignore`.
- **Prepared Statements**: Seluruh kueri dinamis yang melibatkan input pengguna diproses menggunakan `mysqli::prepare` dan `bind_param` untuk menjamin sterilisasi dari serangan SQL Injection.
- **Kriptografi Password**: Kata sandi disimpan dalam format hash kriptografis yang aman menggunakan `password_hash($pass, PASSWORD_DEFAULT)`.
- **CSRF Token**: Dilengkapi token acak berkriptografi aman (`random_bytes(32)`) yang diverifikasi pada setiap request mutasi data.
- **Apache Hardening (`.htaccess`)**:
  - `Options -Indexes` untuk mencegah kebocoran struktur file direktori.
  - Aturan *rewrite* untuk memblokir akses HTTP langsung ke file tersembunyi (seperti `.git`, `.env`).

---

## 👤 Pengembang

**Moh. Dandi January**  
*Web Developer*  
- 🌐 Portofolio: [dandifolio.my.id](https://dandifolio.my.id)
- 🐙 GitHub: [@GrayZeus20](https://github.com/GrayZeus20)
- 💼 LinkedIn: [Moh. Dandi January](https://linkedin.com)

---

<div align="center">
  <sub>Dibangun untuk keperluan pemenuhan tugas praktikum pemrograman web. Dikembangkan dengan ❤️ dan dedikasi pada clean code.</sub>
</div>
