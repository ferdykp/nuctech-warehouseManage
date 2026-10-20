# Tunjangan bulanan

Kelola melalui **Karyawan → Ubah → Tunjangan Bulanan** (superadmin dan administration).
Simpan nama, nominal, bulan mulai, bulan terakhir dibayar opsional, keterangan dan sertifikat opsional (PDF/JPG/PNG maksimal 5 MB, penyimpanan privat).
Bulan mulai dan bulan terakhir sama-sama termasuk periode pembayaran. Tanpa bulan terakhir, tunjangan berulang terus. Satu karyawan dapat memiliki beberapa tunjangan.

Payroll baru, termasuk Tambah Manual, otomatis menyimpan salinan rincian tunjangan yang berlaku. Tambah Manual memakai bulan saat ini; Buat Penggajian memakai periode yang dipilih. Tunjangan dibayar penuh per bulan, tanpa prorata, dan tidak menambah dasar tarif lembur. Gaji pokok dan dokumen kontrak/remuneration kontrak tidak berubah.

Payroll yang telah ada (termasuk sebelum fitur ini) tidak mendapat tambahan secara retroaktif melalui regenerasi. Untuk penyesuaian yang disengaja, buka **Ubah Data Gaji**, centang **Perbarui tunjangan**, dan simpan. Jika karyawan payroll diganti, rincian diambil dari karyawan pengganti untuk bulan payroll tersebut.

Gunakan **Bulan terakhir dibayar → Simpan Masa Berlaku** untuk menghentikan tunjangan setelah bulan tersebut. Bulan terakhir minimal bulan berjalan dan tidak sebelum bulan mulai. Untuk perubahan nominal, akhiri komponen lama dan tambahkan komponen baru mulai bulan berikutnya agar tidak tumpang tindih. Riwayat tetap tersimpan. Sistem payroll saat ini belum mempunyai status finalisasi/penguncian.

Tabel dan modal Salary memperlihatkan rincian gaji pokok, tunjangan dan total. Ekspor salary Excel kolom Amount berisi gaji pokok + tunjangan; tiga kolom tambahan menampilkan gaji pokok, total tunjangan dan rinciannya. Penyesuaian lembur tetap mengikuti kolom Before/After yang sudah ada. Tidak ada perubahan aturan potongan atau penambahan dokumen slip terpisah.

Validasi: 90 tes backend (956 assertions), 11 tes frontend, build Vite berhasil. QA browser terpisah menggunakan data sintetis; tambah tunjangan, pembaruan payroll eksplisit, rincian modal, serta lebar panel/form/modal 320 px diperiksa.
