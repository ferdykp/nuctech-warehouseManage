# Nuctech Warehouse Management System

Sistem manajemen inventaris dan sparepart berbasis web yang dirancang khusus untuk memonitor, melacak, dan mengelola pergerakan stok sparepart mesin Nuctech di berbagai cabang dan lokasi (site) di seluruh Indonesia.

## 🌟 Fitur Utama

- **Multi-Branch & Multi-Site Management**: Mengelola sparepart berdasarkan hierarki Cabang (Branch) dan Lokasi Mesin (Site).
- **Manajemen Inventaris Terpadu**: Melacak status dan kondisi stok setiap sparepart secara detail (Baru, Bekas, Rusak, atau Hasil Perbaikan).
- **Tracking & Movement (Transfer Stok)**: Memindahkan stok sparepart dari satu mesin/lokasi ke lokasi lain lengkap dengan pencatatan riwayat (History) otomatis.
- **Bulk Import Excel (Smart Mapping)**: Fitur cerdas untuk mengimpor data sparepart secara massal melalui file Excel (.xlsx, .xls, .csv). Sistem akan secara otomatis mendeteksi kolom yang relevan dan menggunakan antarmuka AJAX dengan progress *real-time toast*.
- **Export Excel dengan Gambar**: Mengekspor daftar inventaris sparepart pada suatu site ke dalam format Excel, lengkap dengan gambar (thumbnail) barang.
- **Reporting System**: Pencatatan pelaporan (report) harian/berkala yang dilengkapi dengan dokumentasi foto.
- **Role-based Access**: `superadmin`, `administration`, `team_leader`, `station_master`, `manager`, dan `employee_role`, dengan pembatasan site dan tahap approval.
- **HR & Payroll**: Data karyawan, jadwal, absensi, cuti, dan payroll bulanan.
- **Reimbursement**: Pengajuan klaim, tanda tangan bertahap, import/export, dan recycle bin.

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
   DB_DATABASE=nuctech_warehouse
   DB_USERNAME=your_local_user
   DB_PASSWORD=your_local_password
   QUEUE_CONNECTION=database
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

## 📄 Lisensi
Aplikasi ini bersifat tertutup (Proprietary) dan ditujukan khusus untuk keperluan manajemen internal Nuctech. Menggandakan atau mendistribusikan source code tanpa izin tidak diperkenankan.

## Pengujian

```sh
php artisan test
node --test tests/Frontend/*.test.mjs
npm run build
```

GitHub Actions menjalankan ketiga pemeriksaan pada push dan pull request. Tes backend menggunakan SQLite in-memory, storage palsu, dan HTTP fake. Tidak menggunakan database operasional atau mengirim notifikasi Telegram.

## Perbaikan audit Oktober 2026

- Fast approve hanya untuk superadmin; approval biasa tetap bertahap. Klaim yang sudah memiliki tanda tangan tidak dapat diedit. Pengajuan revisi dibuat sebagai klaim baru.
- Pengelolaan master site hanya untuk superadmin. Slug site tetap stabil setelah nama diubah.
- Seluruh aksi jadwal memakai scope site yang sama, termasuk kelompok mesin team leader yang sudah digunakan aplikasi. Akun tanpa site tidak memperoleh akses global.
- Endpoint absensi hanya mengirim atribut yang dibutuhkan kalender.
- Payroll historis tidak dihapus atau ditimpa saat generate ulang. Gaji periode yang sudah ada dipertahankan; pembuatan periode baru memakai histori gaji jika tersedia. Lembur hari libur berdasarkan absensi yang sudah disimpan, dengan kalkulasi yang sama pada halaman dan Excel. Rumus tarif dasar yang sudah ada dipertahankan.
- Import reimbursement mendukung cell numerik dan teks Indonesia seperti `1.000.000,50`; format ambigu ditolak dengan nomor baris.
- Kegagalan sinkronisasi kalender memakai data valid terakhir dan menampilkan peringatan; percobaan ulang paling cepat lima menit.

### Penerapan ke database yang sudah ada

```sh
php artisan migrate
php artisan queue:restart
```

Migrasi menambahkan `reports.site_id` dan memetakan slug/nama site lama yang cocok secara unik. Nilai lama yang ambigu/tidak cocok tetap disimpan dengan `site_id = null`; superadmin perlu meninjaunya melalui pencarian/ekspor laporan dan menetapkan site lewat edit. Jangan menebak pemetaan site lama.

Penghapusan lampiran dijalankan oleh job setelah transaksi berhasil. Jalankan worker dengan supervisor pada server, misalnya `php artisan queue:work --tries=5`. Pantau `php artisan queue:failed` dan gunakan `queue:retry` setelah masalah storage diperbaiki. Migrasi dan worker harus diterapkan bersama perubahan kode.

Periode payroll saat ini tetap mengikuti `created_at` sesuai skema lama; jangan mengubah timestamp histori secara manual. Pengujian MySQL produksi, beban besar, dan dokumen PDF kompleks tetap diperlukan sebelum rilis.
