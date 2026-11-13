# P5 PO Jasa: pickup material, revisi SPK, evaluasi vendor — 12 November 2026

**P5 dapat ditutup untuk cakupan struktur dan sistem di database uji lokal.** Migrasi data lama tetap terpisah. Tidak ada perubahan pada kedua database asli atau perbandingan dengan production.

Database uji: `kiucoid_kiupo_merge_p0_20261110`. Seluruh penulisan DDL dan fixture hanya berlangsung di database ini; pemeriksaan struktur kosong menggunakan database sementara tersendiri yang kemudian dihapus.

## Alur yang lulus

| Gate | Hasil |
| --- | --- |
| Regresi pengajuan, approval berurutan, SPK, pembelian, penerimaan, harga aktual dan batch LIFO P3 | PASS |
| Pickup hanya setelah material tertaut master, kebutuhan direncanakan dan pembelian ON_HAND | PASS |
| PIC pemilik → Kadep sesuai departemen → Purchasing/Admin menyiapkan → PIC menerima | PASS |
| Penolakan dengan catatan, pengajuan ulang, permintaan berulang tanpa header ganda | PASS |
| Persiapan mengubah material pembelian menjadi reservasi, tanpa mutasi keluar | PASS |
| Konfirmasi PIC parsial dan sisa, mutasi 11512 dan harga batch; rollback ketika harga batch belum valid | PASS |
| Pickup selesai menjadi DITERIMA_PIC; notifikasi diterima PIC yang benar | PASS |
| Jadwal SPK: pengajuan, REVISI, pengajuan ulang, ACC Direktur; versi dan arsip | PASS |
| Revisi data: urutan Direktur Operasional/Direktur sesuai aturan departemen, replay dan konflik isi | PASS |
| Kedua jalur revisi saling mengunci saat ada pengajuan tertunda; BAST menunggu keputusan keduanya | PASS |
| Revisi material terkunci setelah masuk pickup; reservasi tidak dapat dilepas saat handover berjalan | PASS |
| Cetak SPK aktif dan arsip versi 1/2 memuat scope serta vendor; revisi jadwal mempertahankan estimasi | PASS |
| Revisi biaya overbudget: persetujuan memperbarui plafon dan status biaya, dengan rollback fixture | PASS: CLI Phase8 |
| Evaluasi hanya Purchasing/Admin setelah BAST dan DITUTUP; nilai 1–5, rata-rata dihitung server | PASS |
| Evaluasi vendor manual menyimpan identitas dari request; vendor/status/nilai total kiriman browser tidak dipercaya | PASS |
| Satu evaluasi per pekerjaan, pembaruan dengan versi, audit sebelum/sesudah dan token replay | PASS |
| CSRF, batas peran/departemen, kepemilikan, escaping catatan dan akses baca | PASS |
| Penutupan melalui BAST oleh Purchasing/Admin, invoice parsial/lunas dan larangan revisi setelah DITUTUP | PASS |
| Regresi stok dan Request PIC P2 | PASS: 9 kelompok HTTP |
| Struktur kosong, replay DDL, gate P2–P5, tanpa dependensi lintas database | PASS: 98 tabel aplikasi + 1 view |

Pengujian gabungan HTTP P3/P4/P5 menghasilkan **13 kelompok lulus**, regresi P2 **9 kelompok lulus**, CLI Phase6 **23 pemeriksaan lulus**, dan CLI Phase8 **32 pemeriksaan lulus**. Pemeriksaan sintaks mencakup 15 file PHP dan dua template JavaScript yang berubah.

## Perubahan yang menutup celah sistem

Pickup kini memakai transaksi dan penguncian request yang sama selama pengajuan, keputusan dan persiapan. Header/detail tidak dapat tertinggal separuh, dan token pengajuan mengikat request serta aktor. Penolakan disimpan sebagai riwayat; pengajuan berikutnya mempunyai header baru. Kode validator UUID yang sebelumnya belum tersedia pada controller PIC ditambahkan.

Purchasing menyiapkan kekurangan reservasi untuk material pembelian yang sudah diterima. Stok fisik berkurang hanya ketika PIC mengonfirmasi. Penerimaan parsial mempertahankan SIAP_DIAMBIL sampai seluruh kebutuhan diserahkan, kemudian menjadi DITERIMA_PIC dengan cap waktu. Konfirmasi menolak pickup yang belum siap dan pembelian yang kembali belum terpenuhi. Notifikasi siap ambil memakai pemilik `kd_user`, bukan kolom request `id_user` yang tidak tersedia.

Revisi jadwal dan perubahan data/biaya tetap menyimpan riwayat terpisah, tetapi saling mengunci pengajuan tertunda dan memakai versi SPK yang sama. Retry harus cocok dengan aktor serta isi semula. Persetujuan tidak dapat mengubah pekerjaan yang sudah ditutup; BAST juga tidak dapat mendahului keputusan revisi. Material yang sedang/disudah diserahkan tidak boleh ditulis ulang lewat revisi SPK.

Format snapshot untuk perubahan data diperbaiki agar sesuai kontrak cetak SPK awal: request lengkap, vendor, scope dan material tetap tersedia. Baris yang tidak berubah tidak ditulis ulang. Harga efektif menggunakan hasil review Purchasing; revisi jadwal saja tidak menurunkan harga atau mereset plafon yang sudah disetujui. Cetak dan arsip versi diperiksa isinya, bukan hanya respons HTTP.

Evaluasi memakai status aktif DITUTUP dan BAST, menggantikan gate lama DONE. Vendor master tetap memakai foreign key; vendor manual memakai kode NULL dan nama snapshot dari request, tanpa membuat identitas vendor fiktif. Nilai DECIMAL dua desimal, rata-rata server, versi baris dan audit bertoken mencegah duplikasi/penimpaan diam-diam. Halaman **BAST dan Pembayaran** kini juga menampilkan form evaluasi bagi Purchasing/Admin dan hasil baca bagi pihak berwenang. Ringkasan/laporan proyek selesai mengenali DITUTUP serta nama vendor manual.

## Integritas dan batas penutupan

Checksum isi **95 tabel PO Baru asli**, **44 tabel POERP asli**, dan **134 tabel yang sudah ada di database uji** sama dengan baseline sebelum P5. Database uji kini mempunyai **135 tabel dasar**, termasuk satu tabel audit evaluasi baru. Tabel audit baru kosong setelah uji; seluruh pengguna, request, pickup, SPK, evaluasi, audit, invoice, file evidence dan mutasi sintetis telah dibersihkan. Counter pengujian juga kembali ke keadaan awal.

DDL P5 menambahkan `received_at` pada pickup, hash isi pada pengajuan perubahan SPK, versi/nama snapshot evaluasi, serta tabel audit evaluasi. Upgrade idempotent; baseline struktur gabungan tidak berisi INSERT maupun referensi ke database asli. Tabel audit/backup rekonsiliasi historis tidak dimasukkan ke baseline aplikasi.

Penutupan ini merupakan penerimaan teknis lokal atas struktur dan alur yang disebutkan. Migrasi, pemetaan vendor lama, rekonsiliasi stok/harga historis dan penyesuaian data lama dilakukan kemudian mengikuti sistem gabungan. Format cetak SPK masih memakai template kompatibilitas yang sudah ada; persetujuan template resmi perusahaan tetap di luar gate teknis ini. Tidak ada deployment atau proses pembayaran bank.

## Artefak

- [Hasil dan cara menjalankan verifikasi](../database/verification/p5_system_20261112/README.md).
- [Upgrade struktur P5](../database/sql/p5_pojasa_structure_20261112.sql).
- [Gate struktur P5](../database/sql/p5_system_preflight_20261112.sql).
- [Baseline struktur gabungan tanpa data](../database/schema/p5_merged_structure_20261112.sql).

Integrasi dan regresi lintas modul ditutup pada [gate P6](P6_SYSTEM_CLOSURE_20261112.md), termasuk koreksi pelepasan reservasi sebelum SPK dan perlindungan reservasi stok terhadap transaksi modul lain.
