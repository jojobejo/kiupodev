# P4 PO Jasa: progres, BAST dan pembayaran — 12 November 2026

**P4 dapat ditutup untuk cakupan struktur dan sistem yang diuji di database lokal.** Migrasi data historis tetap terpisah.

Keputusan pengguna: **Purchasing/Admin menutup pekerjaan melalui BAST setelah progres 100%**. Alur gabungan kini memakai `SPK_TERBIT → ON_PROGRESS → SELESAI → DITUTUP`; PIC menyelesaikan progres, sedangkan BAST menetapkan penutupan oleh Purchasing/Admin.

## Alur dan hasil uji

| Gate | Hasil |
| --- | --- |
| Regresi alur P3 dari pengajuan sampai penerimaan, termasuk persetujuan berurutan dan batch LIFO | PASS: 5 kelompok HTTP |
| Progres dan evidence, aktivitas panjang, replay, konflik isi berkas, larangan persentase mundur dan larangan PIC menutup | PASS |
| BAST mensyaratkan 100%, penerimaan material selesai, versi request sesuai dan SPK tersedia | PASS |
| Satu BAST per pekerjaan, replay tanpa duplikasi, penutupan dan cap waktu/aktor Purchasing | PASS |
| Pekerjaan tertutup menolak progres serta penerimaan/reversal baru | PASS |
| Invoice baru → pembayaran parsial → lunas, tanpa menggandakan tagihan | PASS |
| Validasi nominal/tanggal, status dihitung server, konflik token, invoice duplikat dan versi usang | PASS |
| Riwayat perubahan pembayaran dan pembatasan akses baca/tulis | PASS |
| Pemeriksaan CLI eksekusi dan penutupan BAST | PASS: 23 pemeriksaan |
| Regresi P2 setelah integrasi | PASS: 9 kelompok HTTP |
| Struktur kosong tanpa data, 97 tabel + 1 view, gate P2/P3/P4 dan replay DDL | PASS |
| Sintaks PHP dan JavaScript halaman settlement; 1.000 UUID aksi valid/unik | PASS |

Uji pembayaran memakai invoice Rp100.000,50: belum dibayar, diperbarui menjadi Rp30.000,25, kemudian lunas Rp100.000,50. Invoice tetap satu baris; tiga kejadian tersimpan pada audit. Invoice kedua Rp0,30 dengan pembayaran Rp0,10 menghasilkan sisa tepat Rp0,20. Nominal divalidasi dengan representasi sen integer; nilai disimpan sebagai DECIMAL(18,2). Status `BELUM BAYAR`/`PARTIAL`/`LUNAS` ditentukan dari nominal, bukan status kiriman browser.

## Perubahan sistem

- Jalur BAST dan pembayaran lama memakai validasi/model settlement yang sama dengan alur aktif. BAST tidak lagi mengubah pekerjaan menjadi status legacy `DONE`; penutupan menjadi `DITUTUP`, dengan versi, cap waktu, aktor dan log.
- PIC tidak mempunyai aksi progres `DITUTUP`. Aktivitas lengkap tetap tersimpan pada kolom teks; ringkasan milestone dibatasi 180 karakter agar aktivitas panjang tidak menggagalkan penyimpanan. Hash replay progres mencakup hash berkas evidence.
- BAST mempunyai token replay dan referensi revisi/SPK. Pembatas unik satu BAST per request dipertahankan.
- Invoice unik per kombinasi PO Jasa dan nomor invoice. Pembaruan invoice memakai ID dan versi baris sehingga perubahan bersamaan tidak menimpa data secara diam-diam. Pembayaran adalah **total kumulatif untuk invoice tersebut**, dengan audit sebelum/sesudah setiap penyimpanan.
- Tabel `tbpo_jasa_payment_audit` mengikat invoice, request dan aktor dengan foreign key, serta token unik. Pembayaran tidak mengubah status pekerjaan yang telah ditutup.
- Halaman `pojasa/settlement/{kode}` tersedia melalui tautan **BAST dan Pembayaran** di detail PIC, workflow, serta detail PO Jasa lama. Purchasing/Admin dapat mengisi; pemilik PIC dan pembaca yang berwenang dapat melihat. PIC lain dan Kadep departemen lain ditolak.
- Penerimaan/reversal material dengan token baru ditolak setelah pekerjaan ditutup. Replay penerimaan yang telah tersimpan tetap tidak membuat mutasi baru.

## Struktur dan integritas

Struktur P4 menambahkan kolom audit/replay pada `tbpo_jasa_bast`, versi pembaruan serta indeks invoice unik pada `tbpo_jasa_payment`, dan satu tabel audit pembayaran. Upgrade bersifat idempotent dan tidak berisi impor, deduplikasi, atau pembaruan data historis.

Checksum isi **95 tabel database PO Baru asli**, **44 tabel database POERP asli**, dan **133 tabel yang sudah ada pada database uji** tetap sama dengan baseline sebelum P4. Kedua database asli tidak diubah. Tabel audit baru kosong setelah fixture dibersihkan; counter PO Jasa juga kembali kosong. Database uji kini mempunyai 134 tabel dasar, termasuk tabel audit P4.

Seluruh pengguna, invoice, BAST, dokumen unggahan, notifikasi, mutasi dan data pendukung pengujian bersifat sintetis dan sudah dibersihkan. Database sementara untuk uji struktur juga sudah dihapus. Tidak ada rekonsiliasi harga atau migrasi data lama tambahan.

Penutupan ini mencakup progres, BAST dan tracking pembayaran, serta integrasi/regresi yang disebut di atas. Evaluasi vendor, siklus pickup PO Jasa secara penuh dan revisi SPK lanjutan belum dinyatakan lulus oleh gate P4 ini.

## Artefak

- [Log, hasil verifikasi dan skrip](../database/verification/p4_system_20261112/README.md).
- [Upgrade struktur P4](../database/sql/p4_pojasa_structure_20261112.sql).
- [Kontrak struktur P4](../database/sql/p4_system_preflight_20261112.sql).
- [Baseline struktur gabungan tanpa data](../database/schema/p4_merged_structure_20261112.sql).

Data historis akan disesuaikan kemudian terhadap struktur serta aturan sistem gabungan ini.

Pemeriksaan lanjutan pickup, revisi SPK dan evaluasi vendor selesai pada [gate P5](P5_SYSTEM_CLOSURE_20261112.md). Gate material/BAST kini juga mencakup seluruh kebutuhan handover dan keputusan revisi yang tertunda.
