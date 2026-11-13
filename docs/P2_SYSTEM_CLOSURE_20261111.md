# Penutupan P2: struktur dan sistem — 11 November 2026

Pembaruan berikutnya: [P3 PO Jasa telah diuji](P3_SYSTEM_CLOSURE_20261111.md), termasuk penambahan `jenis_detail` dan integrasi penerimaan dengan LIFO. Catatan PO Jasa di bagian batas keputusan di bawah merupakan keadaan saat penutupan P2; regresi P2 setelah perubahan P3 tetap lulus.

**Keputusan: P2 dapat ditutup untuk struktur dan alur sistem PO nonkomersil, Request PIC, approval, penerimaan, pickup, dan mutasi stok LIFO yang diminta.**

Pengguna menetapkan bahwa penggabungan pada tahap ini mencakup struktur dan sistem. Penggabungan, pembersihan, dan kelengkapan data historis ditangani pada pekerjaan migrasi berikutnya berdasarkan sistem gabungan. Keputusan ini menggantikan penilaian sebelumnya yang memakai kelengkapan harga historis sebagai syarat penutupan P2.

## Hasil pemeriksaan

| Gate P2 | Hasil |
| --- | --- |
| Struktur tabel/kolom yang dipakai alur P2 | PASS |
| Unique guard kode Request PIC, penerimaan batch, dan alokasi keluar | PASS |
| Tabel transaksi InnoDB dan kapasitas status Request PIC | PASS |
| Baseline struktur kosong: 96 tabel + 1 view, nol data historis | PASS |
| Pemanggilan ulang SQL fondasi dan baseline struktur | PASS |
| View/relasi pada struktur kosong tanpa ketergantungan ke database asli | PASS |
| Pengajuan Request PIC dan pembatasan departemen/Kadep | PASS |
| PO nonkomersil: Kadep, Direktur, proses pembelian | PASS |
| Penerimaan per detail, pencatatan batch/harga, penolakan pengulangan | PASS |
| Konfirmasi PIC tanpa menggandakan penerimaan | PASS |
| Approval pickup, pembatasan peran, mutasi keluar dan penolakan pengulangan | PASS |
| Kegagalan alokasi pada detail berikutnya membatalkan seluruh pickup | PASS |
| Pengambilan stok tersedia tanpa membuat PO baru | PASS |
| Penyerahan parsial hanya memproses detail yang tersisa | PASS |
| Halaman stok, histori batch dan tabel AJAX | PASS |
| Adjustment masuk/keluar, peran pengelola stok, harga wajib, snapshot LIFO | PASS |

Uji HTTP memakai barang, pengguna, PO, Request PIC, harga, dan mutasi **sintetis** di `kiucoid_kiupo_merge_p0_20261110`. Semua fixture dibersihkan. Struktur kosong diuji dalam database lokal sementara yang dibuat khusus, kemudian dihapus. Isi seluruh tabel yang telah ada di database uji tetap identik dengan baseline sebelum pengujian.

## Perbaikan struktur dan sistem

1. SQL fondasi LIFO kini membuat `tbpo_stock_lifo_batch_nk`, sesuai nama yang dipakai model aktif. Versi sebelumnya membuat `tbpo_stock_lifo_batch` yang tidak dibaca model.
2. Bagian P2 pada pemeriksaan struktur lama diselaraskan dengan kolom aktual: timestamp pengajuan/pickup dan pilihan harga realisasi. Pemeriksaan P2 khusus tersedia pada `database/sql/p2_system_preflight_20261111.sql` dan tidak memeriksa kelengkapan data lama.
3. Endpoint adjustment stok kini memakai pembatasan pengelola yang sama dengan pengelolaan harga LIFO. Uji membuktikan PIC ditolak dengan HTTP 403 dan petugas berwenang dapat melakukan adjustment.
4. Baseline struktur gabungan tersedia pada `database/schema/p2_merged_structure_20261111.sql`. File memuat DDL saja, tanpa data, tanpa nilai counter AUTO_INCREMENT historis, dan tanpa pengikatan view ke database sumber. File ini untuk membangun database kosong, bukan migrasi ALTER untuk database yang sudah berisi.

Tidak ada rekonsiliasi atau impor data historis tambahan dalam pekerjaan penutupan ini. Checksum seluruh **95 tabel dasar PO Baru** dan **44 tabel dasar POERP** identik dengan baseline. Kedua database asli tidak diubah.

## Batas keputusan

Sisa 72 barang/83 batch `PERLU_HARGA`, retur QVIT001, serta penyesuaian data historis lainnya adalah backlog migrasi data. Data dan jejak audit yang sudah ada dipertahankan. Kelengkapannya tidak menjadi penghambat penutupan P2 struktur/sistem berdasarkan arahan terbaru pengguna. Sistem tetap memvalidasi harga untuk transaksi baru sebagaimana diuji dengan fixture.

Penutupan ini menyatakan alur P2 di atas lulus pada lingkungan uji lokal. PO Jasa, stock opname, serta hapus/pulihkan histori stok tidak dinyatakan lulus oleh pengujian ini. Pemeriksaan lama yang juga mencakup PO Jasa masih menandai `tbpo_jasa_request_detail.jenis_detail`; pemeriksaan modul tersebut berada di luar gate P2 yang ditetapkan di sini.

## Bukti yang disimpan

- [Hasil verifikasi dan log HTTP](../database/verification/p2_system_20261111/README.md).
- [Kontrak struktur P2](../database/sql/p2_system_preflight_20261111.sql).
- [Baseline struktur tanpa data](../database/schema/p2_merged_structure_20261111.sql).

Hasil penelusuran data sebelumnya tetap disimpan sebagai riwayat/backlog migrasi; kesimpulan penutupan P2 terkini mengikuti laporan ini.
