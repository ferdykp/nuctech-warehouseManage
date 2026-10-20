# Daftar perpanjangan bulanan dan ekspor Word

Menu **Kontrak Karyawan** sekarang membuka daftar perpanjangan untuk bulan/tahun berjalan. Kartu di bagian atas menampilkan jumlah karyawan, total gaji pokok dan enam nama pertama. Seluruh nama tersedia pada tabel berhalaman di bawah. Area kartu dapat digulir pada layar kecil.

## Filter

- Bulan awal dan bulan akhir dalam tahun yang sama; untuk setahun pilih Januari–Desember.
- Tahun, branch, site machine, position, nama atau nomor PKWT.
- Tindak lanjut: masih perlu diperpanjang (default), perpanjangan disetujui, atau semua.
- Filter tambahan: status kontrak, keaktifan dan status kepegawaian.

Pilihan site machine mengikuti branch. Server juga menolak pasangan site/branch yang tidak cocok. Klik **Terapkan Filter** untuk memperbarui kartu, tabel dan tautan ekspor. **Bulan Ini / Reset** mengembalikan filter awal.

## Acuan pemilihan karyawan

Kontrak yang telah disetujui dengan periode terverifikasi menggunakan tanggal akhir kontrak. Jika periode terverifikasi tersedia, tanggal PKWT lama tidak dipakai lagi sebagai pengganti.

Karyawan tanpa periode terverifikasi menggunakan ulang tahun `employees.pkwt_date` dalam tahun terpilih, setelah minimal satu tahun. Data tersebut diberi label **Perlu Verifikasi** dan tanda bintang pada nama dalam dokumen. Ulang tahun 29 Februari menggunakan 28 Februari pada tahun nonkabisat.

Setiap karyawan tampil satu kali dalam rentang bulan terpilih. Kontrak dengan penerus disetujui atau keputusan tidak diperpanjang dikeluarkan dari daftar tindak lanjut default; pilih Semua untuk meninjaunya. Draft atau dokumen perpanjangan yang sedang diproses tetap dapat dibuka dari kartu.

Seluruh kontrak dan draft, termasuk yang tidak berada pada bulan terpilih atau belum mencapai satu tahun, tersedia melalui **Arsip Semua Kontrak**. Riwayat dari detail karyawan dan pengingat dashboard diarahkan ke arsip agar tidak terpotong filter bulan.

## Ekspor

Pilih **Ekspor Daftar Word**, periksa jumlah, total, daftar nama dan data yang belum lengkap, lalu **Unduh Word (.docx)**. Semua hasil filter diekspor, bukan hanya 15 baris pada halaman tabel saat ini.

Kolom mengikuti dokumen contoh: No., Project Team Name, Name, Bank, Account No., Amount. Project Team Name menggunakan `employees.position`; bank dan rekening menggunakan profil karyawan; Amount menggunakan `employees.basic_salary` saat ekspor. Rekening disimpan sebagai teks, dan total dihitung dengan pembulatan sen. Nilai desimal ditampilkan konsisten pada layar dan dokumen.

Data position/bank/rekening/gaji yang kosong ditandai pada halaman pemeriksaan. Pengguna dapat melengkapi profil, atau secara eksplisit mengonfirmasi ekspor data belum lengkap yang ditampilkan sebagai tanda pisah. Ekspor kosong tidak menghasilkan dokumen.

Template aplikasi berada di `resources/contracts/renewal-list-v1.docx`, berupa salinan yang telah mengganti data contoh menjadi placeholder dan satu baris tabel yang dapat diulang. Judul, format kolom, total dan dua lampiran gambar dari contoh dipertahankan. Kalimat pengantar dan catatan bintang disesuaikan untuk daftar perpanjangan; nominal tetap berdasarkan gaji pokok sesuai kebutuhan pengguna. Header tabel diulang dan setiap baris dijaga agar tidak terpotong antarhalaman. File asli tidak diubah.

Unduhan hanya tersedia untuk Superadmin dan Administration. File hasil ekspor dibuat sementara, dikirim dengan header privat, lalu dibersihkan setelah pengiriman. Ekspor tidak mengaktifkan kontrak, mengubah gaji, atau memutuskan perpanjangan karyawan.

## Pemeriksaan

- Enam tes fitur baru mencakup ulang tahun satu tahun, periode/rentang bulan, acuan terverifikasi, penerus disetujui, filter gabungan, deduplikasi, rekening berawalan nol, karakter XML, total, akses role, validasi dan data kosong, ekspor seluruh pagination, serta 29 Februari.
- Suite backend lengkap: 87 tes lulus. Frontend: 11 tes lulus. Build produksi, kompilasi Blade dan pemeriksaan whitespace berhasil.
- Browser dengan database SQLite dan data sintetis: filter September–Oktober 2026 + branch/site + Software Engineer menghasilkan 7 karyawan dan total Rp42.700.000. Halaman pemeriksaan dan file Word yang diunduh memiliki tujuh baris, position yang sama dan total yang sama.
- Kartu, pemeriksaan ekspor dan arsip diuji pada lebar 320, 375, 768, 1024, 1440, 1920 dan 3840 px; 21 pengukuran tidak menemukan overflow halaman. Rincian pada `renewal-responsive-qa.json`.
- Dokumen contoh 14 dan 38 baris dirender. Semua halaman versi akhir 38 baris, termasuk dua lampiran gambar asli, diperiksa. Data uji terisolasi; data karyawan operasional tidak diubah.
- Tes visibilitas menu lama diselaraskan dengan sidebar yang sudah memberikan menu Karyawan kepada Administration; hak akses aplikasi tidak diubah oleh penyesuaian tes ini.

Belum diuji pada perangkat fisik/Safari atau ekspor dengan jumlah karyawan produksi sangat besar.
