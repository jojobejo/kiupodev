# Checklist UAT per peran

Rujukan pelaksanaan: [panduan UAT](README.md). Catat hasil resmi, tester, tanggal dan bukti pada workbook; kotak di bawah hanya salinan untuk membantu review.

## PIC

- [ ] **CK01** Draft dan pengajuan Request PIC menyimpan item, lokasi dan pemilik. Skenario: N01, N07.
- [ ] **CK02** Akses request/dokumen PIC lain ditolak. Skenario: A02.
- [ ] **CK03** Draft PO Jasa tersimpan, pengajuan memerlukan dokumen dan revisi PIC dapat diajukan ulang. Skenario: J01, J02, J13.
- [ ] **CK04** Konfirmasi hasil review dilakukan sebelum approval berikutnya. Skenario: J04, J05.
- [ ] **CK05** Pickup hanya saat material siap; penerimaan parsial dan sisa tidak ganda. Skenario: H01, H02, H06.
- [ ] **CK06** Progres dan evidence terbaca; PIC tidak menutup pekerjaan. Skenario: B01, B02, B03.
- [ ] **CK07** Biaya nyata mempunyai bukti, bukan pengakuan ulang stok. Skenario: R07.
- [ ] **CK08** Pembayaran/evaluasi dapat dibaca sesuai hak, tetapi tidak disimpan. Skenario: P05, E04.

## Kadep

- [ ] **CK09** Approval dibatasi pada departemen sendiri. Skenario: A03, N02.
- [ ] **CK10** PO NK dan pengambilan Request PIC mengikuti approval. Skenario: N04, N07.
- [ ] **CK11** Approval PO Jasa meneruskan ke DirOps, bukan Purchasing lama. Skenario: J06.
- [ ] **CK12** Pickup dapat disetujui/ditolak dengan riwayat dan catatan. Skenario: H03, H04.
- [ ] **CK39** Cabang PENDING, REVISI dan REJECT menjaga status, riwayat serta reservasi. Skenario: J14, J15, J18.

## Purchasing

- [ ] **CK13** Shipment To draft, pengajuan, cetak dan penerimaan komersil konsisten. Skenario: K01, K02, K03, K05, K06, K07, K08.
- [ ] **CK14** PO NK terbentuk sekali dan penerimaannya membuat batch yang tepat. Skenario: N03, N06.
- [ ] **CK15** Penyerahan hanya memproses item/sisa yang sah; penerusan timeout mengikuti batas waktu. Skenario: N08, N09, N10.
- [ ] **CK16** Review harga/vendor dan UPDATE menunggu konfirmasi PIC. Skenario: J03, J04, J05.
- [ ] **CK17** SPK diterbitkan setelah approval lengkap, tidak ganda. Skenario: J09.
- [ ] **CK18** Penerimaan Jasa memakai harga aktual dan reversal batch asal. Skenario: J10, J11, J12.
- [ ] **CK19** Persiapan pickup mereservasi, belum mengurangi stok. Skenario: H05, H07.
- [ ] **CK20** Revisi review kembali ke tahap yang tepat; revisi SPK tidak menimpa versi dan arsip bisa dicetak. Skenario: J15, J16, J17, R01, R03, R06.
- [ ] **CK21** BAST hanya setelah 100%, material lengkap, revisi diputuskan. Skenario: B04, B05.
- [ ] **CK22** Invoice diperbarui kumulatif, unik dan tepat sampai sen. Skenario: P01, P02, P03, P04.
- [ ] **CK23** Evaluasi setelah BAST mempunyai nilai valid dan identitas vendor benar. Skenario: E01, E02, E03.
- [ ] **CK24** Harga batch/adjustment dan reservasi lintas modul konsisten. Skenario: S01, S02, S03, X01, X02.

## Direktur Operasional

- [ ] **CK25** Akun/peran DirOps dikenali tanpa memberi hak Purchasing ke Keuangan. Skenario: A04.
- [ ] **CK26** Approval PO Jasa tidak menerima PENDING dan menuju Direktur. Skenario: J07.
- [ ] **CK27** Revisi data GA mendapat urutan DirOps lalu Direktur. Skenario: R04.
- [ ] **CK40** Revisi review kembali ke DirOps dan penolakan menjadi terminal. Skenario: J16, J18.

## Direktur

- [ ] **CK28** Approval komersil dan PO NK tercatat sekali dengan aktor benar. Skenario: K04, N05.
- [ ] **CK29** ACC PO Jasa menunggu penerbitan Purchasing. Skenario: J08.
- [ ] **CK30** Revisi jadwal dapat dikembalikan/disetujui tanpa mengubah data prematur. Skenario: R02.
- [ ] **CK31** Penolakan revisi menjaga jadwal/versi aktif. Skenario: R05.
- [ ] **CK32** Akses baca pembayaran/evaluasi tidak memberi hak simpan. Skenario: P05, E04.
- [ ] **CK41** Revisi review kembali ke Direktur dan penolakan tidak menerbitkan SPK. Skenario: J17, J18.

## Admin

- [ ] **CK33** Lingkungan, akun dan data fixture disiapkan hanya pada database uji. Skenario: A01.
- [ ] **CK34** Aktor sesi dan token/sesi tidak valid diuji. Skenario: A05, A06.
- [ ] **CK35** Saldo, batch dan harga snapshot bisa ditelusuri dari dokumen sumber. Skenario: S05.
- [ ] **CK36** Pekerjaan tertutup menolak aksi operasional baru. Skenario: B06.
- [ ] **CK37** Akun komersil terpisah dari stok nonkomersil. Skenario: X03.
- [ ] **CK38** Integritas asli, arsip bukti dan cleanup berdasarkan manifest selesai. Skenario: X04.

