# Penelusuran asal batch P2 lokal

Lihat [laporan hasil](../../../docs/P2_BATCH_ORIGIN_20261111.md).

- `manifest.json`: 89 batch awal, keputusan dan ringkasan penelusuran setiap batch, serta bukti transaksi/detail/header/alokasi untuk enam harga yang diterapkan.
- `unresolved.json`: sisa 72 barang pada 83 batch.
- `qvit_trace.json`: jejak sumber dan keputusan pengguna untuk mempertahankan PERLU_HARGA.
- `source-checksums.json` dan `verification.json`: checksum baseline kedua sumber serta hasil pemeriksaan independen.
- `unit-label-correction.json`: rekaman koreksi label pada alasan QKAR003, dari buku menjadi bendel sesuai satuan 19. Angka, sumber, dan harga tidak berubah; hash manifest dan bukti audit diperbarui bersama labelnya.
- `P2_origin_reconcile.php`: artefak runner di luar controller aplikasi aktif. Runner hanya bekerja melalui CLI testing pada database tepat `kiucoid_kiupo_merge_p0_20261110`.

Runner memeriksa kembali sumber, mencadangkan empat tabel stok/LIFO di database uji, lalu menerapkan harga beserta audit dalam satu transaksi. Kedua database asli hanya dibaca. Runner sudah dijalankan melalui controller salinan pada aplikasi uji terpisah `/tmp/kiupo-p2-final.9HzXXE`.

Verifikasi pada aplikasi uji yang sama:

```sh
CI_ENV=testing \
P2_ORIGIN_MANIFEST=/Applications/XAMPP/xamppfiles/htdocs/kiupobaru/database/reconciliation/p2_origin_20261111/manifest.json \
/Applications/XAMPP/xamppfiles/bin/php \
  -d mysqli.default_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock \
  /tmp/kiupo-p2-final.9HzXXE/index.php p2_origin_reconcile verify
```

Metode `exercise` menguji pengeluaran QFLY001 dan penolakan QCDR001 dalam transaksi yang dibatalkan. Pemanggilan metode utama setelah COMPLETE memverifikasi hasil tanpa menerapkan harga lagi. Backup bukan instruksi pemulihan otomatis; perubahan sesudah snapshot harus diperhitungkan sebelum pemulihan.
