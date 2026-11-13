# Verifikasi penutupan P2 struktur/sistem

Keputusan dan cakupan: [laporan P2](../../../docs/P2_SYSTEM_CLOSURE_20261111.md).

- `verification.json`: hasil gate, checksum data, dan hash kode/struktur yang diuji.
- `http-regression.log`: sembilan kelompok skenario HTTP yang lulus dan pembersihan fixture.
- `schema-smoke.json`: pembuatan ulang struktur kosong, nol data, dan penghapusan database sementara.
- `before-checksums.json`: baseline semua tabel yang telah ada pada kedua sumber asli dan database uji.
- `check_schema.py`: membangun database lokal sementara dengan nama acak, menjalankan fondasi dan baseline DDL dua kali, memeriksa gate P2, lalu menghapus database yang dibuatnya sendiri. Tidak mengimpor data.
- `check_workflow.py` dan `http_support.py`: menguji aplikasi melalui HTTP di `127.0.0.1:18761`, membuat fixture sintetis hanya di `kiucoid_kiupo_merge_p0_20261110`, dan membersihkannya dalam blok finally.
- `check_integrity.py`: membandingkan checksum isi tabel terhadap baseline.

Uji HTTP sudah dijalankan melalui aplikasi terisolasi `/tmp/kiupo-p1-http.it7zn4`, dengan kode aktif yang sama dengan workspace dan konfigurasi database testing yang secara eksplisit menunjuk database uji lokal. Router menetapkan lingkungan testing. Nama cookie sesi uji adalah `kiupo_p1_test`.

Contoh pemeriksaan struktur dan integritas dari root repository:

```sh
python3 database/verification/p2_system_20261111/check_schema.py
python3 database/verification/p2_system_20261111/check_integrity.py \
  database/verification/p2_system_20261111/before-checksums.json
```

Untuk menjalankan ulang `check_workflow.py`, gunakan server aplikasi uji terisolasi dengan konfigurasi yang sama. Jangan menunjuk server pengujian ke salah satu database asli. Perubahan fixture dapat menaikkan counter AUTO_INCREMENT; pemeriksaan integritas membandingkan isi seluruh tabel yang telah ada.
