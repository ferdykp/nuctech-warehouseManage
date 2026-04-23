# Nuctech Warehouse Management System

Sistem manajemen inventaris dan sparepart berbasis web yang dirancang khusus untuk memonitor, melacak, dan mengelola pergerakan stok sparepart mesin Nuctech di berbagai cabang dan lokasi (site) di seluruh Indonesia.

## 🌟 Fitur Utama

- **Multi-Branch & Multi-Site Management**: Mengelola sparepart berdasarkan hierarki Cabang (Branch) dan Lokasi Mesin (Site).
- **Manajemen Inventaris Terpadu**: Melacak status dan kondisi stok setiap sparepart secara detail (Baru, Bekas, Rusak, atau Hasil Perbaikan).
- **Tracking & Movement (Transfer Stok)**: Memindahkan stok sparepart dari satu mesin/lokasi ke lokasi lain lengkap dengan pencatatan riwayat (History) otomatis.
- **Bulk Import Excel (Smart Mapping)**: Fitur cerdas untuk mengimpor data sparepart secara massal melalui file Excel (.xlsx, .xls, .csv). Sistem akan secara otomatis mendeteksi kolom yang relevan dan menggunakan antarmuka AJAX dengan progress *real-time toast*.
- **Export Excel dengan Gambar**: Mengekspor daftar inventaris sparepart pada suatu site ke dalam format Excel, lengkap dengan gambar (thumbnail) barang.
- **Reporting System**: Pencatatan pelaporan (report) harian/berkala yang dilengkapi dengan dokumentasi foto.
- **Role-based Access**: Hak akses yang dibedakan antara `Admin` (akses penuh untuk CRUD dan pemindahan stok) dan `User` (hanya melihat/read-only).

## 🛠️ Teknologi yang Digunakan

- **Framework**: Laravel 12.x
- **Bahasa**: PHP 8.4
- **Database**: MySQL
- **Frontend**: Tailwind CSS, FontAwesome, Vanilla JS (AJAX)
- **Library Tambahan**: 
  - `maatwebsite/excel` (Untuk Import & Export Excel)
  - `phpoffice/phpspreadsheet` (Pemrosesan raw file Excel)

## 🚀 Panduan Instalasi (Local Development)

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi di komputer lokal:

### 1. Kebutuhan Sistem (Prerequisites)
Pastikan Anda sudah menginstal:
- PHP >= 8.4
- Composer
- Node.js & NPM
- MySQL Server

> [!IMPORTANT]  
> **Konfigurasi `php.ini`**: Karena aplikasi ini memiliki fitur *Bulk Import Excel* untuk data yang cukup besar, Anda **wajib** mengubah nilai konfigurasi berikut pada file `php.ini` server Anda (misal: Herd/XAMPP):
> ```ini
> upload_max_filesize = 20M
> post_max_size = 20M
> ```
> *Jangan lupa restart PHP / Web Server setelah mengubah konfigurasi.*

### 2. Instalasi Aplikasi

1. Clone repositori ini atau ekstrak source code ke direktori lokal Anda.
2. Buka terminal, masuk ke folder proyek, lalu instal dependensi PHP:
   ```bash
   composer install
   ```
3. Salin file environment bawaan:
   ```bash
   cp .env.example .env
   ```
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Buka file `.env` dan atur koneksi database Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sparepart-nuctech
   DB_USERNAME=root
   DB_PASSWORD=
   ```
6. Buat tautan storage (agar gambar yang diupload bisa diakses publik):
   ```bash
   php artisan storage:link
   ```
7. Jalankan migrasi database dan *seeder* (untuk mengisi data awal / dummy data):
   ```bash
   php artisan migrate --seed
   ```
8. Instal dependensi frontend dan *compile* aset Tailwind CSS:
   ```bash
   npm install
   npm run build
   ```

### 3. Menjalankan Server
Jalankan development server Laravel:
```bash
php artisan serve
```
Aplikasi sekarang dapat diakses di browser pada: `http://127.0.0.1:8000`

## 👥 Akun Default (Seeder)
Jika Anda menjalankan perintah `php artisan migrate --seed`, aplikasi akan memiliki dua akun bawaan yang bisa digunakan untuk login:

- **Admin**
  - Email: `admin@gmail.com`
  - Password: `password`
- **User Biasa**
  - Email: `user@gmail.com`
  - Password: `password`

## 📄 Lisensi
Aplikasi ini bersifat tertutup (Proprietary) dan ditujukan khusus untuk keperluan manajemen internal Nuctech. Menggandakan atau mendistribusikan source code tanpa izin tidak diperkenankan.
