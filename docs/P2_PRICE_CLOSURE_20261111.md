# Rekonsiliasi harga P2 — 11 November 2026

**Keputusan P2 terkini mengikuti [penutupan struktur dan sistem](P2_SYSTEM_CLOSURE_20261111.md).** Pengguna memisahkan migrasi data dari gate P2; laporan harga ini dipertahankan sebagai riwayat pekerjaan data.

Hasil lanjutan tersedia pada [laporan penelusuran asal batch](P2_BATCH_ORIGIN_20261111.md): enam batch tambahan diselesaikan dan sisa saat ini 72 barang. Angka 77 barang di bawah merupakan baseline sebelum penelusuran lanjutan tersebut.

**P2 belum dapat ditutup penuh.** Dari 89 barang yang diperiksa, harga 12 barang berhasil dipastikan. Masih ada 77 barang pada 89 batch aktif, sejumlah 2.404,75 unit, yang memerlukan bukti harga atau asal batch. Seluruh pekerjaan diterapkan hanya pada `kiucoid_kiupo_merge_p0_20261110`.

## Harga yang diterapkan

Semua harga berikut berasal dari detail pengajuan pembelian yang tercatat sama di PO Baru dan POERP lokal. Dasar harga disimpan sebagai `PENGAJUAN`. Catatan mutasi secara eksplisit menyebut PO/Request PIC; kode barang, kode sistem, dan satuan cocok. Untuk QGAN002, pengguna mengonfirmasi pada 11 November 2026 bahwa Rp4.500 adalah harga satu gantungan.

| Barang | Batch | PO sumber | Detail | Harga per satuan | Sisa batch |
| --- | ---: | --- | ---: | ---: | ---: |
| PONK1802260002 — Double Tape Sedang | 4477 | NKPO1802260005 | 1390 | Rp5.500 | 1 |
| QCIT001 | 3329 | NKPO1103250002 | 325 | Rp1.305 | 1 |
| QDET001 | 3319 | NKPO2802250002 | 301 | Rp113.850 | 1 |
| QGAN002 | 4488 | NKPO1402260006 | 1380 | Rp4.500 | 1 |
| QHEA001 | 4231 | NKPO1312250150 | 1150 | Rp96.834 | 1 |
| QISI008 | 3393 | NKPO1504250018 | 401 | Rp849 | 3 |
| QKRE005 | 3351 | NKPO2003250001 | 354 | Rp17.127 | 1 |
| QLAM012 | 4554 | NKPO2002260001 | 1400 | Rp2.345 | 2 |
| QPAS001 | 3357 | NKPO2403250010 | 362 | Rp41.100 | 1 |
| QSPI006 | 4202 | NKPO0612250003 | 1130 | Rp17.000 | 1 |
| QSPI010 | 4203 | NKPO0612250003 | 1132 | Rp10.950 | 1 |
| QSUP002 | 3326 | NKPO2502250003 | 289 | Rp13.702 | 2 |

Total 12 batch, 16 unit sisa. Harga dan nilai 51 alokasi historis yang memakai batch tersebut ikut disinkronkan; jumlah alokasi dan kuantitasnya tetap. Dua belas log harga ditambahkan dan 12 issue harga terkait diselesaikan. Jenis sumber tetap `ADJUSTMENT`, sedangkan detail PO sumber ditautkan untuk penelusuran bukti.

## Sisa yang belum dapat diberi harga

Kategori berikut menghitung batch; satu barang dapat memiliki beberapa batch dan kategori.

| Penyebab | Batch | Sisa unit | Bukti yang diperlukan |
| --- | ---: | ---: | --- |
| Saldo awal tanpa harga perolehan batch | 29 | 420,75 | Rincian penilaian saldo awal dengan satuan dan harga perolehan |
| Tidak ada detail pembelian berharga dengan kode barang dan sistem yang sama | 33 | 903 | Dokumen harga perolehan yang mengikat barang/batch tersebut |
| Ada harga pembelian barang yang sama, tetapi asal penyesuaian tidak terhubung ke PO tertentu | 26 | 1.080 | Rujukan PO/retur/perpindahan yang membuktikan asal batch dan satuannya |
| QVIT001: pengembalian campuran dan jumlah tidak konsisten | 1 | 1 | Rincian asal dan harga tube yang dikembalikan |

Untuk QVIT001, pengeluaran 11384 sebanyak 3 tube dialokasikan dari 2 tube @Rp29.950 (penerimaan 11381, NKPO2507260144) dan 1 tube @Rp25.155 (penerimaan 5120, NKPO1408250057). Catatan pengembalian yang sama muncul pada koreksi keluar 11423 sebanyak 2 tube dan koreksi masuk 11424 sebanyak 4 tube. Data ini belum menentukan harga setiap tube dalam batch pengembalian. Kuantitas dan harga yang belum terbukti dipertahankan.

[Daftar 77 barang dan setiap batch yang belum selesai](../database/reconciliation/p2_price_20261111/unresolved.json) mencantumkan kode, nama, jumlah, mutasi sumber, alasan, dan kandidat harga yang **belum diterapkan**. Kandidat tersebut memerlukan bukti hubungan ke batch sebelum dapat dipakai.

## Pengujian dan integritas

- Seluruh 943 saldo mutasi tetap sama dengan sebelum rekonsiliasi harga dan cocok dengan sisa batch; saldo negatif nol. QFIL015 dan QSTE004 tetap nol.
- Semua transaksi keluar 11512/11514 tetap teralokasi penuh; kuantitas awal setiap batch sama dengan sisa ditambah alokasi.
- Uji model aplikasi dalam transaksi yang dibatalkan: pengeluaran QHEA001 berhasil memakai batch 4231 dengan snapshot Rp96.834; pengeluaran QCDR001 yang hanya memiliki batch tanpa harga ditolak. Mutasi uji dibatalkan dan tidak tersisa.
- Pemanggilan ulang runner hanya memverifikasi hasil dan tidak mengubah data lagi.
- Seluruh transaksi, dokumen PO/Request PIC, batch di luar 12 pilihan, alokasi di luar batch pilihan, serta log harga lama identik dengan baseline. Perubahan alokasi terpilih hanya pada snapshot harga dan nilai.
- Verifikasi P2 sebelumnya tetap lulus: 21 mutasi impor sebelumnya terjaga, nol status tertahan, 50 detail yatim tetap diarsipkan, dan satu fitting NPONK1709260004 masih menunggu penyerahan sesuai konfirmasi pengguna.
- Checksum 95 tabel dasar PO Baru dan 44 tabel dasar POERP identik dengan baseline pekerjaan harga. Kedua database asli tidak diubah.

Uji HTTP lengkap pada pekerjaan stok sebelumnya tetap menjadi bukti alur P2; pekerjaan harga ini menguji alokasi model yang terkena perubahan. Rebuild histori tidak dijalankan sebagai perubahan permanen dalam pekerjaan harga ini.

## Audit

Database uji menyimpan `p2_price_closure_review` (101 batch dengan bukti dan SHA-256), `p2_price_reconcile_run`, serta empat tabel snapshot `p2_price_backup_*`. Status `COMPLETE` pada tabel run berarti pemeriksaan dan penerapan harga terbukti selesai; P2 penuh masih menunggu bukti harga 77 barang.

Artefak tetap berada di [database/reconciliation/p2_price_20261111](../database/reconciliation/p2_price_20261111/): manifest bukti, runner CLI khusus database uji, daftar pengecualian, dan hasil verifikasi. Snapshot dan skrip pemeriksaan tambahan berada di `/tmp/kiupo-p2-price.iScmhM/` selama direktori sementara tersebut masih tersedia.
