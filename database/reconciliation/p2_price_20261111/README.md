# Rekonsiliasi harga P2 lokal

Tahap berikutnya sudah menyelesaikan enam batch tambahan; sisa kini 72 barang. Gunakan [runner dan hasil penelusuran terbaru](../p2_origin_20261111/README.md) untuk verifikasi keadaan terkini. Runner pada direktori ini memeriksa jumlah sisa baseline tahap harga sebelumnya.

Hasil dan keputusan penutupan: [laporan harga P2](../../../docs/P2_PRICE_CLOSURE_20261111.md).

`manifest.json` memuat 101 batch baseline, keputusan untuk setiap batch, serta bukti detail/header dari kedua sumber untuk 12 penerapan harga. `unresolved.json` memuat sisa 77 barang; harga kandidat di dalamnya belum disetujui untuk batch tersebut. `verification.json` mencatat pemeriksaan independen sesudah penerapan dan uji rollback.

`P2_price_reconcile.php` disimpan sebagai artefak rekonsiliasi, di luar controller aplikasi aktif. Runner memerlukan CLI, `ENVIRONMENT=testing`, database tepat `kiucoid_kiupo_merge_p0_20261110`, dan manifest. Sebelum perubahan, runner mencocokkan ulang isi sumber, membuat snapshot di database uji, lalu menerapkan harga dan audit dalam satu transaksi. Sumber asli hanya dibaca. Pemanggilan ulang setelah COMPLETE memverifikasi hasil tanpa menulis ulang.

Runner sudah dijalankan dari aplikasi CLI uji terpisah `/tmp/kiupo-p2-final.9HzXXE`. Contoh verifikasi pada aplikasi uji yang sama:

```sh
CI_ENV=testing \
P2_PRICE_MANIFEST=/Applications/XAMPP/xamppfiles/htdocs/kiupobaru/database/reconciliation/p2_price_20261111/manifest.json \
/Applications/XAMPP/xamppfiles/bin/php \
  -d mysqli.default_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock \
  /tmp/kiupo-p2-final.9HzXXE/index.php p2_price_reconcile verify
```

Metode `exercise` menguji alokasi QHEA001 dan penolakan QCDR001 dalam transaksi yang selalu dibatalkan. Backup harga bukan instruksi untuk menimpa database; pemulihan harus mempertimbangkan transaksi yang terjadi sesudah snapshot.
