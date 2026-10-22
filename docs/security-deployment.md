# Deploy perbaikan keamanan

Perubahan ini mempertahankan halaman dan alur bisnis. Lampiran reimbursement/cuti sekarang
memerlukan sesi login dan hak akses yang sama dengan record terkait. URL `/storage/receipts/*`,
`/storage/signatures/*`, dan `/storage/leave_attachments/*` yang lama tidak lagi digunakan.
Kontrak dan sertifikat yang sudah privat tetap menggunakan disk `local`.

## Sebelum deploy

- Backup database, `/etc/nginx`, `.env`, dan seluruh `storage/app` ke lokasi di luar web root.
  Backup juga harus dilindungi; jangan taruh arsip backup pada `public/` atau disk publik.
- Pastikan production menggunakan HTTPS, `APP_ENV=production`, `APP_DEBUG=false`,
  `APP_URL=https://domain-anda`, dan `SESSION_SECURE_COOKIE=true`.
- Set `TRUSTED_PROXIES` ke IP/CIDR proxy sebenarnya, dipisahkan koma. Default hanya
  `127.0.0.1,::1`; jangan memakai `*`. Jika TLS diakhiri pada proxy eksternal, masukkan
  alamat proxy tersebut dan pastikan proxy menimpa header forwarded dari klien.
  Batasi akses origin dengan firewall bila hanya proxy yang boleh mengaksesnya.
- Build frontend bersama rilis ini (`npm ci`, `npm run build`) supaya modal lampiran
  menggunakan endpoint baru. Jangan memakai aset JavaScript dari rilis lama.
- Periksa/ganti password akun production yang berasal dari seeder lama. Mengubah seeder
  tidak mengubah akun yang sudah tersimpan. Seeder sekarang tidak membuat akun; untuk
  instalasi baru gunakan `php artisan security:create-admin` di terminal interaktif.
- Dependency dan lockfile sudah diperbarui serta diuji lokal. Di server jalankan
  `composer install --no-dev --prefer-dist --optimize-autoloader`, bukan `composer update`.
  Resolver Composer memakai platform PHP 8.2.35 agar paket tetap kompatibel dengan
  PHP 8.2–8.4; runtime server tetap wajib memakai patch PHP terbaru.
- Build frontend membutuhkan Node >=22.13.0 (disarankan Node 24 LTS). PDF.js 6 memuat
  worker, font, CMap, dan decoder dari aplikasi sendiri. Deploy seluruh `public/build`,
  termasuk direktori `pdfjs/`, bukan hanya berkas manifest dan JavaScript.
- Audit Composer dan npm production bersih pada pemeriksaan 22 Oktober 2026. Audit npm
  lengkap masih mencatat 5 temuan high pada rantai alat build Tailwind 3/braces.
  Belum ada patch braces yang kompatibel tersedia. Ini berkaitan dengan pemrosesan
  pola build, bukan paket runtime browser. Jangan menerima konfigurasi/pola build dari
  pengguna atau menjalankan Vite dev server di internet. Migrasi Tailwind 4 perlu
  pekerjaan terpisah beserta pengujian visual untuk mempertahankan tampilan.

## Urutan deployment pada Nginx

1. Tambahkan aturan berikut pada setiap `server` block aplikasi, termasuk HTTPS, sebelum
   lokasi regex PHP. Tempatkan sebelum lokasi regex lain yang lebih umum. Pastikan tidak
   ada lokasi `^~ /storage/` yang melewati aturan ini; bila ada, tambahkan penolakan yang
   setara di lokasi tersebut. Ini penting karena maintenance Laravel tidak memblokir file statis.

   ```nginx
   location ~* ^/storage/(receipts|signatures|leave_attachments)(/|$) {
       return 404;
   }
   ```

   Jalankan `sudo nginx -t` lalu `sudo systemctl reload nginx`.
   Untuk Apache, aturan penolakan telah ditambahkan ke `public/.htaccess`; pastikan
   `mod_rewrite` dan `AllowOverride` berlaku atau pasang aturan setara di virtual host.
   Web root harus menunjuk ke direktori `public/`, bukan root proyek.

2. Masuk maintenance (`php artisan down`), hentikan sementara worker queue melalui
   process manager yang digunakan, lalu deploy kode dan aset baru. Pastikan pengguna
   PHP-FPM/worker memiliki akses tulis ke `storage/app/private/sensitive`.
3. Bersihkan cache konfigurasi lama lalu migrasikan file menggunakan pengguna aplikasi:

   ```bash
   php artisan config:clear
   php artisan security:privatize-attachments --dry-run
   php artisan security:privatize-attachments
   ```

   Perintah memindahkan semua file di tiga direktori sensitif pada disk publik,
   termasuk arsip dan file tanda tangan. Setiap salinan diverifikasi SHA-256 sebelum
   salinan publik dihapus. Konflik isi menghentikan proses tanpa menimpa file tujuan.
   Perintah aman diulang setelah penyebab error diperbaiki. Jangan membuka maintenance
   jika migrasi gagal. Direktori publik lain, seperti foto sparepart/laporan, tetap dipertahankan.
   Bila ada penyimpanan historis di luar `storage/app/public`, inventarisasi dan pindahkan
   file tersebut secara terverifikasi ke lokasi privat yang sama sebelum membuka aplikasi.
4. Jalankan `php artisan optimize`, aktifkan kembali worker queue, lalu `php artisan up`.
   Jika menggunakan release directory, pastikan storage privat dan publik dibagikan antar rilis.
5. Uji login, upload, preview gambar/PDF, tanda tangan, approval, export PDF/Excel, dan
   lampiran cuti. Uji akses dari pengguna beda site serta browser tanpa login.
   Endpoint dokumen privat harus menolak pengguna yang tidak berwenang. URL statis lama
   harus 403/404, termasuk jika file pernah tersimpan pada CDN.
6. Purge cache CDN untuk ketiga prefix storage lama bila CDN pernah menyimpan file tersebut.

Tidak perlu menjalankan `migrate:fresh` atau seeder untuk rilis ini. Tidak ada perubahan skema DB.
Queue job penghapusan lama tetap kompatibel; aturan Nginx harus tetap dipertahankan.

## Rollback

Jangan mengembalikan dokumen sensitif ke direktori publik hanya untuk menjalankan kode lama.
Pertahankan blokir Nginx dan storage privat. Jika perbaikan perlu ditarik, masuk maintenance
dan siapkan rilis perbaikan yang tetap membaca storage privat. Simpan backup awal sampai
pengujian produksi selesai.
