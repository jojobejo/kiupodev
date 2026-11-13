# Hasil rekonsiliasi P2, 11 November 2026

**Keputusan P2 terkini mengikuti [penutupan struktur dan sistem](P2_SYSTEM_CLOSURE_20261111.md).** Kelengkapan data historis ditangani dalam pekerjaan migrasi terpisah sesuai arahan terbaru pengguna.

Pembaruan harga berikutnya tersedia pada [laporan rekonsiliasi harga P2](P2_PRICE_CLOSURE_20261111.md): 12 barang sudah diberi harga berbukti; sisa 77 barang masih memerlukan bukti sebelum penutupan penuh. Angka 89 barang di bawah merupakan hasil sebelum pekerjaan harga tersebut.

Database yang diubah: `kiucoid_kiupo_merge_p0_20261110` saja.

Rekonsiliasi kuantitas 111 barang selesai. P2 belum dinyatakan tutup penuh karena 89 barang masih memiliki stok batch tanpa harga; pengeluaran yang membutuhkan batch tersebut tertahan oleh validasi harga aplikasi.

## Hasil kuantitas

- Seluruh 111 baris `p2_stock_closure_review` berstatus `SELESAI - MUTASI DAN BATCH COCOK`, selisih nol.
- Seluruh 943 barang: saldo mutasi sama dengan jumlah sisa batch, saldo negatif nol.
- Sebanyak 208 kekurangan alokasi historis, sejumlah 1.460,25 unit, ditautkan ke sisa batch penerimaan/penyesuaian berikutnya yang tercatat untuk barang yang sama. Ini pelunasan kekurangan historis, dengan jejak alokasi; tidak membuat penerimaan atau harga pengganti.
- Semua mutasi keluar 11512/11514 teralokasi penuh. Setiap batch memenuhi jumlah awal = jumlah tersisa + jumlah dialokasikan, tanpa sisa negatif.
- Saldo total setelah koreksi 7.312,75 unit, naik bersih satu unit dari baseline.

## Tiga mutasi koreksi dari POERP lokal

| ID sumber dan tujuan | Barang | Akun | Kuantitas | Hasil |
| --- | --- | --- | --- | --- |
| 12354 | QFIL015 | 11513 | +1 | Saldo -1 menjadi 0 |
| 12353 | QSTE004 | 11513 | +1 | Saldo -1 menjadi 0 |
| 12542 | QSTE014 | 11514 | -1 | Saldo 1 menjadi 0 |

QSTE004 adalah Stella Matic. Mutasi lama 10269 memakai kode ini untuk stempel/tagging ban dengan sistem `PONK2811250001`. Stempel yang sesuai adalah QSTE014, diterima melalui transaksi 10268, detail PO 1703, `NKPO0705260033`. Dua koreksi sumber 12353 dan 12542 membenahi dampak saldo tanpa mengedit mutasi lama. Ketiga mutasi diimpor identik seluruh kolom dengan sumber.

## Validasi

- Uji HTTP lulus: pengajuan Request PIC, pembatasan departemen dan approval Kadep, pembentukan PO nonkomersil, approval Kadep/Direktur, penerimaan per detail dan batch, konfirmasi PIC tanpa mutasi ganda, persetujuan pickup, rollback pengeluaran parsial, penolakan pengulangan, jalur stok tersedia, serta penyerahan sisa item saja. Fixture dibersihkan.
- Rebuild model diuji dalam transaksi yang dibatalkan: seluruh 943 saldo kembali cocok. Checksum lima tabel stok/LIFO sebelum dan sesudah rollback identik.
- Pemanggilan ulang rekonsiliasi tidak membuat perubahan lagi.
- Verifikasi lanjutan mempertahankan 21 mutasi rekonsiliasi sebelumnya, 50 detail yatim dalam arsip, nol status tertahan, serta satu fitting `NPONK1709260004` untuk Lampu Gantung Gudang A3 tetap menunggu penyerahan sesuai konfirmasi pengguna.
- Seluruh transaksi dan alokasi yang sudah ada sebelum pekerjaan ini identik. Identitas dan harga batch lama serta log harga tetap utuh.
- Checksum seluruh 95 tabel dasar `kiucoid_kiupo_baru` dan 44 tabel dasar `kiucoid_po` identik dengan baseline pekerjaan ini. Kedua database asli tidak diubah.

## Sisa sebelum penutupan penuh

Sebanyak 101 batch aktif pada 89 barang, sejumlah 2.420,75 unit, masih `PERLU_HARGA`. Harga yang belum diketahui tetap kosong. Model hanya mengizinkan pengeluaran dari batch berharga valid, sehingga stok fisik yang tersedia belum selalu dapat dikeluarkan. Penyelesaian atau keputusan ruang lingkup terpisah untuk harga historis diperlukan sebelum menyatakan P2 siap operasional penuh.

## Audit dan pemulihan di database uji

- `p2_closure_backup_*`: snapshot enam tabel sebelum rekonsiliasi.
- `p2_closure_stock_import`: tiga mutasi sumber dan payload beserta SHA-256.
- `p2_closure_allocation_added`: 211 alokasi baru, termasuk koreksi QSTE014.
- `p2_closure_run`: hasil COMPLETE untuk rekonsiliasi 111 barang; ini status pekerjaan kuantitas, bukan persetujuan penutupan P2 penuh.
- Bukti lokal: `/tmp/kiupo-p2-stock-closure.pIVqFD/`, termasuk baseline checksum, snapshot TSV, log regresi, dan `final-verification.json`. Direktori sementara dapat dibersihkan sistem; tabel audit database uji dan laporan ini menjadi rujukan tetap.

Model `application/models/stock/M_StockLifo.php` kini menyelesaikan kekurangan historis terhadap penerimaan tercatat setelah replay rebuild, serta menormalisasi tanggal tanpa komponen jam saat ini.
