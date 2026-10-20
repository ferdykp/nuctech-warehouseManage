# Koreksi histori gaji

Superadmin dan administrasi dapat membuka **Detail Karyawan → Histori Perubahan Gaji → Koreksi / Batalkan**.

- **Koreksi** memperbaiki nominal dan keterangan pada catatan yang sama. Alasan koreksi wajib diisi. Jika catatan terakhir dikoreksi, gaji pokok ikut diselaraskan. Koreksi catatan lama tetap mempertahankan perubahan gaji berikutnya dan menyelaraskan nominal awal catatan penerus.
- **Batalkan** menandai catatan sebagai dibatalkan, dengan alasan wajib dan konfirmasi nominal hasil. Jika catatan terakhir dibatalkan, gaji pokok kembali mengikuti catatan aktif sebelumnya. Jika hanya ada satu catatan, gaji kembali ke nilai gaji lama pada catatan tersebut (bisa Rp0; nominal selalu diperlihatkan sebelum pembatalan). Catatan batal tidak dipakai untuk menentukan gaji historis.
- **Ubah Karyawan → Jenis Perubahan Gaji** membedakan perubahan sebenarnya (histori baru) dengan koreksi salah input (memperbaiki catatan aktif terbaru). Jika belum ada histori, koreksi membuat catatan awal dengan jejak nilai profil sebelumnya.
- Jejak koreksi mencatat aktor, waktu, alasan, serta seluruh rantai nominal sebelum dan sesudah. Nominal masukan mengikuti format Indonesia.
- Data diperbarui dalam transaksi dengan kunci per karyawan. Halaman lama yang nominalnya sudah berubah ditolak melalui pemeriksaan versi.
- Payroll yang telah tersimpan, nominal tunjangan, dan arsip dokumen kontrak tetap dipertahankan. Koreksi bisa mengubah hasil pembuatan payroll baru untuk periode lama, karena payroll baru membaca histori aktif yang telah dikoreksi.

Tidak tersedia reset massal atau hapus permanen dari fitur ini. Catatan batal menampilkan **Lihat Jejak** dan tidak dapat dikoreksi ulang.

Migrasi: `2026_10_21_000002_add_salary_history_corrections.php` menambahkan `cancelled_at` dan tabel `salary_history_audits`.
