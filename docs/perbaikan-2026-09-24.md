# Perbaikan sesi, akses, dan integritas data — 24 September 2026

## Perubahan

- Header `no-store` sekarang benar-benar dipasang, termasuk pada halaman login. Halaman dari back/forward cache diperiksa ulang; konten lama disembunyikan saat meninggalkan halaman.
- Logout mengakhiri sesi server dan mengarahkan tab lain pada browser yang sama ke login.
- Batas tidak aktif default 30 menit (`SESSION_IDLE_TIMEOUT`). Interaksi pengguna memperbarui aktivitas; pemeriksaan status latar belakang tidak memperpanjang batas tidak aktif. Validasi server tetap berlaku meskipun JavaScript dimatikan.
- Form dengan token CSRF kedaluwarsa kembali ke login; request JSON mendapat 419. Fetch, Axios, dan jQuery mengarahkan halaman terautentikasi ke login ketika mendapat 401/419.
- `/login` dan GET `/login/auth` diarahkan ke halaman login. Password tidak disimpan sebagai old input. Login dibatasi 10 percobaan per menit per IP.
- Pengguna biasa tidak dapat mengubah profil/password akun lain. Nama role konsisten menggunakan `employee_role`. Semua pengguna terautentikasi dapat mengakses profil sendiri.
- Route menuju method yang tidak tersedia dihapus jika tidak digunakan; export laporan, clear/export jadwal, dan pembaruan pola site disambungkan ke implementasi yang tersedia.
- Pembatasan site diterapkan pada mutasi laporan harian, cuti, jadwal, dan absensi. Cuti diperiksa lagi terhadap saldo saat approval. Approval berulang tidak memotong saldo lagi.
- Jadwal tetap mempertahankan aturan kelompok mesin team leader yang sudah digunakan proyek (lima karakter awal nama mesin); absensi memakai cakupan yang sama. Pengguna tanpa site tidak otomatis mendapat akses semua site.
- Transfer dan koreksi stok memakai transaksi serta penguncian record. Status diperiksa kembali di dalam transaksi. Koreksi jumlah ke atas diperbolehkan; perubahan kondisi tidak boleh melebihi stok.
- Penghapusan sementara daily report mempertahankan foto; penghapusan permanen memeriksa akses site.
- Reimbursement memeriksa kepemilikan, site pemeriksa, dan urutan approval. Parameter `all_site` tidak dapat memperluas akses pengguna biasa. Tanda tangan disimpan sebagai array JSON, identitas penanda tangan berasal dari sesi, dan lampiran harus tersedia sebelum approval.
- Upload gambar sparepart dan matriks absensi divalidasi. Tanggal resign tersimpan dan jadwal setelah tanggal tersebut ditolak.
- Rename site tidak lagi mengganti slug sehingga URL inventori tetap stabil.
- Generate payroll tidak menghapus catatan lama hanya karena karyawan kini resign; pemilihan karyawan memperhatikan periode masuk/resign. Rumus nominal/prorata payroll tidak diubah.
- Webhook Telegram wajib memiliki secret, ID pengguna yang diizinkan, dan chat tujuan yang sesuai konfigurasi. Backup memakai argumen proses terpisah, konfigurasi database Laravel, timeout, dan pembersihan file sementara.
- Dua migrasi perubahan enum menggunakan schema builder sehingga database uji SQLite dapat menjalankan seluruh migrasi.

## Konfigurasi

Default sesi sudah aktif tanpa mengubah `.env`. Jika diperlukan:

```dotenv
SESSION_IDLE_TIMEOUT=30
SESSION_LIFETIME=120
```

Sesi perangkat/browser lain tetap independen. Logout antar-tab berlaku untuk tab dengan sesi browser yang sama.

Untuk mengaktifkan kembali perintah Telegram, isi:

```dotenv
TELEGRAM_WEBHOOK_SECRET=<secret-acak>
TELEGRAM_ALLOWED_USER_IDS=<id-pengguna-telegram, dipisahkan-koma>
TELEGRAM_CHAT_ID=<chat-yang-diizinkan>
```

Secret yang sama harus diberikan sebagai `secret_token` saat mendaftarkan webhook Telegram. Tanpa konfigurasi tersebut, webhook menolak request dengan 403. Pengujian tidak mengirim pesan atau backup ke Telegram.

Setelah perubahan dipasang pada server, bangun asset dan perbarui cache konfigurasi/route/view sesuai prosedur deployment:

```sh
npm run build
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Bila deployment menggunakan cache konfigurasi/route, buat ulang cache setelah environment produksi benar. Perubahan ini belum dideploy ke server produksi. Konfigurasi document root/rewrite server produksi belum diverifikasi; apabila masih ada 404 di produksi, periksa document root `public` serta log web server.

## Verifikasi

- 28 test, 417 assertion lulus dengan database SQLite `:memory:`; mencakup halaman utama, export, login/logout/login ulang, CSRF kedaluwarsa, idle timeout, hak akses, stok, cuti, absensi, resign, URL site, payroll historis, serta dua tahap tanda tangan reimbursement.
- Migrasi lengkap berhasil pada database uji terpisah.
- Build Vite, pemeriksaan sintaks PHP, dan `git diff --check` lulus.
- Browser lokal dengan akun/database sementara: login, logout, logout pada tab kedua, Back setelah logout, auto logout, dan login ulang setelah auto logout berhasil. Timeout khusus server uji dipercepat menjadi satu menit; default aplikasi tetap 30 menit.
- Database operasional dan konfigurasi `.env` pengguna tidak diubah.

Penguncian konkurensi MySQL sudah diterapkan pada transfer stok dan approval cuti, tetapi belum diuji beban dengan beberapa koneksi MySQL bersamaan. Backup Telegram dan pemrosesan PDF melalui Ghostscript belum diuji terhadap layanan/file produksi. Pengujian ini bukan jaminan bahwa setiap kemungkinan bug di seluruh aplikasi sudah tidak ada.
