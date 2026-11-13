# P3 PO Jasa: struktur dan sistem — 11 November 2026

Pembaruan 12 November: [P4 progres, BAST dan pembayaran telah diuji](P4_SYSTEM_CLOSURE_20261112.md). Sesuai keputusan pengguna, penutupan setelah progres 100% dilakukan Purchasing/Admin lewat BAST. Batas pengujian P3 di bawah merupakan riwayat saat penutupan P3; regresi alur P3 tetap lulus pada P4.

**P3 dapat ditutup untuk cakupan pengajuan, approval, pembelian, serta penerimaan PO Jasa yang diuji secara lokal.** Migrasi dan rekonsiliasi data historis tetap merupakan pekerjaan terpisah, sesuai arahan pengguna.

Seluruh perubahan struktur dan fixture pengujian diarahkan ke `kiucoid_kiupo_merge_p0_20261110`. Kedua database asli tidak diubah. Tidak ada impor atau penyesuaian data historis tambahan.

## Alur yang lulus

Pengajuan PIC melalui Purchasing awal → konfirmasi PIC → pengajuan Purchasing → Kadep → Direktur Operasional → Direktur → penerbitan SPK dan PO pembelian oleh Purchasing. Penerbitan baru terjadi setelah rangkaian approval selesai.

| Pemeriksaan | Hasil |
| --- | --- |
| Draft disimpan dan dibuka ulang; master material serta harga PIC dikendalikan server | PASS |
| Dokumen pendukung diunggah melalui HTTP; submit ditolak bila belum ada dokumen | PASS |
| Kepemilikan PIC, CSRF, peran, dan departemen Kadep | PASS |
| Review vendor, scope, material, dan draft pembelian oleh Purchasing | PASS |
| Approval berurutan, larangan melompati tahap, dan replay aksi | PASS |
| SPK dapat dibuka; halaman cetak HTML tersedia; PO pembelian tertaut ke PIC/PO Jasa asal | PASS |
| Penerimaan parsial, batas sisa quantity, biaya otomatis, dan batch LIFO berharga aktual | PASS |
| Perubahan payload pada token penerimaan yang sama ditolak | PASS |
| Gagal membentuk batch membatalkan receipt, biaya, dan mutasi stok terkait | PASS |
| Endpoint penerimaan bersama tidak menggandakan penerimaan yang sudah lengkap | PASS |
| Reversal memakai batch serta harga penerimaan asal; replay dan konflik token diperiksa | PASS |
| Konfirmasi penerimaan PIC: kepemilikan, harga batch wajib, rollback dan mutasi keluar LIFO | PASS |
| Dua pemeriksaan CLI yang diperbarui: 24 + 8 pemeriksaan | PASS |
| Regresi sembilan kelompok alur P2 setelah perubahan model LIFO | PASS |
| Struktur kosong: 96 tabel + 1 view, nol data historis, gate P2/P3 dan replay DDL | PASS |

Pengujian penerimaan parsial menggunakan satu material master sintetis: 1 unit @Rp9.000 lalu 2 unit @Rp11.000. Dua batch mempertahankan harga masing-masing; biaya otomatis berjumlah Rp31.000. Reversal penerimaan pertama mengurangi batch Rp9.000, sementara batch Rp11.000 tetap. Konfirmasi PIC dari reservasi sintetis berikutnya membentuk alokasi keluar dengan snapshot Rp11.000. Reservasi untuk skenario konfirmasi PIC disiapkan sebagai fixture agar endpoint penerimaan dapat diuji terpisah dari perencanaan pembelian.

Status pemenuhan submission menjadi `ON_HAND` setelah seluruh quantity diterima. Header PO pembelian sumber PO Jasa tetap `PROSES PEMBELIAN` sesuai perilaku monitoring yang sudah ada; status pemenuhan tersimpan terpisah pada submission/detail.

## Perbaikan yang diterapkan

1. Struktur `tbpo_jasa_request_detail` dilengkapi `jenis_detail` untuk kompatibilitas jalur PO Jasa lama. `verification_status` pada biaya aktual diperlebar menjadi VARCHAR(50); ukuran sebelumnya tidak memuat `OTOMATIS_TERVERIFIKASI` dan menyebabkan penerimaan gagal. SQL fondasi diperbaiki dan upgrade struktur idempotent tersedia terpisah.
2. Penerimaan PO Jasa kini membentuk mutasi dan batch LIFO dalam transaksi yang sama, dengan detail PO yang tepat dan snapshot harga aktual per penerimaan. Harga rata-rata pada detail PO tidak mengganti harga batch penerimaan parsial sebelumnya.
3. Reversal menargetkan batch penerimaan asal. Jika batch asal tidak cukup, perubahan dibatalkan. Token receipt/reversal diperiksa terhadap request, aktor, dan payload relevan agar token yang sama tidak dapat digunakan untuk data lain.
4. Konfirmasi stok oleh PIC kini mengalokasikan LIFO dalam transaksi yang sama dengan mutasi keluar. Ketiadaan schema/batch berharga membatalkan perubahan.
5. Fixture pemeriksaan CLI PIC disesuaikan dengan kontrak material saat ini, serta memakai berkas teks sintetis sementara. Pemeriksaan approval CLI mengikuti langkah pengajuan Purchasing setelah konfirmasi PIC.

## Integritas dan batas cakupan

Checksum isi **95 tabel PO Baru**, **44 tabel POERP**, dan **133 tabel yang telah ada di database uji** identik dengan baseline sebelum pekerjaan. Fixture, dokumen unggahan sintetis, notifikasi, biaya, mutasi, dan counter pengujian dibersihkan. Perubahan permanen database uji hanya struktur dua kolom yang disebut di atas.

Baseline struktur kosong tidak mengandung data, counter AUTO_INCREMENT historis, atau ketergantungan view ke database sumber. Database sementara untuk uji struktur sudah dihapus. Sintaks PHP berkas yang diubah lulus pemeriksaan.

Keputusan P3 mencakup alur pada tabel pengujian di atas. Pelaksanaan/progress pekerjaan, BAST, pembayaran, evaluasi vendor, revisi SPK lanjutan, dan siklus pickup PO Jasa secara penuh belum dinyatakan lulus oleh penutupan ini. Kelengkapan master dan data historis akan ditangani saat migrasi.

## Artefak

- [Hasil verifikasi, log dan skrip pengujian](../database/verification/p3_system_20261111/README.md).
- [Upgrade struktur P3 tanpa data](../database/sql/p3_pojasa_structure_20261111.sql).
- [Kontrak pemeriksaan struktur P3](../database/sql/p3_system_preflight_20261111.sql).
- [Baseline struktur gabungan tanpa data](../database/schema/p3_merged_structure_20261111.sql).
