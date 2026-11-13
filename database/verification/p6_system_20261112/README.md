# Verifikasi P6 — integrasi dan regresi lokal

Hasil akhir: **PASS; 33 kelompok HTTP + 210 pemeriksaan CLI**. [Laporan penutupan](../../../docs/P6_SYSTEM_CLOSURE_20261112.md).

## Pengujian dan bukti

| Artefak | Isi |
| --- | --- |
| `results.json` / `run-all.log` | Hasil runner terintegrasi, jumlah gate dan status penutupan server |
| `commercial.log` | 9 kelompok PO komersil, Shipment To, pemasok ambigu sintetis, cetak dan isolasi akun |
| `noncommercial-regression.log` | 9 kelompok Request PIC, PO nonkomersil dan stok |
| `jasa-integration.log` | 15 kelompok P3/P4/P5 dan persaingan stok antar modul |
| `cli-phase2.json` hingga `cli-phase9.json` | Delapan suite internal PO Jasa, total 210 pemeriksaan; nomor CLI ini bukan nomor milestone P2–P9 |
| `empty-schema.json` | 98 tabel + 1 view kosong, replay DDL, gate P2–P6 dan penghapusan schema sementara |
| `before-checksums.json` / `before-schema.json` | Baseline isi dan hash struktur seluruh database lokal sebelum P6 |
| `before-run-integrity.log` / `final-integrity.log` | Isi dan struktur tetap sama pada kedua sumber asli serta database uji |
| `verified-sha256.json` | Hash kode, runner dan struktur yang diuji |
| `http-server.log` | Log server HTTP testing milik runner |

## Lingkungan terisolasi

Database penulisan hanya `kiucoid_kiupo_merge_p0_20261110`. Server testing menggunakan `127.0.0.1:18761`, PHP XAMPP dan socket MariaDB lokal. Kedua database asli hanya diperiksa checksum/hash strukturnya. Jangan gunakan konfigurasi database default aplikasi untuk menjalankan pengujian.

Aplikasi testing sesi ini berada di `/tmp/kiupo-p1-http.it7zn4`. Folder controller/model/view/helper mengarah ke source workspace; konfigurasi `application/config/testing/database.php` menunjuk database uji, dan router menetapkan `CI_ENV=testing`. Runner memvalidasi symlink source, routes, konfigurasi database dan port sebelum menjalankan server. Jika port ditempati, runner berhenti; ia tidak mematikan proses lain.

## Mengulang gate pada baseline sesi ini

Dari root workspace, dengan port 18761 kosong:

```sh
python3 database/verification/p6_system_20261112/run_all.py \
  --app-root /tmp/kiupo-p1-http.it7zn4
```

Runner memulai server miliknya sendiri, menjalankan tiga suite HTTP, delapan suite CLI, gate struktur kosong/aktif dan integritas, lalu menghentikan server dalam `finally`. Semua hasil ditulis di folder ini. CLI dijalankan tanpa `--persist`.

Suite komersil merupakan fixture P1 yang disimpan ulang dan diperluas untuk P6; tidak menjalankan rekonsiliasi tujuh header historis. Suite PO Jasa memperluas gate P5 dengan Request PIC dan adjustment pada SKU yang sama. Pembentukan barang/penomoran tambahan serta catatan adjustment dibersihkan secara eksplisit. Seluruh fixture memakai penanda acak; penghapusan berdasarkan kode fixture milik run tersebut.

`check_schema.py` memakai baseline DDL P5 yang tidak berubah, menjalankan fondasi dan upgrade secara idempotent, gate P2–P6, memeriksa nol data dan tidak adanya dependensi lintas database, kemudian menghapus schema sementara dengan prefix khusus yang dibuatnya sendiri.

Baseline yang tersimpan mengacu pada keadaan sebelum sesi P6 ini. Jika data uji sengaja diubah setelahnya, buat direktori bukti/baseline sesi baru. `capture_baseline.py` menolak menimpa baseline lama; jangan mengganti bukti lama untuk membuat pemeriksaan integritas terlihat lulus.

## Perubahan source P6

- `M_StockLifo`: reservasi bersama pada jalur keluar dan precheck adjustment, locking read serta pengecualian reservasi sendiri yang terikat request.
- `M_PojasaCore`: kode request lengkap saat konsumsi reservasi sendiri dan urutan penguncian master sebelum alokasi.
- `M_PojasaPurchasing`: pelepasan sebelum SPK kembali diperbolehkan, dengan pembatas terminal/pickup; saldo dan reservasi dibaca terkini.
- `M_PojasaPickup`: pembacaan saldo/reservasi terkini saat persiapan.
- CLI `PojasaPhase2Check`, `Phase4Check`, `Phase5Check`, `Phase7Check`: ekspektasi kontrak aktif, cabang revisi/approval dan fixture perencanaan yang terisolasi.
- Fixture HTTP P3: assertion harga mutasi dibatasi pada request sendiri agar sah untuk pengujian SKU bersama.

Tidak ada DDL baru pada database uji dan tidak ada migrasi data historis. `p6_system_preflight_20261112.sql` hanya membaca metadata.
