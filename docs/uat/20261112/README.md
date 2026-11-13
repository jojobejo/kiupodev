# Panduan UAT seluruh alur PO

Paket disusun pada 12 November 2026 untuk pengujian penerimaan pengguna di database uji lokal. **UAT belum dijalankan dan belum disetujui.** Hasil P6 yang sudah lulus adalah bukti teknis pendahuluan, bukan pengganti hasil masing-masing tester.

Workbook: [Skenario, checklist dan hasil UAT](../../../outputs/uat_po_20261112_c72f4a/UAT_Seluruh_Alur_PO_20261112.xlsx).

Salinan review: [checklist per peran](CHECKLIST_PER_PERAN.md).

## Cakupan dan isi paket

- **80 skenario**: akses/otorisasi, PO komersil dan Shipment To, Request PIC, PO nonkomersil, stok dan batch LIFO, seluruh alur PO Jasa, pickup, revisi SPK, progres, BAST, pembayaran, evaluasi vendor dan integrasi stok bersama.
- **41 checklist per peran**: PIC, Kadep, Purchasing, Direktur Operasional, Direktur dan Admin.
- **11 prasyarat** lingkungan, akun, fixture, bukti dan penerimaan.
- Data sintetis yang harus disiapkan, register temuan/retest, persetujuan tiap peran dan keputusan koordinator.

Seluruh kasus diawali **Belum Diuji**. Nama tester, jadwal pelaksanaan, akun aktual, hasil aktual, bukti dan persetujuan belum diisi. Alias barang/dokumen pada workbook adalah rencana fixture; paket ini tidak membuat akun atau mengisi data transaksi ke database.

## Lingkungan dan batas pekerjaan

Target penulisan UAT hanya **`kiucoid_kiupo_merge_p0_20261110`**. Kedua sumber asli **`kiucoid_kiupo_baru`** dan **`kiucoid_po`** tidak boleh menjadi target koneksi aplikasi UAT, impor, perbaikan atau cleanup.

Sebelum mulai, Admin menetapkan URL UAT, akun peran, versi kode, kode run dan folder bukti pada Prasyarat. Server sementara pengujian P6 sudah dihentikan; alamat `127.0.0.1:18761` belum dianggap sebagai layanan UAT aktif. Gunakan aplikasi terisolasi dengan `CI_ENV=testing` dan konfigurasi database testing yang diverifikasi menunjuk target di atas. Jangan menjalankan UAT lewat konfigurasi default aplikasi.

Struktur aplikasi acuannya **98 tabel + 1 view**. Database uji saat penutupan P6 mempunyai **135 tabel dasar**, termasuk tabel pendukung/rekonsiliasi historis yang tidak termasuk baseline aplikasi. Perbedaan kedua jumlah ini tidak berarti ada tabel aplikasi yang hilang.

Migrasi dan rekonsiliasi data lama, penyesuaian harga/batch historis, deployment dan pembayaran bank tidak termasuk UAT ini. Cetak SPK masih memakai template kompatibilitas; periksa isi dan versi dalam UAT, sedangkan pengesahan template resmi perusahaan tetap terpisah.

## Akun dan tanggung jawab

| Alias | Konfigurasi yang diperlukan | Pemakaian |
| --- | --- | --- |
| PIC-A | Level 4, GA | Pemilik Request PIC dan PO Jasa fixture utama |
| PIC-B | Level 4, GA | Uji akses pihak lain dan request pesaing stok |
| Kadep-GA | Level 5, GA | Approval request, PO NK dan pickup GA |
| Kadep-IT | Level 5, IT | Uji larangan lintas departemen |
| Purchasing | Level 2, PURCHASING | Review, pembelian, penerimaan, stok, SPK, BAST, invoice, evaluasi |
| DirOps | Level 6, DIREKTUR OPERASIONAL | Approval Jasa dan revisi sesuai tahapan |
| Direktur | Level 3, DIREKTUR | Approval komersil/NK/Jasa dan keputusan revisi jadwal |
| Admin | Level 1 atau konfigurasi Admin yang berlaku | Lingkungan, akun, fixture, bukti teknis dan penutupan UAT |
| Keuangan-negatif | Level 2, KEUANGAN, bukan Purchasing | Memastikan tidak mendapatkan peran Purchasing PO Jasa |

Gunakan akun biasa untuk uji penolakan peran, bukan Admin. Admin mempunyai kemampuan tambahan pada sejumlah jalur. Akun Keuangan-negatif dipakai untuk batas PO Jasa; jangan menganggap semua akses stoknya harus ditolak, karena kebijakan stok aktif juga mengenali level 2 dan pengecualian kode akun tertentu. Jangan mengubah aturan hak akses untuk membuat hasil UAT terlihat lulus.

Gunakan profil browser terpisah agar sesi tidak tertukar. Workbook hanya memuat alias; bagikan kredensial kepada tester melalui mekanisme internal yang biasa digunakan, bukan melalui register hasil.

## Persiapan data

1. Tentukan kode run, misalnya `UAT-20261112-S01`. Tambahkan penanda itu pada nama master dan catatan fixture baru. Catat nomor transaksi yang benar-benar dibentuk aplikasi pada kolom **Kode aktual run** dan bukti kasus.
2. Sediakan supplier dan dua Shipment To dengan penerima/alamat yang jelas berbeda. Sediakan barang, satuan/lokasi dan dokumen fixture yang dapat diunggah.
3. Buat request melalui alur aplikasi. Jangan mengubah status langsung di database untuk melompati pengajuan, approval atau BAST. Jika sebuah prasyarat tidak dapat dibentuk melalui UI, tandai **Terhambat** dan catat temuan.
4. Setiap kelompok `K-BASE`, `N-BELI`, `J-BASE` adalah rangkaian utama. Kelompok `CABANG`, `RETUR`, `JADWAL`, `DATA`, `PICKLOCK`, `BASTGATE`, dan kelompok negatif lain memakai salinan request/SKU tersendiri. Prasyarat ID skenario menunjukkan tahap yang harus dicapai, bukan perintah mengubah request rangkaian utama.
5. Harga, kuantitas dan PPN 11% dalam workbook adalah **data sintetis khusus fixture**. Angka itu tidak menjadi penetapan harga historis atau kebijakan pajak umum. Jangan mengisi harga pada barang historis yang masih PERLU_HARGA untuk melancarkan UAT.

Kontrol angka utama:

| Fixture | Kontrol hasil |
| --- | --- |
| K-BASE | 2 × 10.000 + 3 × 5.000 = dasar 35.000; PPN fixture 11% = 3.850; masuk komersil 5 unit pada 11411 |
| N-BELI | Pembelian 2 @1.000 dan 3 @2.000 = 8.000; masuk 5 unit; kebutuhan pickup 1 + 2 = 3; sisa 2 |
| J-BASE | Scope 150.000 + estimasi material 3 @10.000 = 180.000; penerimaan 1 @9.000 + 2 @11.000 = biaya 31.000; pickup total 3 |
| J-RETUR | Salinan sebelum pickup: reverse penerimaan pertama 1 @9.000; saldo 2 dan biaya neto 22.000 |
| S-ADJ | Masuk 2, harga fixture 2.500, keluar 1; saldo 1 |
| X-RESERVASI | Fisik 3 dan reservasi Jasa 3 berarti bebas 0; tambahan bebas 1 dapat diambil request lain tanpa mengurangi reservasi 3 |
| Invoice | 100.000,50: dibayar kumulatif 0, 30.000,25, 100.000,50; invoice sen 0,30 dibayar 0,10 menghasilkan sisa 0,20 |

## Urutan sesi dan perpindahan peran

| Sesi | Urutan yang disarankan | Hasil akhir |
| --- | --- | --- |
| 1. Prasyarat dan akses | Admin siapkan lingkungan, semua peran login, jalankan A01–A06 | Sesi dan batas akses benar, data fixture terdaftar |
| 2. Komersil | Purchasing draft/pengajuan, Direktur ACC, Purchasing ON DELIVERY/cetak/penerimaan | DONE, Shipment To konsisten, mutasi 11411 tepat sekali |
| 3. NK dan Request PIC | PIC ajukan, Kadep ACC, Purchasing bentuk PO, Kadep/Direktur approve, Purchasing terima dan serahkan | Mutasi 11511/11512 dan sisa stock benar |
| 4. PO Jasa awal | PIC draft/ajukan, Purchasing review/UPDATE, PIC konfirmasi, Purchasing SUBMIT, Kadep, DirOps, Direktur, Purchasing TERBITKAN | SPK_TERBIT dan keterkaitan pembelian benar |
| 5. Material Jasa | Purchasing terima, PIC ajukan pickup, Kadep putuskan, Purchasing siapkan, PIC terima parsial/sisa | DITERIMA_PIC dan stok keluar hanya pada konfirmasi PIC |
| 6. Cabang dan integrasi | Jalankan fixture terpisah untuk retur, revisi, penolakan, reservasi bersama dan harga batch | Tidak menimpa versi atau menghabiskan reservasi pihak lain |
| 7. Penyelesaian Jasa | PIC progres 25% dan 100%, Purchasing/Admin BAST, Purchasing invoice/evaluasi | SELESAI kemudian DITUTUP melalui BAST; tracking pembayaran/evaluasi benar |
| 8. Retest dan persetujuan | Pemilik temuan perbaiki, tester retest, perwakilan peran review, koordinator putuskan | Keputusan UAT dengan bukti, bukan status otomatis dari P6 |

Approval Jasa yang aktif: **Purchasing UPDATE → konfirmasi PIC → Purchasing SUBMIT → Kadep → DirOps → Direktur → Purchasing TERBITKAN**. ACC Direktur tidak langsung menerbitkan SPK. Revisi review Kadep/DirOps/Direktur kembali kepada Purchasing; jalur lama yang melewati konfirmasi PIC tidak menjadi acuan UAT.

Menu utama yang dipakai tester: draft/detail PO komersil, status PO, Request PIC, detail PO NK, Stok Nonkomersil, PO Jasa PIC/Workflow/Purchasing, SPK dan arsip versinya, serta **BAST dan Pembayaran**. Admin membantu pengujian token, sesi dan bukti mutasi; tester bisnis menjalankan langkah melalui UI.

## Cara mencatat hasil

- **Belum Diuji**: belum dijalankan. **Dalam Proses**: sedang dijalankan. **Lulus**: hasil aktual sesuai seluruh hasil yang diharapkan. **Gagal**: perilaku berbeda. **Terhambat**: tidak dapat dieksekusi karena akun/data/prasyarat.
- Untuk kasus negatif, **Lulus** berarti penolakan yang benar dan tidak ada perubahan parsial. Bukan berarti aksi terlarang harus berhasil.
- Tiap hasil Lulus memerlukan tester, tanggal, hasil aktual dan bukti. Gagal juga dihubungkan ke ID temuan. Jangan menyalin hasil P6 ke kolom hasil UAT.
- Bukti minimal: kode run/nomor dokumen, akun/peran, status sebelum/sesudah, angka yang diperiksa dan tangkapan layar atau PDF/tautan bukti. Sertakan histori mutasi/batch untuk kasus stok dan versi cetak untuk kasus SPK.
- Gunakan nama bukti seperti `J10_UAT-20261112-S01_penerimaan_01.png`. Simpan bukti di folder internal yang telah ditetapkan, tanpa password atau data transaksi nyata.
- Pada Peran, filter peran masing-masing dan isi checklist setelah membaca bukti skenario rujukannya. Hasil Skenario tetap menjadi sumber tunggal hasil kasus; checklist adalah review tanggung jawab peran.

Ringkasan menghitung progres, kelengkapan bukti, checklist, prasyarat dan temuan terbuka. UAT tidak otomatis diterima hanya karena jumlah Lulus sudah penuh. Keputusan akhir koordinator serta persetujuan peran tetap harus diisi.

Workbook menyediakan 20 baris temuan. Jika perlu lebih banyak, koordinator memperluas tabel, validasi dan rentang ringkasan bersama; jangan menulis temuan di luar rentang lalu menganggapnya sudah terhitung.

## Temuan, retest dan kriteria penutupan yang diusulkan

Kriteria berikut disiapkan untuk disepakati koordinator pada PR09:

1. Seluruh 80 kasus wajib dieksekusi dan Lulus dengan bukti. Kasus Terhambat tetap menghalangi penutupan; tidak diubah menjadi Lulus karena fitur belum dapat dijangkau.
2. Seluruh prasyarat dan checklist peran lengkap. Semua perwakilan peran meninjau dan menyetujui hasilnya.
3. Temuan **Kritis** (contoh: sumber asli tersentuh, akses pihak lain, stok/nominal rusak) dan **Mayor** (alur utama macet/berulang, approval atau versi salah) harus diperbaiki serta lulus retest.
4. Temuan **Minor** yang tidak memengaruhi kebenaran/akses boleh diterima hanya dengan alasan, penyetuju dan tanggal. Penerimaan minor tidak mengubah kasus gagal menjadi Lulus jika hasil yang diwajibkan belum terpenuhi; penyesuaian kriteria memerlukan keputusan koordinator yang tercatat.
5. Kedua database asli tidak berubah. Bukti serta manifest fixture diarsipkan; cleanup hanya dilakukan untuk run ini setelah disetujui penanggung jawab lingkungan.
6. Koordinator mengisi keputusan akhir, nama, tanggal dan bukti keputusan. Penutupan UAT tidak memberi otorisasi migrasi atau deployment.

Pada Temuan, **Lulus Retest** memerlukan hasil retest, bukti, validator dan tanggal. **Diterima Minor** memerlukan keparahan Minor, alasan penerimaan, penyetuju dan tanggal. Baris yang belum lengkap tetap dihitung Terbuka.

## Pemeriksaan teknis dan integritas

Admin dapat menggunakan [gate P6](../../../database/verification/p6_system_20261112/README.md) untuk pemeriksaan teknis pendahuluan. **Jangan menjalankan runner otomatis P6 selama fixture UAT sedang dipakai**: suite memiliki prasyarat counter kosong dan cleanup sendiri. Untuk pelaksanaan UAT baru, ambil baseline baru dan jalankan gate metadata baca-saja P2–P6. Jangan menimpa baseline P6 hanya untuk menghilangkan perbedaan checksum.

Penyusunan paket ini hanya membuat file panduan, dataset skenario dan workbook. Tidak ada perubahan kode aplikasi, DDL, akun maupun transaksi. Pemeriksaan baca-saja terhadap checksum isi dan struktur seluruh database dilakukan sebelum dan sesudah persiapan; hasil disimpan pada `integrity_before.log` dan `integrity_after.log`.

Acuan: [penutupan P6](../../P6_SYSTEM_CLOSURE_20261112.md), [penutupan P5](../../P5_SYSTEM_CLOSURE_20261112.md), kontrak peran/status PO Jasa, serta model aktif pickup, settlement dan revisi SPK. Dataset sumber paket tersimpan pada `uat_cases.json`.
