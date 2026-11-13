# Penelusuran asal batch P2 — 11 November 2026

**Arahan terbaru:** penutupan P2 kini menilai struktur dan sistem; migrasi data ditangani terpisah. [P2 dinyatakan dapat ditutup dalam cakupan tersebut](P2_SYSTEM_CLOSURE_20261111.md). Kesimpulan tertahan oleh data di bawah merupakan penilaian sebelum perubahan ruang lingkup pengguna.

**P2 belum dapat ditutup penuh.** Pemeriksaan 77 barang/89 batch menghasilkan enam batch tambahan berharga terbukti. Kini tersisa **72 barang, 83 batch, 1.449,75 unit** berstatus `PERLU_HARGA`. QVIT001 dipertahankan tanpa harga sesuai jawaban pengguna **“tetap”**.

Semua perubahan dilakukan pada `kiucoid_kiupo_merge_p0_20261110`. Checksum 95 tabel dasar `kiucoid_kiupo_baru` dan 44 tabel dasar `kiucoid_po` tetap identik dengan baseline.

## Enam rantai asal yang diselesaikan

Harga diperoleh dari detail pengajuan PO pada kedua database lokal, dengan dasar `PENGAJUAN`. Rantai di bawah dicocokkan terhadap identitas barang, kode sistem, satuan, kuantitas, tanggal, catatan transaksi, dan alokasi pengeluaran yang sudah tersimpan di PO Baru. Harga ditetapkan melalui rekonstruksi rantai pengembalian/penyimpanan yang terdokumentasi.

| Barang / batch | Penerimaan → pengeluaran → koreksi masuk | PO / detail | Harga per satuan historis | Sisa batch |
| --- | --- | --- | ---: | ---: |
| QISO006 / 3184 | 1836 → 2204 → 2205 | NKPO2501250011 / 253 | Rp834/PCS | 1 |
| PONK2501250003 / 3187 | 1846 → 2006 → 2215 | NKPO2501250010 / 252 | Rp15.000/botol | 1 |
| QKAR003 / 3216 | 2264 → 2267 → 2270 | NKPO0102250001 / 265 | Rp20.860/bendel | 1 |
| QFLY001 / 3696 | 4652 → 4658 → 4659 | NKPO1107250030 / 639 | Rp186/lembar | 950 |
| QSEA001 / 4067 | 6551 → 6555 → 6563 | NKPO0511250139 / 1013 | Rp3.500/PCS | 1 |
| QSAB002 / 4191 | 7094 → 7133 → 7134 | NKPO0312250005 / 1120 | Rp12.634/PCS historis | 1 |

- QISO006: koreksi “dobel pengajuan” sebanyak 1 PCS terjadi 21 menit sesudah pengeluaran 1 PCS untuk pengguna/keperluan yang sama. Semua penerimaan sebelum koreksi berasal dari satu PO berharga Rp834. Pengeluaran sebelumnya 1958 terhubung langsung ke Request PIC pada PO tersebut.
- Refill PONK2501250003: catatan “30/1/25” sesuai tanggal penerimaan dan pengeluaran 2 botol untuk penambahan stok. Koreksi mengembalikan 2 botol. Batch lain 4294 bertuliskan “tidak penuh” tetap tanpa harga karena nilainya memerlukan bukti volume sisa.
- QKAR003: 10 bendel dikembalikan 50 detik setelah pengeluaran 10 bendel untuk penambahan stok. Alokasi sumber mengikat seluruh pengeluaran ke penerimaan 2264 berharga Rp20.860/bendel.
- QFLY001: satu penerimaan 2.000 lembar diikuti pengeluaran 2.000 lembar dan penyimpanan sisa 1.500 lembar pada hari yang sama. Riwayat sebelum koreksi tidak mempunyai saldo awal atau sumber tambahan. Sisa batch di database uji saat pekerjaan ini adalah 950 lembar.
- QSEA001: penyimpanan 2 PCS pada hari pengeluaran 3 PCS dari penerimaan 6551. Penerimaan sebelumnya juga berharga Rp3.500, sehingga tidak ada alternatif harga pada rantai tersebut.
- QSAB002: lot lama sudah habis sebelum penerimaan 7094; pengeluaran 3 PCS dan penyimpanan 2 PCS terjadi pada hari yang sama. Satuan pada detail PO dan koreksi sama-sama kode 2/PCS. Pembelian berikutnya memakai kode satuan 10; harga penerimaan berikutnya tidak dipakai untuk batch ini.

Total enam batch mencakup **955 unit sisa**. Harga dan nilai **14 alokasi historis** dari batch tersebut diperbarui. Identitas dan kuantitas alokasi tetap, dan enam log harga serta enam penyelesaian issue ditambahkan. Lima barang menjadi sepenuhnya berharga; refill masih mempunyai satu batch lain tanpa harga. Dua belas batch yang diselesaikan pada tahap harga sebelumnya tetap utuh.

## QVIT001

Kedua sumber lokal mencatat pengeluaran 11384 sebanyak 3 tube dari dua harga: 2 tube @Rp29.950 dari penerimaan 11381/NKPO2507260144 dan 1 tube @Rp25.155 dari penerimaan 5120/NKPO1408250057. Selanjutnya terdapat koreksi keluar 11423 sebanyak 2 tube dan koreksi masuk 11424 sebanyak 4 tube dengan catatan pengembalian yang sama.

Arsip transaksi terhapus tidak memuat koreksi QVIT001 yang menjelaskan pasangan tersebut. Metadata dua bukti pembelian tersedia, tetapi berkasnya tidak ditemukan pada direktori lokal `images/upbukti` aplikasi-aplikasi di htdocs. Bukti pembelian menetapkan dua harga asal, sedangkan komposisi retur tetap belum terbukti. Pengguna memilih mempertahankan status harga. Batch 5177 tetap `PERLU_HARGA`, harga NULL, kuantitas sisa 1 tube; mutasi aslinya tetap.

[Jejak rinci QVIT001](../database/reconciliation/p2_origin_20261111/qvit_trace.json) menyimpan mutasi kedua sumber, detail pembelian, alokasi sumber, metadata berkas, hasil pencarian berkas lokal, dan keputusan pengguna.

## Sisa bukti yang dibutuhkan

| Kategori | Batch | Sisa unit |
| --- | ---: | ---: |
| Saldo awal tanpa rincian harga perolehan | 29 | 420,75 |
| Tidak ada detail pembelian berharga dengan kode barang dan sistem yang sama | 33 | 903 |
| Asal penyesuaian belum terikat ke lot/PO tertentu | 16 | 121 |
| Refill botol tidak penuh | 1 | 1 |
| QACE002: volume 1 liter/200 ml dan lot sisa belum dapat dipastikan | 2 | 2 |
| QPIG001: asal 9 pigura belum terbukti | 1 | 1 |
| QVIT001: tetap tanpa harga sesuai pengguna | 1 | 1 |

QPIG001 memiliki pengeluaran gathering terbaru 3 pigura @Rp23.000, sedangkan penyesuaian berjumlah 9. PO lama mencatat 9 untuk gathering dan 1 untuk piagam @Rp24.000, tetapi hubungan retur batch tersebut masih memerlukan bukti. QACE002 juga memiliki perubahan volume pembelian. Kasus ini tetap ditahan agar harga dari lot atau satuan yang berbeda tidak diterapkan tanpa dasar.

[Daftar 72 barang beserta setiap batch dan alasannya](../database/reconciliation/p2_origin_20261111/unresolved.json) menjadi daftar pekerjaan berikutnya. Penutupan P2 penuh masih memerlukan bukti harga perolehan/satuan atau asal batch yang tercantum dalam daftar tersebut.

## Validasi dan audit

- Seluruh 943 saldo stok tetap identik dengan baseline pekerjaan ini dan sama dengan sisa batch. Saldo negatif nol; QFIL015 dan QSTE004 tetap nol. Konservasi setiap batch dan alokasi penuh seluruh pengeluaran 11512/11514 tetap lulus.
- Uji model aplikasi dalam transaksi yang dibatalkan berhasil mengeluarkan satu lembar QFLY001 dari batch 3696 dengan harga dan nilai snapshot Rp186. QCDR001 yang hanya memiliki batch tanpa harga ditolak. Seluruh mutasi uji di-rollback.
- Pemanggilan ulang runner memverifikasi hasil tanpa menulis ulang. Pemeriksaan independen membuktikan hanya empat tabel stok/LIFO yang sudah ada berubah; perubahan sesuai enam batch pilihan, 14 nilai alokasi, enam issue, dan enam log baru. Transaksi, header/detail PO/Request PIC, harga tahap sebelumnya, dan seluruh batch di luar pilihan tetap identik.
- Verifikasi P2 sebelumnya tetap lulus: 21 impor sebelumnya, nol status tertahan, 50 detail dalam arsip, serta satu fitting NPONK1709260004 masih menunggu penyerahan.
- Database uji menyimpan `p2_origin_price_review`, `p2_origin_price_run`, dan empat snapshot `p2_origin_backup_*`. Status run COMPLETE menyatakan pekerjaan penelusuran selesai, sedangkan P2 penuh masih tertahan oleh bukti harga 72 barang.

Artefak tetap: [database/reconciliation/p2_origin_20261111](../database/reconciliation/p2_origin_20261111/). Bukti sebelum perubahan dan skrip pemeriksaan tambahan berada di `/tmp/kiupo-p2-origin.mcJtfR/` selama direktori sementara masih tersedia.
