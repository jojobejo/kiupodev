# Verifikasi P5 — 12 November 2026

Hasil akhir: **PASS; P5 dapat ditutup untuk struktur/sistem lokal.** Lihat [laporan penutupan](../../../docs/P5_SYSTEM_CLOSURE_20261112.md).

- `results.json`: ringkasan jumlah pengujian.
- `http-workflow.log`: 13 kelompok integrasi P3/P4/P5.
- `p2-regression.log`: 9 kelompok regresi Request PIC, PO nonkomersil dan stok.
- `cli-phase6.json`: 23 pemeriksaan eksekusi, stok keluar dan BAST.
- `cli-phase8.json`: 32 pemeriksaan integrasi pembelian, revisi data/biaya dan versi SPK.
- `empty-schema.json`: 98 tabel + 1 view kosong, gate P2–P5, replay DDL, tanpa referensi lintas database.
- `before-checksums.json` dan `integrity.json`: isi kedua database asli dan seluruh tabel uji yang sudah ada tetap sama.

`check_workflow.py` menjalankan fixture P3 serta tahap P4 dengan tambahan skenario P5. Pengguna dan data mempunyai penanda acak P5T; cleanup child-first pada `finally` mencakup audit evaluasi baru. Alur pickup menjadi syarat konfirmasi material pada fixture P3. Fixture P4 menyelesaikan sisa pickup sebelum BAST. Fixture unit CLI Phase6 memulai konfirmasi dari pickup siap; approval pickup penuh diuji lewat HTTP. Ekspektasi sumber material CLI Phase8 disesuaikan dengan kontrak backend yang aktif.

Sebelum pengujian ulang, siapkan aplikasi HTTP CodeIgniter **testing** terisolasi pada `127.0.0.1:18761`, dengan konfigurasi database yang secara eksplisit menunjuk `kiucoid_kiupo_merge_p0_20261110` melalui socket lokal XAMPP. Jangan memakai konfigurasi database aplikasi default. Pada sesi ini aplikasi terisolasi ada di `/tmp/kiupo-p1-http.it7zn4`, dengan controller/model/view tersambung ke workspace. Server pengujian dihentikan setelah verifikasi.

Jalankan dari root workspace:

```sh
python3 database/verification/p5_system_20261112/check_workflow.py
python3 database/verification/p2_system_20261111/check_workflow.py
python3 database/verification/p5_system_20261112/check_schema.py
python3 database/verification/p2_system_20261111/check_integrity.py database/verification/p5_system_20261112/before-checksums.json
```

Baseline checksum di sini membuktikan keadaan pada sesi P5. Untuk sesi pengujian baru, ambil baseline baru agar perubahan data lokal yang disengaja setelah sesi ini tidak disalahartikan sebagai perubahan akibat pengujian.

`check_schema.py` membuat nama database sementara acak dengan prefix khusus, mengimpor struktur tanpa data serta upgrade P5 dua kali, menjalankan gate P2–P5, memeriksa semua tabel kosong, lalu menghapus database yang dibuatnya. DDL upgrade P5 hanya diterapkan pada database uji; tidak ada migrasi data dari database asli.
