# P6 integrasi dan regresi PO — 12 November 2026

**P6 dapat ditutup untuk cakupan struktur dan sistem yang diuji di database lokal.** Migrasi data historis tetap ditunda sesuai arahan pengguna. Kedua database asli tidak diubah; tidak ada perbandingan dengan production maupun deployment.

Seluruh penulisan pengujian menggunakan `kiucoid_kiupo_merge_p0_20261110` melalui aplikasi CodeIgniter **testing** terisolasi. Pemeriksaan struktur kosong menggunakan database sementara dengan nama acak, lalu menghapus database yang dibuatnya sendiri.

## Gate penerimaan

| Cakupan | Hasil akhir |
| --- | --- |
| PO komersil: draft Shipment To, buka ulang, pengajuan, approval Direktur, ON DELIVERY, penerimaan dan cetak | PASS: 9 kelompok HTTP |
| Request PIC / PO nonkomersil: Kadep, Direktur, pembelian, penerimaan, pickup parsial, retry, mutasi dan adjustment | PASS: 9 kelompok HTTP |
| PO Jasa: draft, review Purchasing, konfirmasi PIC, approval berurutan, SPK, pembelian dan penerimaan | PASS |
| PO Jasa: pickup Kadep/Purchasing/PIC, progres, BAST, invoice, revisi jadwal/data SPK dan evaluasi vendor | PASS |
| Stok bersama: Request PIC dan adjustment tidak boleh menghabiskan reservasi PO Jasa | PASS |
| Stok bersama: penambahan stok bebas memungkinkan retry Request PIC dengan harga batch sendiri, tanpa mengurangi reservasi PO Jasa | PASS |
| Akun komersil `11411` tidak menambah saldo stok nonkomersil untuk SKU yang sama | PASS |
| Keseluruhan suite PO Jasa dan stok bersama | PASS: 15 kelompok HTTP |
| Delapan suite CLI PO Jasa: fondasi, otorisasi, draft, pending, revisi, penolakan, perencanaan, penerbitan, eksekusi dan integrasi | PASS: 210 pemeriksaan |
| Struktur kosong tanpa data: fondasi, replay baseline/upgrade, gate P2–P6 dan view lokal | PASS: 98 tabel aplikasi + 1 view |
| Gate struktur P2–P6 pada database uji aktif | PASS |
| Sintaks PHP pada model yang berubah dan seluruh controller pemeriksaan CLI | PASS: 12 file |
| Isi seluruh tabel dan struktur kedua database asli serta database uji setelah cleanup | PASS |

Total penerimaan P6: **33 kelompok HTTP dan 210 pemeriksaan CLI lulus**. Pengujian akhir dijalankan berurutan menggunakan satu runner, dengan pemeriksaan integritas sebelum dan sesudahnya. Perubahan terakhir pada penguncian stok sudah termasuk dalam hasil akhir tersebut.

## Temuan yang diselesaikan

### Pelepasan reservasi sebelum SPK

Pembatas pickup P5 sempat menolak pelepasan reservasi pada tahap perencanaan sebelum SPK terbit. Akibatnya stok bebas tidak kembali tersedia, dan skenario perencanaan/penolakan berikutnya ikut gagal. Pelepasan kini diizinkan pada tahap aktif sebelum SPK, dengan pembatas peran yang sama. Pelepasan tetap ditolak untuk request terminal atau material yang sudah masuk proses pickup. Pengujian perencanaan, pengembalian stok bebas, pembatalan draft dan pelepasan otomatis saat penolakan sudah lulus.

### Konsumsi stok lintas modul

Uji dengan satu SKU bersama membuktikan bahwa Request PIC dapat mengambil stok yang sudah direservasi PO Jasa. Pemeriksaan batch LIFO sebelumnya hanya memastikan jumlah dan harga batch mencukupi; reservasi PO Jasa belum diperhitungkan oleh jalur keluar lain.

Pemeriksaan reservasi kini berlaku pada alokasi stok keluar bersama. Request PIC, adjustment keluar dan reversal harus menyisakan stok yang masih direservasi. Konfirmasi PIC PO Jasa dapat memakai reservasinya sendiri; pengecualian itu menerima kode request lengkap dan memvalidasi kaitannya dengan pemilik serta referensi transaksi. Harga tetap diambil dari batch LIFO, dan reversal tetap terbatas pada batch asal.

Penguncian menggunakan master stok yang sama, pembacaan batch/reservasi terbaru dengan locking read, serta urutan penguncian master sebelum baris alokasi pada konfirmasi material. Pembentukan reservasi dan persiapan pickup juga membaca saldo/reservasi terkini. Uji penerimaan P6 mencakup rollback dan retry lintas modul; hasil ini bukan pengukuran kapasitas atau beban serentak.

Bukti uji sintetis: 3 unit stok direservasi untuk PO Jasa. Request PIC sebanyak 1 unit dan adjustment keluar ditolak tanpa mengubah status/detail/mutasi. Setelah 1 unit bebas ditambahkan dan diberi harga fixture Rp2.500, Request PIC yang sama berhasil menyerahkan 1 unit dari batch tersebut. Tiga unit reservasi PO Jasa tetap utuh, kemudian diterima PIC PO Jasa melalui alur normal. Saldo akhir fixture kembali nol.

### Pemeriksaan lama yang tertinggal

Beberapa controller pemeriksaan CLI masih mengharapkan alur sebelum P3: Purchasing langsung ke Kadep, approval Direktur langsung menerbitkan SPK, dan revisi Kadep kembali ke PIC. Pemeriksaan diperbarui mengikuti kontrak aktif:

`Purchasing UPDATE → konfirmasi PIC → Purchasing SUBMIT → Kadep → Direktur Operasional → Direktur → Purchasing TERBITKAN → SPK_TERBIT`.

Kadep/DirOps/Direktur mengembalikan revisi review kepada Purchasing dengan revisi baris berikutnya; Kadep tetap mempunyai cabang PENDING dan kelanjutan approval. Pengujian ini memeriksa model transaksi dan otorisasi; pengujian HTTP memeriksa alur beserta validasi controller.

Fixture kekurangan stok CLI juga dibuat terisolasi agar hasilnya tidak bergantung pada saldo historis barang yang kebetulan dipilih. Perubahan tersebut tidak mengubah aturan bisnis untuk menyesuaikan ekspektasi pengujian lama.

## Integritas dan struktur

- Isi **95 tabel PO Baru asli** (`kiucoid_kiupo_baru`) tidak berubah.
- Isi **44 tabel POERP asli** (`kiucoid_po`) tidak berubah.
- Isi **135 tabel database uji** sama dengan baseline sebelum P6 setelah seluruh fixture dibersihkan.
- Hash struktur ternormalisasi seluruh tabel/view sama: 96 objek PO Baru, 45 objek POERP, dan 136 objek database uji. Nilai counter `AUTO_INCREMENT` dikecualikan karena pembuatan fixture dapat menaikkannya; definisi tabel, indeks dan view tetap diperiksa.
- Tidak ada DDL baru yang perlu diterapkan pada database uji untuk P6. Baseline aplikasi tetap struktur P5: **98 tabel + 1 view**, tanpa data historis dan tanpa dependensi lintas database. Tabel audit/backup rekonsiliasi lama tidak disertakan dalam baseline aplikasi.
- Gate P6 menambahkan kontrak integrasi baca-saja: mesin transaksi, kolom sumber/Shipment To/pickup, indeks unik yang diperlukan, dan batas dependensi database.

Pengguna, supplier, template Shipment To, SKU, request, pickup, invoice, evaluasi, audit, counter penomoran, notifikasi, mutasi, batch dan unggahan sintetis telah dibersihkan. Server HTTP pengujian dihentikan oleh runner. Tidak ada perubahan harga atau pemetaan pada data historis.

## Keputusan dan tindak lanjut

P6 ditutup untuk penerimaan teknis lokal atas cakupan pada tabel di atas. Sistem siap menjadi dasar UAT dengan skenario operasional pengguna. Migrasi data lama, penyesuaian harga/batch historis serta template SPK resmi perusahaan tetap mengikuti pekerjaan terpisah yang telah ditunda. Penutupan ini tidak memulai migrasi atau deployment.

## Artefak

- [Runner, hasil dan cara mengulang pengujian](../database/verification/p6_system_20261112/README.md).
- [Hasil terstruktur](../database/verification/p6_system_20261112/results.json).
- [Gate struktur P6](../database/sql/p6_system_preflight_20261112.sql).
- [Baseline struktur aplikasi yang tetap berlaku](../database/schema/p5_merged_structure_20261112.sql).
