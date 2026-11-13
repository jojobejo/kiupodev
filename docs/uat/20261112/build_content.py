"""Maintain one authoritative UAT scenario/checklist dataset. No database writes."""
from pathlib import Path
import json

HERE=Path(__file__).parent
cases=[]
def c(id,module,role,kind,fixture,pre,steps,expected):
    cases.append(dict(id=id,module=module,role=role,kind=kind,fixture=fixture,pre=pre,steps=steps,expected=expected))

c('A01','Akses','Admin','Prasyarat','ENV','Prasyarat lingkungan selesai','1. Buka URL UAT lokal. 2. Cocokkan label lingkungan dan koneksi dengan Admin. 3. Catat versi kode.','Aplikasi menggunakan database uji yang ditetapkan. Kedua database asli tidak menjadi target penulisan.')
c('A02','Akses','PIC','Negatif','AKUN','A01','1. Login PIC-A. 2. Buka request milik PIC-B dan dokumennya melalui tautan yang diberikan Admin.','Data pihak lain ditolak atau tidak ditemukan. Tidak ada isi dokumen atau aksi perubahan yang terbuka.')
c('A03','Akses','Kadep','Negatif','AKUN','A01','1. Login Kadep-IT. 2. Buka dan coba putuskan request GA.','Kadep beda departemen ditolak. Status, aktor approval dan catatan transaksi tetap.')
c('A04','Akses','Direktur Operasional','Negatif','AKUN','A01','1. Login akun level 6 di departemen Direktur Operasional. 2. Buka antrean PO Jasa. 3. Ulangi dengan akun level 2 Keuangan biasa.','Akun DirOps dikenali. Akun level 2 Keuangan tidak memperoleh peran Purchasing pada PO Jasa.')
c('A05','Akses','Purchasing','Negatif','AKUN','A01','1. Gunakan sesi yang sudah logout. 2. Coba simpan melalui halaman lama. 3. Admin bantu uji permintaan tanpa token CSRF untuk PO Jasa.','Sesi/token yang tidak valid ditolak. Tidak ada transaksi atau approval baru.')
c('A06','Akses','Admin','Integrasi','AKUN','A01','1. Login peran berbeda pada profil browser terpisah. 2. Lakukan satu approval dan buka riwayatnya.','Aktor dan departemen pada audit sesuai akun yang melakukan aksi, bukan nama pemilik request atau teks formulir.')

c('K01','PO komersil','Purchasing','Positif','K-BASE','A01','1. Pilih supplier UAT dan dua barang. 2. Pilih Shipment To A. 3. Simpan draft, keluar halaman lalu buka ulang.','Dua item dan Shipment To A tetap tersimpan. Belum ada PO yang diajukan.')
c('K02','PO komersil','Purchasing','Negatif','K-CABANG','K01','1. Pada salinan draft, kosongkan Shipment To. 2. Coba ajukan.','Pengajuan ditolak. Item draft tetap tersedia untuk diperbaiki.')
c('K03','PO komersil','Purchasing','Positif','K-BASE','K01','1. Ajukan 2 × Rp10.000 dan 3 × Rp5.000. 2. Gunakan PPN fixture 11%. 3. Catat nomor PO.','Satu header dan dua detail. Nilai dasar Rp35.000, PPN fixture Rp3.850, status ON PROGRESS. Draft terkait dibersihkan.')
c('K04','PO komersil','Direktur','Positif','K-BASE','K03','1. Buka detail PO. 2. ACC. 3. Muat ulang dan coba aksi yang sama lagi.','Status ACC DIREKTUR, aktor sesuai sesi. Approval/catatan tidak menjadi dua kali.')
c('K05','PO komersil','Purchasing','Positif','K-BASE','K04','1. Konfirmasi pembelian menjadi ON DELIVERY. 2. Ulangi tombol yang sama.','Status ON DELIVERY. Pengulangan tidak menghasilkan perubahan atau mutasi tambahan.')
c('K06','PO komersil','Purchasing','Positif','K-BASE','K05','1. Cetak internal, supplier Include, supplier Exclude dan cetakan kompatibilitas. 2. Bandingkan dengan draft.','Tiap cetakan memuat satu header, dua detail, supplier yang dipilih dan Shipment To A. Tidak mencampur alamat B atau menggandakan detail.')
c('K07','PO komersil','Purchasing','Positif','K-BASE','K06','1. Konfirmasi barang diterima. 2. Ulangi konfirmasi. 3. Periksa riwayat mutasi dengan Admin.','Status DONE. Dua mutasi masuk akun 11411, total 5 unit, masing-masing hanya satu kali.')
c('K08','PO komersil','Purchasing','Negatif','K-CABANG','K01','1. Simpan Shipment To pada draft satu item. 2. Hapus item terakhir. 3. Buat ulang draft baru.','Item dan pilihan shipment draft lama dibersihkan. Draft baru tidak mewarisi Shipment To tanpa pilihan baru.')

c('N01','Request PIC dan PO NK','PIC','Positif','N-BELI','A01','1. Buat Request PIC dua detail, qty 1 dan 2. 2. Isi lokasi/keterangan. 3. Ajukan dan buka ulang.','Satu kode Request PIC, dua detail dan pemilik/lokasi benar. Pengajuan ulang tanpa draft tidak membentuk header baru.')
c('N02','Request PIC dan PO NK','Kadep','Positif','N-BELI','N01','1. Login Kadep GA. 2. ACC Request PIC GA. 3. Ulangi keputusan.','Status ON PROGRESS - ACC KADEP. Catatan approval tidak berulang.')
c('N03','Request PIC dan PO NK','Purchasing','Positif','N-BELI','N02','1. Rencanakan pembelian 2 unit @Rp1.000 dan 3 unit @Rp2.000. 2. Bentuk PO NK terkait request. 3. Muat ulang.','Satu PO NK, keterkaitan request/PIC/departemen benar. Nilai pembelian Rp8.000. Tidak ada PO ganda saat aksi diulang.')
c('N04','Request PIC dan PO NK','Kadep','Positif','N-BELI','N03','1. ACC PO NK dengan Kadep departemen terkait. 2. Purchasing lanjutkan pengajuan ke Direktur.','PO NK melewati ACC-KADEP lalu SEDANG DIAJUKAN. Akun Kadep lain tidak dapat menggantikan persetujuan ini.')
c('N05','Request PIC dan PO NK','Direktur','Positif','N-BELI','N04','1. ACC PO NK. 2. Purchasing ubah menjadi proses pembelian.','Status ACC DIREKTUR kemudian PROSES PEMBELIAN. Tidak ada stok masuk sebelum penerimaan.')
c('N06','Request PIC dan PO NK','Purchasing','Positif','N-BELI','N05','1. Konfirmasi penerimaan PO. 2. Buka ulang dan ulangi konfirmasi.','Dua mutasi 11511, total 5 unit. Batch terpisah sesuai detail/harga Rp1.000 dan Rp2.000. Tidak ada posting ganda.')
c('N07','Request PIC dan PO NK','PIC','Positif','N-BELI','N06','1. Ajukan pengambilan. 2. Kadep ACC pengambilan. 3. Purchasing serahkan qty 1 dan 2.','Request selesai setelah penyerahan. Total keluar 3 unit pada 11512, saldo tersisa 2. Pemilik dan lokasi detail terjaga.')
c('N08','Request PIC dan PO NK','Purchasing','Positif','N-SISA','N07','1. Pada fixture tersendiri dengan detail sudah diserahkan dan satu detail belum diserahkan, buka daftar pickup. 2. Serahkan hanya detail tersisa.','Daftar hanya menampilkan sisa. Detail lama tidak diposting ulang. Total keluar bertambah hanya sebesar sisa.')
c('N09','Request PIC dan PO NK','Purchasing','Integrasi','N-STOK','N06','1. Buat request baru 1 unit dari stok tersedia. 2. Selesaikan persetujuan dan pickup.','Tidak membuat PO pembelian baru. Satu mutasi keluar 1 unit. Jika memakai saldo 2 unit dari N07, saldo akhirnya 1.')

c('S01','Stok','Purchasing','Positif','S-ADJ','A01','1. Adjustment masuk 2 unit pada SKU kosong. 2. Buka histori batch.','Saldo 2 unit. Batch masuk berstatus PERLU_HARGA sampai ada harga berbukti fixture.')
c('S02','Stok','Purchasing','Negatif','S-ADJ','S01','1. Sebelum mengisi harga, coba adjustment keluar 1 unit.','Ditolak tanpa mengurangi stok, batch, atau membuat transaksi keluar.')
c('S03','Stok','Purchasing','Positif','S-ADJ','S02','1. Isi harga fixture Rp2.500 dengan alasan. 2. Adjustment keluar 1 unit. 3. Periksa batch/harga snapshot.','Saldo 1 unit. Keluar 11514 memakai snapshot Rp2.500. Alasan harga dan aktor tersimpan.')
c('S04','Stok','PIC','Negatif','S-ADJ','S01','1. Login PIC biasa. 2. Coba adjustment dan pengubahan harga batch.','Aksi ditolak. Hak stok tidak diperoleh hanya karena memiliki Request PIC.')
c('S05','Stok','Admin','Integrasi','N-BELI','N06','1. Cocokkan detail PO, mutasi, batch dan saldo tampilan. 2. Cocokkan kuantitas keluar dengan alokasi LIFO.','Jumlah batch/mutasi sesuai transaksi fixture. Harga snapshot tetap sesuai batch asal, bukan harga PO yang dibaca ulang.')

c('J01','PO Jasa','PIC','Positif','J-BASE','A01','1. Buat draft satu scope jasa dan material master qty 3. 2. Simpan lalu buka ulang.','Pemilik, spesifikasi master, kuantitas dan jadwal tetap. Harga persetujuan ditentukan review Purchasing, bukan input PIC.')
c('J02','PO Jasa','PIC','Negatif','J-BASE','J01','1. Pada draft tanpa dokumen, coba ajukan. 2. Unggah dokumen pendukung nyata lalu ajukan.','Tanpa dokumen ditolak. Setelah dokumen valid tersedia, status MENUNGGU_PURCHASING_AWAL dan notifikasi Purchasing muncul.')
c('J03','PO Jasa','Purchasing','Positif','J-BASE','J02','1. Review vendor manual fixture. 2. Scope 1 × Rp150.000 dan material 3 × Rp10.000. 3. Simpan review dan draft pembelian.','Estimasi Purchasing Rp180.000. Identitas vendor manual tersimpan pada request. Belum ada SPK atau mutasi stok.')
c('J04','PO Jasa','Purchasing','Positif','J-BASE','J03','1. Pilih UPDATE dan isi catatan. 2. PIC buka notifikasi dan hasil review.','Status MENUNGGU_KONFIRMASI_PIC. Pengajuan belum melompati konfirmasi PIC.')
c('J05','PO Jasa','PIC','Positif','J-BASE','J04','1. Setujui hasil review dengan catatan. 2. Purchasing SUBMIT.','Status berurutan MENUNGGU_PURCHASING lalu MENUNGGU_KADEP.')
c('J06','PO Jasa','Kadep','Positif','J-BASE','J05','1. ACC sebagai Kadep GA. 2. Cek aktor dan antrean berikutnya.','Status MENUNGGU_DIRUT_OPS. Aktor sesuai Kadep yang login. Tidak kembali ke tahap Purchasing lama.')
c('J07','PO Jasa','Direktur Operasional','Positif','J-BASE','J06','1. ACC sebagai DirOps. 2. Coba opsi PENDING pada salinan request pada tahap ini.','ACC meneruskan ke MENUNGGU_DIREKTUR. PENDING tidak menjadi aksi yang sah untuk DirOps.')
c('J08','PO Jasa','Direktur','Positif','J-BASE','J07','1. ACC request. 2. Buka ulang sebelum Purchasing menerbitkan.','Status MENUNGGU_PENERBITAN_PURCHASING. SPK belum terbit otomatis oleh ACC Direktur.')
c('J09','PO Jasa','Purchasing','Positif','J-BASE','J08','1. TERBITKAN. 2. Buka SPK dan PO pembelian material yang terbentuk. 3. Ulangi aksi.','Satu nomor SPK, satu keterkaitan pembelian aktif yang sesuai sumbernya. Status SPK_TERBIT. Tidak ada nomor/header ganda.')
c('J10','PO Jasa','Purchasing','Positif','J-BASE','J09','1. Terima material 1 unit @Rp9.000. 2. Terima sisa 2 unit @Rp11.000. 3. Periksa biaya dan stok.','Total masuk 3 unit, biaya otomatis Rp31.000. Batch harga Rp9.000 dan Rp11.000 terpisah. Detail menjadi ON_HAND.')
c('J11','PO Jasa','Purchasing','Negatif','J-CABANG','J10','1. Coba terima lebih dari sisa. 2. Ulangi penerimaan tersimpan dengan data sama. 3. Admin bantu uji token sama dengan harga berbeda.','Kelebihan dan konflik token ditolak. Replay data identik tidak menambah stok, dokumen penerimaan atau biaya.')
c('J12','PO Jasa','Purchasing','Integrasi','J-RETUR','J09','1. Pada salinan sebelum pickup, terima 1 unit @Rp9.000 dan 2 unit @Rp11.000. 2. Reverse penerimaan pertama 1 unit.','Keluar koreksi 11514 memakai batch asal Rp9.000. Saldo 2 unit, biaya neto Rp22.000. Koreksi bersifat tambahan dan diaudit.')

c('H01','Pickup PO Jasa','PIC','Negatif','J-BELUMSIAP','J09','1. Coba ajukan pickup saat pembelian belum ON_HAND atau material manual belum tertaut master.','Pengajuan ditolak. Tidak ada header/detail pickup setengah tersimpan.')
c('H02','Pickup PO Jasa','PIC','Positif','J-BASE','J10','1. Ajukan pickup material. 2. Muat ulang dan ulangi permintaan yang sama.','Satu permintaan aktif MENUNGGU_ACC_KADEP. Permintaan berulang yang identik tidak membuat header ganda.')
c('H03','Pickup PO Jasa','Kadep','Negatif','J-PICKREJ','H02','1. Tolak salinan pickup tanpa catatan, lalu isi catatan penolakan. 2. PIC ajukan ulang.','Penolakan tanpa catatan ditolak. Penolakan tercatat; pengajuan ulang membuat riwayat baru tanpa menghapus penolakan lama.')
c('H04','Pickup PO Jasa','Kadep','Positif','J-BASE','H02','1. ACC pickup GA. 2. Purchasing buka antrean persiapan.','Status MENUNGGU_PERSIAPAN_PURCHASING. Kadep departemen lain tidak dapat menyetujui.')
c('H05','Pickup PO Jasa','Purchasing','Positif','J-BASE','H04','1. Tandai material siap diambil. 2. Cek notifikasi PIC dan saldo.','Status SIAP_DIAMBIL. Material pembelian masuk reservasi sesuai kebutuhan. Saldo fisik tetap 3, belum ada 11512.')
c('H06','Pickup PO Jasa','PIC','Positif','J-BASE','H05','1. Konfirmasi diterima 1 unit. 2. Buka ulang lalu terima sisa 2 unit. 3. Ulangi konfirmasi terakhir.','Saldo 3 ke 2 ke 0. Hanya penerimaan PIC memicu 11512. Setelah lengkap status DITERIMA_PIC; replay tidak menggandakan mutasi.')
c('H07','Pickup PO Jasa','Purchasing','Negatif','J-PICKLOCK','H05','1. Pada salinan SIAP_DIAMBIL, coba lepas reservasi dan ajukan perubahan qty material SPK.','Pelepasan/perubahan material ditolak selama handover berjalan. Reservasi, qty dan versi SPK tetap.')

c('R01','Revisi SPK','Purchasing','Positif','J-JADWAL','J09','1. Ajukan jadwal baru dan alasan. 2. Buka SPK aktif sebelum keputusan.','Jadwal aktif belum berubah. Satu pengajuan menunggu Direktur dan menunjuk versi asal SPK.')
c('R02','Revisi SPK','Direktur','Positif','J-JADWAL','R01','1. Minta REVISI dengan catatan. 2. Purchasing ajukan ulang. 3. Direktur ACC.','Riwayat revisi tersimpan. Versi SPK bertambah satu setelah ACC; versi lama dapat dibuka. Revisi jadwal saja tidak mengubah estimasi.')
c('R03','Revisi SPK','Purchasing','Positif','J-DATA','J09','1. Sebelum pickup, ajukan perubahan scope dan alasannya melalui revisi data. 2. Buka ringkasan dampak.','Perubahan belum diterapkan sebelum persetujuan. Dampak qty/biaya sesuai data yang diajukan, bukan mereset baris lain.')
c('R04','Revisi SPK','Direktur Operasional','Positif','J-DATA','R03','1. Pada request GA, DirOps ACC revisi data. 2. Direktur ACC. 3. Purchasing buka versi aktif dan arsip.','Urutan DirOps lalu Direktur. Versi bertambah tepat sekali. Cetak memuat scope, vendor, material dan angka versi yang benar.')
c('R05','Revisi SPK','Direktur','Negatif','J-REJT','R01','1. Tolak salinan revisi jadwal dengan alasan. 2. Buka SPK aktif.','Jadwal, estimasi dan versi aktif tidak berubah. Keputusan penolakan tersimpan.')
c('R06','Revisi SPK','Purchasing','Negatif','J-TERTUNDA','R01','1. Saat revisi jadwal tertunda, coba ajukan revisi data. 2. Pada salinan terbalik, coba revisi jadwal saat revisi data tertunda.','Kedua jalur saling mengunci pengajuan tertunda. Tidak ada dua perubahan yang menimpa versi asal yang sama.')
c('R07','Revisi SPK','PIC','Positif','J-BIAYA','J09','1. Catat biaya nyata dengan bukti sehingga melewati plafon fixture. 2. Purchasing verifikasi dan ajukan revisi biaya. 3. Selesaikan approval yang diwajibkan.','Biaya mempunyai bukti dan referensi scope/material. Biaya menunggu persetujuan revisi; plafon hanya berubah setelah persetujuan final.')

c('B01','Progres dan BAST','PIC','Positif','J-BASE','H06','1. Simpan progres 25% dengan aktivitas dan evidence. 2. Buka ulang.','Status ON_PROGRESS. Aktivitas lengkap dan bukti tetap dapat dibuka oleh pihak yang berwenang.')
c('B02','Progres dan BAST','PIC','Negatif','J-CABANG','B01','1. Pada salinan progres 25%, coba 10% dan 101%. 2. Coba tutup pekerjaan sebagai PIC.','Progres mundur/lebih dari 100% ditolak. PIC tidak dapat menetapkan DITUTUP.')
c('B03','Progres dan BAST','PIC','Positif','J-BASE','B01','1. Simpan progres 100% dan status selesai. 2. Purchasing buka halaman BAST dan Pembayaran.','Status SELESAI. Pekerjaan belum DITUTUP sampai Purchasing/Admin menyimpan BAST.')
c('B04','Progres dan BAST','Purchasing','Negatif','J-BASTGATE','B03','1. Pada salinan, sisakan material belum diterima PIC atau revisi SPK menunggu keputusan. 2. Coba simpan BAST.','BAST ditolak sampai seluruh material dan keputusan revisi selesai. Tidak ada cap waktu penutupan prematur.')
c('B05','Progres dan BAST','Purchasing','Positif','J-BASE','B03','1. Isi nomor, tanggal, penerima dan catatan BAST. 2. Simpan dan buka ulang. 3. Ulangi penyimpanan identik.','Status DITUTUP, satu BAST, cap waktu dan aktor Purchasing. Replay identik tidak membentuk BAST kedua.')
c('B06','Progres dan BAST','Admin','Negatif','J-BASE','B05','1. Coba buat progres, penerimaan, reversal atau revisi SPK baru pada request tertutup.','Aksi operasional baru ditolak. Status penutupan dan saldo tidak berubah. Tracking pembayaran tetap terpisah.')

c('P01','Pembayaran','Purchasing','Positif','J-BASE','J09','1. Catat invoice Rp100.000,50, dibayar 0. 2. Buka ulang.','Satu invoice BELUM BAYAR. Invoice tidak menutup atau membuka ulang pekerjaan.')
c('P02','Pembayaran','Purchasing','Positif','J-BASE','P01','1. Perbarui total dibayar kumulatif menjadi Rp30.000,25. 2. Kemudian Rp100.000,50.','Invoice tetap satu. Status PARTIAL lalu LUNAS, dengan riwayat tiga penyimpanan. Total dibayar adalah kumulatif, bukan ditambah dua kali.')
c('P03','Pembayaran','Purchasing','Negatif','J-INVNEG','P01','1. Coba bayar melebihi tagihan, nominal negatif, nomor invoice duplikat dan pembaruan dari halaman versi lama.','Data tidak valid/duplikat/versi usang ditolak. Nilai lama dan audit tidak berubah akibat upaya gagal.')
c('P04','Pembayaran','Purchasing','Positif','J-SEN','J09','1. Catat invoice Rp0,30 dan dibayar Rp0,10. 2. Periksa total dan sisa.','Sisa tepat Rp0,20 dan status PARTIAL. Tidak muncul selisih pembulatan.')
c('P05','Pembayaran','PIC','Negatif','J-BASE','P01','1. Pemilik PIC buka hasil invoice. 2. Coba mengubah pembayaran. 3. Ulangi sebagai Direktur.','Pihak berwenang dapat membaca, tetapi PIC/Direktur tidak dapat menyimpan pembayaran.')

c('E01','Evaluasi vendor','Purchasing','Negatif','J-EVALAWAL','J09','1. Coba evaluasi sebelum BAST menutup pekerjaan.','Ditolak. Belum ada evaluasi tersimpan atau nilai vendor sebelum DITUTUP dan BAST tersedia.')
c('E02','Evaluasi vendor','Purchasing','Positif','J-BASE','B05','1. Isi kualitas 5, waktu 4 dan biaya 3. 2. Simpan lalu buka ulang.','Nilai rata-rata 4,00 dihitung server. Vendor sesuai request/master; vendor manual menyimpan nama snapshot tanpa vendor fiktif.')
c('E03','Evaluasi vendor','Purchasing','Negatif','J-BASE','E02','1. Coba nilai 5,01, 0 dan kosong. 2. Coba perbarui dari halaman versi lama.','Nilai di luar 1–5 dan versi usang ditolak. Evaluasi yang sah tetap utuh.')
c('E04','Evaluasi vendor','PIC','Negatif','J-BASE','E02','1. PIC pemilik baca evaluasi. 2. Coba simpan sebagai PIC dan Direktur.','Hasil dapat dibaca sesuai akses. Hanya Purchasing/Admin dapat menyimpan evaluasi.')

c('X01','Integrasi','Purchasing','Integrasi','X-RESERVASI','H05','1. Reservasi 3 unit untuk PO Jasa. 2. Coba Request PIC lain mengambil 1 unit dan adjustment keluar 1 unit.','Keduanya ditolak saat stok bebas nol. Status, detail, batch dan mutasi Request PIC tidak berubah sebagian.')
c('X02','Integrasi','Purchasing','Integrasi','X-RESERVASI','X01','1. Tambahkan 1 unit bebas dengan harga fixture Rp2.500. 2. Retry Request PIC yang tertahan. 3. Selesaikan pickup PO Jasa.','Request PIC memakai 1 unit bebas pada harga Rp2.500. Reservasi Jasa 3 unit tetap utuh. Setelah pickup Jasa, stok akhir nol.')
c('X03','Integrasi','Admin','Integrasi','X-AKUN','K07','1. Sediakan SKU yang sama di master nonkomersil fixture. 2. Periksa saldo setelah penerimaan komersil K07.','Mutasi 11411 tidak menambah saldo nonkomersil pada SKU yang sama. Saldo nonkomersil tetap nol bila belum ada 11511/11513.')
c('X04','Integrasi','Admin','Integrasi','ENV','Semua skenario selesai','1. Cocokkan kode run, mutasi dan bukti hasil. 2. Verifikasi checksum sumber asli. 3. Bersihkan hanya fixture milik run setelah bukti diarsipkan.','Kedua database asli tidak berubah. Fixture run lain dan data historis tidak dihapus. Hasil UAT serta daftar temuan tetap diarsipkan.')

c('N10','Request PIC dan PO NK','Purchasing','Integrasi','N-TIMEOUT','N06 pada salinan','1. Catat waktu catatan BARANG DI TERIMA - ADMIN pada salinan request. 2. Tanpa update Kadep, periksa tombol penerusan sebelum dan sesudah lima menit. 3. Purchasing teruskan proses melalui tombol yang tersedia.','Sebelum ambang waktu, penerusan ditolak/tidak tersedia. Sesudah ambang tanpa update Kadep, status MENUNGGU PENYERAHAN BARANG dan catatan penerusan tersimpan. Belum ada stok keluar. Jangan mengubah jam server.')
c('J13','PO Jasa','PIC','Positif','J-PICREVISI','J04 pada salinan','1. PIC minta revisi hasil review dengan catatan. 2. Perbaiki draft dan dokumen yang diperlukan. 3. Ajukan ulang.','Status REVISI_PIC lalu MENUNGGU_PURCHASING_AWAL. Riwayat review terdahulu terjaga dan Purchasing menerima pengajuan ulang.')
c('J14','PO Jasa','Kadep','Positif','J-PENDING','J05 pada salinan','1. Kadep pilih PENDING. 2. PIC coba edit/ajukan. 3. Kadep lanjut ACC.','Saat PENDING_KADEP, PIC tidak dapat mengubah request. ACC berikutnya menuju MENUNGGU_DIRUT_OPS; tidak memerlukan tahap Purchasing lama.')
c('J15','PO Jasa','Kadep','Positif','J-KADEPREVISI','J05 pada salinan','1. Kadep minta REVISI dengan catatan. 2. Purchasing coba SUBMIT sebelum menyimpan. 3. Simpan review yang diperbaiki lalu SUBMIT.','Status REVISI_PURCHASING_KADEP, bukan revisi PIC. Submit sebelum simpan ditolak. Review tersimpan pada revisi baru, kemudian kembali MENUNGGU_KADEP.')
c('J16','PO Jasa','Direktur Operasional','Positif','J-OPSREVISI','J06 pada salinan','1. DirOps minta REVISI dengan catatan. 2. Purchasing simpan review perbaikan dan SUBMIT.','Status REVISI_PURCHASING_DIROPS kemudian kembali MENUNGGU_DIRUT_OPS. Baris revisi lama tidak ditimpa dan tidak melompati persetujuan ulang DirOps.')
c('J17','PO Jasa','Direktur','Positif','J-DIRREVISI','J07 pada salinan','1. Direktur minta REVISI dengan catatan. 2. Purchasing simpan review perbaikan dan SUBMIT.','Status REVISI_PURCHASING_DIRUT kemudian kembali MENUNGGU_DIREKTUR. Versi request bertambah dan SPK belum terbit.')
c('J18','PO Jasa','Kadep','Negatif','J-REJECT','J05/J06/J07 pada tiga salinan','1. Tolak tiga salinan pada tahap Kadep, DirOps dan Direktur memakai akun masing-masing. 2. Periksa reservasi, draft pembelian dan upaya melanjutkan request.','Masing-masing menjadi DITOLAK_KADEP, DITOLAK_DIRUT_OPS dan DITOLAK_DIREKTUR. Alasan/aktor tersimpan, reservasi dilepas, draft dibatalkan dan tidak terbit SPK.')

module_order={p:i for i,p in enumerate(['A','K','N','S','J','H','R','B','P','E','X'])}
cases.sort(key=lambda row:(module_order[row['id'][0]],int(row['id'][1:])))

checks=[]
def ck(role,action,refs):
    checks.append(dict(id=f'CK{len(checks)+1:02}',role=role,action=action,refs=refs))
for role,rows in {
 'PIC':[
 ('Draft dan pengajuan Request PIC menyimpan item, lokasi dan pemilik.','N01,N07'),
 ('Akses request/dokumen PIC lain ditolak.','A02'),
 ('Draft PO Jasa tersimpan, pengajuan memerlukan dokumen dan revisi PIC dapat diajukan ulang.','J01,J02,J13'),
 ('Konfirmasi hasil review dilakukan sebelum approval berikutnya.','J04,J05'),
 ('Pickup hanya saat material siap; penerimaan parsial dan sisa tidak ganda.','H01,H02,H06'),
 ('Progres dan evidence terbaca; PIC tidak menutup pekerjaan.','B01,B02,B03'),
 ('Biaya nyata mempunyai bukti, bukan pengakuan ulang stok.','R07'),
 ('Pembayaran/evaluasi dapat dibaca sesuai hak, tetapi tidak disimpan.','P05,E04')],
 'Kadep':[
 ('Approval dibatasi pada departemen sendiri.','A03,N02'),
 ('PO NK dan pengambilan Request PIC mengikuti approval.','N04,N07'),
 ('Approval PO Jasa meneruskan ke DirOps, bukan Purchasing lama.','J06'),
 ('Pickup dapat disetujui/ditolak dengan riwayat dan catatan.','H03,H04')],
 'Purchasing':[
 ('Shipment To draft, pengajuan, cetak dan penerimaan komersil konsisten.','K01,K02,K03,K05,K06,K07,K08'),
 ('PO NK terbentuk sekali dan penerimaannya membuat batch yang tepat.','N03,N06'),
 ('Penyerahan hanya memproses item/sisa yang sah; penerusan timeout mengikuti batas waktu.','N08,N09,N10'),
 ('Review harga/vendor dan UPDATE menunggu konfirmasi PIC.','J03,J04,J05'),
 ('SPK diterbitkan setelah approval lengkap, tidak ganda.','J09'),
 ('Penerimaan Jasa memakai harga aktual dan reversal batch asal.','J10,J11,J12'),
 ('Persiapan pickup mereservasi, belum mengurangi stok.','H05,H07'),
 ('Revisi review kembali ke tahap yang tepat; revisi SPK tidak menimpa versi dan arsip bisa dicetak.','J15,J16,J17,R01,R03,R06'),
 ('BAST hanya setelah 100%, material lengkap, revisi diputuskan.','B04,B05'),
 ('Invoice diperbarui kumulatif, unik dan tepat sampai sen.','P01,P02,P03,P04'),
 ('Evaluasi setelah BAST mempunyai nilai valid dan identitas vendor benar.','E01,E02,E03'),
 ('Harga batch/adjustment dan reservasi lintas modul konsisten.','S01,S02,S03,X01,X02')],
 'Direktur Operasional':[
 ('Akun/peran DirOps dikenali tanpa memberi hak Purchasing ke Keuangan.','A04'),
 ('Approval PO Jasa tidak menerima PENDING dan menuju Direktur.','J07'),
 ('Revisi data GA mendapat urutan DirOps lalu Direktur.','R04')],
 'Direktur':[
 ('Approval komersil dan PO NK tercatat sekali dengan aktor benar.','K04,N05'),
 ('ACC PO Jasa menunggu penerbitan Purchasing.','J08'),
 ('Revisi jadwal dapat dikembalikan/disetujui tanpa mengubah data prematur.','R02'),
 ('Penolakan revisi menjaga jadwal/versi aktif.','R05'),
 ('Akses baca pembayaran/evaluasi tidak memberi hak simpan.','P05,E04')],
 'Admin':[
 ('Lingkungan, akun dan data fixture disiapkan hanya pada database uji.','A01'),
 ('Aktor sesi dan token/sesi tidak valid diuji.','A05,A06'),
 ('Saldo, batch dan harga snapshot bisa ditelusuri dari dokumen sumber.','S05'),
 ('Pekerjaan tertutup menolak aksi operasional baru.','B06'),
 ('Akun komersil terpisah dari stok nonkomersil.','X03'),
 ('Integritas asli, arsip bukti dan cleanup berdasarkan manifest selesai.','X04')]
}.items():
    for action,refs in rows:ck(role,action,refs)

ck('Kadep','Cabang PENDING, REVISI dan REJECT menjaga status, riwayat serta reservasi.','J14,J15,J18')
ck('Direktur Operasional','Revisi review kembali ke DirOps dan penolakan menjadi terminal.','J16,J18')
ck('Direktur','Revisi review kembali ke Direktur dan penolakan tidak menerbitkan SPK.','J17,J18')

prereqs=[
 ('PR01','Admin','Koneksi lokal','Database penulisan hanya kiucoid_kiupo_merge_p0_20261110. Verifikasi CI_ENV testing dan konfigurasi koneksi sebelum akun tester dipakai.'),
 ('PR02','Admin','URL dan versi','Tetapkan URL UAT lokal, commit/hash kode dan waktu mulai run. Server pengujian P6 sudah dihentikan; URL belum dianggap aktif.'),
 ('PR03','Admin','Akun per peran','Siapkan alias PIC-A/PIC-B, Kadep-GA/Kadep-IT, Purchasing, DirOps, Direktur, Admin dan Keuangan-negatif. Jangan memakai nama/password nyata di workbook.'),
 ('PR04','Admin','Akun biasa untuk uji negatif','Uji pembatas peran dengan akun biasa, bukan Admin. Admin dapat mempunyai kemampuan tambahan sehingga hasil negatif bisa salah.'),
 ('PR05','Admin','Fixture terisolasi','Buat kode run UAT-<tanggal>-<sesi>. Catat seluruh kode yang dibentuk. Cabang negatif/revisi memakai salinan request dan SKU tersendiri.'),
 ('PR06','Purchasing','Master dan dokumen','Sediakan supplier, dua alamat Shipment To, SKU/satuan/lokasi dan dokumen pendukung fixture. Harga hanya angka sintetis dalam paket, tidak diterapkan pada barang historis.'),
 ('PR07','Admin','Struktur dan baseline','Jalankan gate baca-saja P2–P6; simpan baseline baru sebelum UAT. Acuan aplikasi 98 tabel + 1 view; database uji 135 tabel termasuk tabel pendukung historis.'),
 ('PR08','Admin','Browser dan bukti','Gunakan profil browser terpisah untuk setiap akun. Tentukan folder bukti, printer/PDF dan penamaan ID kasus-kode run-waktu.'),
 ('PR09','Admin','Kriteria penerimaan','Koordinator dan perwakilan peran menyepakati kriteria penutupan yang diusulkan pada panduan sebelum eksekusi.'),
 ('PR10','Admin','Penjadwalan data','Hindari reset/runner otomatis P6 saat tester menyimpan fixture UAT. Runner P6 mengharuskan counter Jasa kosong dan mempunyai cleanup fixture sendiri.'),
 ('PR11','Admin','Pemulihan dan cleanup','Arsipkan bukti sebelum cleanup. Hapus hanya kode fixture dalam manifest run ini. Jangan rebuild histori, impor, TRUNCATE atau rekonsiliasi sumber asli.')]

fixtures=[
 ('K-BASE','Komersil item A','UAT-K-A',2,10000,'PCS','Nilai 20.000. Dua Shipment To A/B; harga dan PPN 11% adalah fixture.'),
 ('K-BASE','Komersil item B','UAT-K-B',3,5000,'PCS','Nilai 15.000; total dasar 35.000 dan PPN fixture 3.850.'),
 ('N-BELI','Pembelian NK detail A','UAT-N-A',2,1000,'PCS','Kebutuhan Request PIC detail A hanya 1 unit; sisanya stok.'),
 ('N-BELI','Pembelian NK detail B','UAT-N-B',3,2000,'PCS','Kebutuhan Request PIC detail B 2 unit. Total masuk 5, keluar 3, sisa 2.'),
 ('J-BASE','Scope jasa','UAT-J-SCOPE',1,150000,'LOT','Scope 150.000 + estimasi material 30.000 = plafon awal 180.000.'),
 ('J-BASE','Material kebutuhan','UAT-J-M',3,10000,'PCS','Satu SKU. Penerimaan pertama 1 @9.000, berikutnya 2 @11.000; biaya aktual 31.000.'),
 ('J-RETUR','Retur batch pertama','UAT-J-RET',1,9000,'PCS','Salinan mandiri sebelum pickup. Setelah reverse: saldo 2, biaya neto 22.000.'),
 ('S-ADJ','Adjustment masuk','UAT-S-ADJ',2,2500,'PCS','Awalnya PERLU_HARGA. Isi harga fixture, lalu keluar 1; saldo akhir 1.'),
 ('X-RESERVASI','Reservasi Jasa','UAT-X-M',3,11000,'PCS','Stok fisik 3, reservasi 3, stok bebas 0. Harga batch boleh fixture valid tersendiri.'),
 ('X-RESERVASI','Tambahan stok bebas','UAT-X-M',1,2500,'PCS','Request PIC lain boleh mengambil 1 setelah tambahan; reservasi 3 tetap.'),
 ('J-BASE','Invoice utama','UAT-INV',1,100000.50,'Invoice','Dibayar kumulatif 0, lalu 30.000,25, lalu 100.000,50.'),
 ('J-SEN','Invoice sen','UAT-INV-SEN',1,0.30,'Invoice','Dibayar 0,10; sisa 0,20.')]

ids={row['id'] for row in cases};assert len(ids)==len(cases)
for row in checks:assert set(row['refs'].split(','))<=ids,row
data={'prepared':'2026-11-12','cases':cases,'checks':checks,'prereqs':prereqs,'fixtures':fixtures,
      'roles':['PIC','Kadep','Purchasing','Direktur Operasional','Direktur','Admin'],
      'sources':['Laporan penutupan P6, 12 November 2026: 33 kelompok HTTP dan 210 pemeriksaan CLI.',
                 'Kontrak peran: pojasa_authorization_helper.php; status: pojasa_status_helper.php.',
                 'Kontrak BAST, invoice dan evaluasi: M_PojasaSettlement.php. Pickup dan revisi mengikuti gate P5/P6.',
                 'Angka fixture adalah contoh sintetis untuk UAT, bukan harga historis atau aturan pajak umum.']}
(HERE/'uat_cases.json').write_text(json.dumps(data,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'scenarios':len(cases),'role_checklists':len(checks),'prerequisites':len(prereqs),'roles':len(data['roles'])}))
