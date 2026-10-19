# Kontrak Karyawan

Modul tersedia melalui sidebar **Kontrak Karyawan** untuk Superadmin dan Administration, serta melalui tombol **Kontrak** pada tabel karyawan dan **Riwayat Kontrak** pada modal rincian karyawan.

## Alur penggunaan

1. Buka kontrak yang perlu verifikasi atau pilih **Siapkan Kontrak** dan karyawan aktif.
2. Periksa identitas, nomor, tanggal PKWT, periode inklusif, jenis pekerjaan, pola kerja, cuti, perwakilan perusahaan, dan versi template.
3. Simpan draft, lalu pilih **Buat Draft Word / PDF**. Unduh dan periksa dokumennya.
4. Setelah penandatanganan di luar aplikasi selesai, unggah PDF final. Catat tanggal pemberitahuan, respons karyawan, dan tanggal respons.
5. Centang pemeriksaan admin lalu **Konfirmasi Kontrak**. Status menjadi Aktif, Terjadwal, atau Berakhir berdasarkan periode yang disetujui dan tanggal hari ini.
6. Untuk perpanjangan berikutnya, pilih **Siapkan Perpanjangan**. Awal periode diusulkan sehari setelah kontrak terakhir berakhir, dan akhir periode satu tahun kemudian dikurangi satu hari. Nomor PKWT baru tetap diperiksa dan diisi admin.

Pengingat H-60/H-30/H-7 tampil pada daftar, dan pekerjaan kontrak jatuh tempo, berakhir tanpa penerus, serta kontrak lama yang perlu verifikasi tampil di dashboard. Pengingat diperhitungkan saat halaman dibuka, sehingga tidak memerlukan scheduler atau pengiriman pesan eksternal. Kontrak yang sudah memiliki penerus disetujui tidak dihitung kembali sebagai pengingat perpanjangan.

## Pemetaan data

- Pihak pertama: PT Unilogistik Makmur Abadi; perusahaan penempatan: Nuctech Company Limited.
- Upah kontrak menggunakan `employees.basic_salary` secara langsung.
- Kontrak pertama menggunakan `employees.pkwt_date` sebagai tanggal dokumen dan usulan tanggal mulai. Tanggal periode dapat diperiksa admin. Hari dan nama bulan diisi otomatis dalam Bahasa Indonesia.
- Alamat karyawan sementara menggunakan `branch_address`, dengan `branch_name` sebagai pengganti jika alamat belum terisi.
- Identitas, penempatan, upah dan perwakilan disimpan sebagai snapshot pada kontrak. Perubahan profil berikutnya tidak mengubah kontrak yang sudah disetujui.
- Riwayat kontrak memiliki tanggal/nomor tersendiri. Kolom PKWT lama pada profil dan `join_date` tidak ditimpa oleh modul ini; daftar kontrak merupakan sumber periode perpanjangan dan pengingat.

## Kontrak lama

Migrasi membuat satu catatan **Perlu Verifikasi** untuk karyawan yang telah memiliki nomor atau tanggal PKWT. Tanggal akhir tidak ditebak. Nomor asli dipertahankan pada snapshot; admin memverifikasi nomor unik dan tanggal periode.

Setelah draft lama diverifikasi, admin dapat langsung mengunggah PDF asli bertanda tangan tanpa menerbitkan ulang dokumen lama. Pencatatan respons dan konfirmasi admin tetap diperlukan. Dokumen lampau dapat dikonfirmasi sebagai arsip dan akan ditampilkan Berakhir sesuai tanggalnya.

## Template dan penyimpanan

Template bawaan `resources/contracts/pkwt-default-v1.docx` dibuat dari salinan kontrak resmi, mempertahankan 14 pasal, penomoran, format dan lampiran. Nama, NIK, alamat, nomor HP, nominal, tanggal, jabatan dan nomor kontrak contoh telah diganti placeholder. File asli tidak diubah dan tanda tangan tidak disalin otomatis.

Tambahkan perubahan template sebagai versi baru melalui **Template Dokumen**. Unduh template yang sudah tersedia untuk memulai; placeholder wajib ditampilkan pada halaman. Template diperiksa sebagai DOCX, dibatasi ukuran, dan tidak menerima objek tertanam, macro atau referensi eksternal. Hash template diperiksa sebelum pembuatan dokumen. Jangan mengubah berkas versi bawaan yang sudah dipakai: unggah versi berikutnya melalui aplikasi.

Dokumen dan template unggahan tersimpan pada disk `local` di `storage/app/private`. Semua unduhan melewati autentikasi dan pembatasan role. Setiap hasil pembuatan/unggahan memakai path unik dan dicatat pada aktivitas, termasuk versi lama yang masih dapat diunduh. Backup database dan seluruh `storage/app/private` sebagai satu kesatuan. Karyawan, branch dan lokasi yang terkait arsip kontrak dilindungi dari penghapusan.

## Konversi dan pratinjau PDF

Host deployment memerlukan PHP Zip/XML serta LibreOffice. Atur `CONTRACT_SOFFICE_BINARY` ke executable LibreOffice; `soffice` merupakan default. Gunakan font yang sesuai template pada server. Konfigurasi lokal sudah diarahkan ke runtime LibreOffice yang tersedia.

Konversi memakai profil LibreOffice terpisah per proses dan batas waktu 25 detik. Jika konversi gagal, Word tetap disimpan dan halaman memberi pesan untuk memperbaiki konfigurasi lalu mencoba ulang. Pembuatan dokumen tidak mengaktifkan kontrak.

Pratinjau memakai PDF.js lokal dan merender halaman saat mendekati area baca, tanpa bergantung pada plugin PDF browser. File tetap dapat diunduh jika pratinjau gagal. Tidak ada layanan konversi pihak ketiga atau pengiriman dokumen keluar aplikasi.

## Verifikasi implementasi

- Backend: 81 tes lulus, termasuk 7 tes fitur kontrak yang menguji akses empat role terlarang, kedua role HR, snapshot nominal/alamat, draft ganda, periode bertumpuk, unggah, persetujuan, penguncian, arsip versi, template, impor kontrak lama, pengingat penerus, dan perlindungan penghapusan.
- Frontend: 11 tes lulus. Build produksi dan kompilasi Blade berhasil.
- Browser Chromium dengan SQLite dan karyawan sintetis terisolasi: alur draft, konversi Word/PDF, unggah PDF uji, pencatatan respons, aktivasi, dan usulan periode tahun berikutnya berhasil. Tidak ada kontrak karyawan operasional yang diaktifkan selama QA.
- Empat halaman diuji pada lebar 320, 375, 768, 1024, 1440, 1920, 3840 px. Pengukuran tersimpan di `responsive-qa.json`.
- Sampel dokumen hasil generate dirender dan diperiksa seluruh 11 halamannya. Pratinjau PDF.js mendeteksi 11 halaman dan berhasil merender halaman saat digulir tanpa error konsol pada tab uji baru.
- Belum diuji pada perangkat fisik/Safari atau beban konversi produksi besar. Peringatan build PDF.js lama berasal dari dependency yang sudah digunakan aplikasi; evaluasi PDF dinonaktifkan pada runtime.
