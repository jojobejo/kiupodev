# Database db_kiupobaru — 12 November 2026

Database aktif proyek sekarang `db_kiupobaru` melalui `application/config/database.php`.
Sumber salinan: `kiucoid_karismaerp_local`; database sumber tidak diubah.
Backup lengkap sebelum pembersihan (akses lokal privat): `/Users/itkarisma/.codex/backups/kiupobaru/20261112/source_before_cleanup_152310.sql`.

- Dari 343 tabel + 1 view sumber, 248 tabel tanpa referensi modul dihapus pada target.
- 95 tabel berisi data modul dan 1 view dipertahankan; 3 tabel pendukung yang belum tersedia ditambahkan.
- Hasil akhir: **98 tabel + 1 view**. Seluruh jumlah baris pada 95 tabel salinan cocok dengan sumber; 88 tabel dengan kolom tidak berubah juga cocok checksum-nya.
- Cakupan: autentikasi/SSO, pengguna, pemasok, master barang, PO komersil, PO nonkomersil, Request PIC, PO jasa, stok/LIFO/opname, laporan, pengaturan, review aplikasi dan audit admin.
- Tabel dihapus mencakup modul ERP keuangan/HRD/penjualan/distribusi/KPI serta tabel lama tanpa pemakaian kode, termasuk `tbpo_akun_tr`, `tbpo_barang_akun`, `tbpo_barang_packaging`, `tbpo_ratings`, `tbq_nilaim`. Nama `users` di kode adalah variabel, bukan tabel untuk autentikasi; autentikasi memakai `tbpo_user`.
- Struktur tambahan: audit pembayaran/evaluasi jasa, draft Shipment To, upgrade struktur P3–P5 dan identitas pemasok PO. Semua perubahan hanya pada database target. Nilai historis tetap disalin apa adanya.
- View stok dibuat ulang menggunakan tabel lokal target; foreign key dan view tidak bergantung pada database sumber.
- Verifikasi: PHP lint konfigurasi, HTTP login 200 dan API barang 200 dengan status sukses; preflight P3/P4/P5 lulus.
- Preflight P2/P6 masih melaporkan indeks unik `tbpo_req_nk.kd_po_nk` yang belum tersedia. Ada **48 kelompok nomor Request PIC duplikat** dari sumber. Tidak ada penggabungan atau penghapusan data historis secara otomatis. Ini bukan tabel hilang akibat pembersihan.

Daftar tabel dihapus, dipertahankan, referensi kode, jumlah baris, dan hasil pemeriksaan ada di `results.json`. `cleanup_applied.sql` merupakan catatan SQL yang sudah dijalankan, bukan skrip untuk diulang. Pengujian ini belum mencakup seluruh alur transaksi melalui browser.
