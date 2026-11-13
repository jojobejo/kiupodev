# Deploy modul PO non-komersil ke Hostinger

Dokumen ini memindahkan tiga bagian yang saling bergantung: PO non-komersil
lama, PO Jasa, dan Request PIC. Seluruh kode sumber yang dirujuk berada pada
tree `application/` saat ini; jangan memakai salinan lama di
`apps_production/`.

Gunakan arsip `dist/kiu_po_php83_hostinger_20260922.zip` untuk deploy PHP 8.3.
Arsip lama tanpa penanda `php83` tidak memuat dependensi spreadsheet yang baru.

## 1. Backup dan prasyarat

1. Backup database Hostinger dan seluruh folder aplikasi sebelum mengganti
   apa pun.
2. Pilih **PHP 8.3** di hPanel dan aktifkan ekstensi `ctype`, `dom`,
   `fileinfo`, `gd`, `iconv`, `mbstring`, `simplexml`, `xml`, `xmlreader`,
   `xmlwriter`, `zip`, dan `zlib`. Gunakan MariaDB 10.4+ (atau MySQL 8 yang mendukung sintaks
   `ADD COLUMN IF NOT EXISTS` dan `ADD INDEX IF NOT EXISTS`). PO Jasa memakai
   foreign key InnoDB dan `utf8mb4`.
3. Upload seluruh project ini ke document root domain, atau arahkan document
   root subdomain ke folder project. Jangan upload `db/`,
   `rollback_backup_*`, dump `*.sql`, atau file pengembangan lain ke web root.
4. Pastikan `.htaccess` ikut terunggah dan Apache `mod_rewrite` aktif.
5. Arsip PHP 8.3 sudah menyertakan folder `vendor/`. Jika deploy dari source
   Git, jalankan `composer install --no-dev --optimize-autoloader` menggunakan
   PHP 8.3 di root aplikasi sebelum aplikasi dibuka. Langkah ini memasang
   PhpSpreadsheet untuk ekspor Excel; PHPExcel lama tidak kompatibel dengan
   PHP 8.

## 2. Kode yang wajib ikut

Upload tree berikut dengan struktur yang sama:

```
application/controllers/purchaseorder/C_Order.php
application/controllers/purchaseorder/C_Reqpic.php
application/controllers/purchaseorder/C_Pojasa.php
application/controllers/purchaseorder/C_PojasaAjax.php
application/controllers/purchaseorder/C_PojasaPic.php
application/controllers/purchaseorder/C_PojasaPurchasing.php
application/controllers/purchaseorder/C_PojasaWorkflow.php
application/controllers/postatus/C_PoStatus.php
application/controllers/stock/C_Stocknonkomersil.php
application/models/PO/
application/models/stock/M_Stocknonkomersil.php
application/models/stock/M_StockLifo.php
application/models/Master_barang/M_MasterBarang.php
application/helpers/pojasa_*.php
application/views/content/po/
application/views/content/postatus/
application/views/content/stock/nonkomersil/
application/views/content/mbarang/
application/views/partial/
application/config/routes.php
application/config/autoload.php
application/config/database.php
assets/
images/
system/
index.php
.htaccess
```

`C_Order`, `C_PoStatus`, dan stok non-komersil ikut diperlukan karena PO Jasa
dan Request PIC membaca master barang, membuat PO pembelian, menerima stok,
serta menampilkan status/eviden PO non-komersil.

## 3. Routes yang sudah dimigrasikan

Route utama tersedia di `application/config/routes.php`:

| Modul | URL utama |
| --- | --- |
| PO non-komersil | `/pononkomersil`, `/postatusnk`, `/stocknonkomersil` |
| Request PIC | `/reqpic`, `/reqpicaccreq`, `/reqpicacckadep`, `/reqpicpickup` |
| PO Jasa | `/pojasa`, `/pojasa/pic`, `/pojasa/workflow`, `/pojasa/purchasing/detail/{kode}` |

Semua endpoint AJAX PO Jasa juga sudah berada pada grup route `/pojasa/ajax/...`.
Tidak perlu menambah route manual bila `routes.php` dari project ini diunggah.

## 4. Impor database

Untuk Hostinger yang sudah memiliki aplikasi PO, **jangan** mengimpor dump
penuh karena berisiko menimpa data produksi. Jalankan SQL berikut satu per
satu di phpMyAdmin, sesuai urutan ini. Berhenti jika satu file gagal dan
perbaiki penyebabnya sebelum menjalankan file sesudahnya.

1. Impor skema dan data PO non-komersil dasar yang belum ada dari dump
   produksi yang paling baru. Tabel dasar minimumnya adalah:
   `tbpo_user`, `tbpo_barang_nk`, `tbpo_barang_nk_lokasi`, `tbpo_satuan`,
   `tbpo_suplier`, `tbpo_po_nk`, `tbpo_detail_po_nk`, `tbpo_req_nk`,
   `tbpo_detail_req`, `tbpo_transaksi`, `tbpo_transaksi_tmp`,
   `tbpo_generate_kd_ponk`, `tbpo_file_nk`, `tbpo_file_bukti_beli`,
   `tbpo_tracking_po`, `tbpo_tmp_item_nk`, `tbpo_tmp_diskon_nk`,
   `tbpo_note_pembelian`, dan `tbpo_diskon_merk`.
   Gunakan `db/2026/kiucoid_po.sql` hanya untuk instalasi baru atau sebagai
   sumber tabel dasar—bukan untuk menimpa database aktif.
   Setelah tabel dasar tersedia, jalankan juga pembaruan PO non-komersil ini
   sesuai urutan:

```
db/2026/add_diskon_merk_satuan_po_20260603.sql
db/2026/add_minimum_stock_barang_nk_20260615.sql
db/2026/stocknonkomersil_defaults_indexes_20260702.sql
db/2026/add_realisasi_harga_nyata_ponk_20260730.sql
db/2026/po_nonkomersil_tambahan_20260902.sql
```

2. Untuk instalasi baru PO Jasa, impor basisnya berurutan:
   `docs/database/2026-09-02-po-jasa-tahap1.sql`,
   `docs/database/2026-09-02-po-jasa-vendor-workflow.sql`,
   `docs/database/2026-09-02-po-jasa-tahap2.sql`, lalu
   `docs/database/2026-09-02-po-jasa-tahap3.sql`.
3. Jalankan migrasi PO Jasa berikut secara kronologis:

```
database/sql/pojasa_direktur_oprasional_20260907.sql
database/sql/pojasa_detail_purchasing_input_20260907.sql
database/sql/pojasa_request_detail_split_20260907.sql
database/sql/pojasa_phase2_foundation_20260910.sql
database/sql/pojasa_phase3_pic_request_20260910.sql
database/sql/pojasa_phase4_approval_workflow_20260910.sql
database/sql/pojasa_phase5_purchasing_stock_spk_20260910.sql
database/sql/pojasa_phase6_execution_finalization_20260910.sql
database/sql/pojasa_phase7_limited_improvements_20260910.sql
database/sql/pojasa_phase8_purchase_revision_cost_20260911.sql
database/sql/pojasa_phase9_sequential_approval_flow_20260911.sql
database/sql/pojasa_phase10_material_catalog_20260914.sql
database/sql/pojasa_phase11_purchasing_review_20260914.sql
database/sql/pojasa_phase12_pic_purchasing_confirmation_20260914.sql
database/sql/pojasa_phase13_estimasi_header_dan_alur_approval_20260914.sql
database/sql/pojasa_phase14_pickup_workflow_20260915.sql
database/sql/pojasa_phase15_keep_purchase_in_process_20260915.sql
database/sql/pojasa_phase16_purchase_submission_revision_guard_20260915.sql
database/sql/pojasa_phase17_purchase_on_hand_stock_posting_20260915.sql
```

4. Jalankan tambahan yang dipakai Request PIC dan stok non-komersil:

```
database/sql/reqpic_supporting_documents_20260916.sql
database/sql/reqpic_prevent_cross_pic_merge_20260916.sql
database/sql/reqpic_pickup_status_length_20260917.sql
database/sql/stock_nonkomersil_lifo_foundation_20260918.sql
```

Untuk data historis PO Jasa, ekspor **data saja** dari semua tabel
`tbpo_jasa_%` pada localhost setelah skema Hostinger selesai. Untuk Request
PIC, ekspor data tabel `tbpo_req_nk`, `tbpo_detail_req`, dan
`tbpo_req_nk_supporting_file` beserta file pendukungnya. Pertahankan primary
key supaya relasi dokumen, approval, pembelian, dan stok tetap konsisten.

Setelah impor, jalankan `database/sql/hostinger/po_nonkomersil_preflight.sql`.
Kedua hasil query harus kosong; setiap baris yang muncul menunjukkan tabel atau
kolom yang belum berhasil dimigrasikan.

## Database Hostinger `u676129830_kiuponkomersil`

Dump lampiran memakai nama tabel lama `tb_*`, sementara aplikasi ini memakai
`tbpo_*`. File gabungan sudah dikunci untuk database Hostinger baru
`u676129830_kiuponkomersil`; impor hanya ke database itu yang masih kosong.
Setelah dump diimpor, jalankan urutan khusus ini:

1. Cara paling mudah: impor satu file
   `dist/u676129830_kiupo_php83_migrated.sql` ke database kosong.
2. Jika menjalankan file satu per satu, mulai dari
   `database/sql/hostinger/u676129830_kiupo_prefix_migration.sql`, lalu
   pembaruan non-komersil berikut (jangan jalankan
   `add_diskon_merk_satuan_po_20260603.sql`, karena itu hanya untuk PO
   komersil dan kolom dasarnya tidak tersedia di dump lampiran).
3. `database/sql/reqpic_supporting_documents_20260916.sql`
4. `database/sql/hostinger/u676129830_kiupo_reqpic_legacy_duplicates.sql`
5. `database/sql/reqpic_pickup_status_length_20260917.sql`
6. `database/sql/stock_nonkomersil_lifo_foundation_20260918.sql`
7. Basis PO Jasa: `docs/database/2026-09-02-po-jasa-tahap1.sql`, lalu
   `docs/database/2026-09-02-po-jasa-vendor-workflow.sql`, kemudian tahap 2
   dan tahap 3.
8. Seluruh file `pojasa_*` pada daftar sebelumnya, dari
   `pojasa_direktur_oprasional_20260907.sql` hingga phase 17.
9. `database/sql/hostinger/u676129830_kiupo_preflight.sql`

Jangan menjalankan `reqpic_prevent_cross_pic_merge_20260916.sql` pada dump ini:
uji pada salinan lampiran menemukan kode request historis yang duplikat. Skrip
khusus di langkah 4 mempertahankan seluruh data tersebut dan memasang indeks
non-unik agar modul tetap berjalan. Indeks unik hanya boleh ditambahkan setelah
duplikat historis direkonsiliasi oleh pemilik data.

## 5. File upload dan izin tulis

Upload isi folder-file berikut beserta subfoldernya. Beri izin tulis kepada
user web server (umumnya 755 untuk folder; gunakan 775 hanya bila Hostinger
memerlukannya):

```
images/pojasa/
images/request-pendukung/
images/filepndukung/
images/upbukti/
images/imgtmp/
images/gbrbarang/
images/qrcodebr/
```

Jangan gunakan 777 kecuali dukungan Hostinger menyatakannya sebagai satu-satunya
pilihan. Aplikasi membuat subfolder PO Jasa berdasarkan departemen dan tanggal.

## 6. Konfigurasi produksi

Set `ENVIRONMENT` menjadi `production` pada `index.php`. Konfigurasi database
produksi membaca environment variable berikut:

```
KIUPO_DB_HOST
KIUPO_DB_NAME=u676129830_kiuponkomersil
KIUPO_DB_USER
KIUPO_DB_PASS
```

Jika paket Hostinger Anda tidak menyediakan environment variable PHP, isi
empat nilai tersebut langsung pada blok `ENVIRONMENT === 'production'` di
`application/config/database.php` melalui File Manager, dan jangan commit file
yang berisi password ke Git. Kredensial hosting lama sudah dihapus dari source.

## 7. Uji setelah deploy

1. Login sebagai PIC, buat Request PIC dan upload dokumen pendukung.
2. Login sebagai Kadep/Admin untuk menguji approval serta pickup.
3. Buat PO Jasa, isi scope/bahan, approval, pembelian, dan unggah dokumen.
4. Pastikan `/stocknonkomersil` menampilkan penerimaan stok dan harga LIFO.
5. Periksa URL cetak SPK dan download dokumen; keduanya memastikan rewrite,
   route, database, dan permission upload sudah benar.
