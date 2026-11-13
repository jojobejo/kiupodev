# Gate P4 lokal — 12 November 2026

Keputusan dan cakupan: [laporan penutupan P4](../../../docs/P4_SYSTEM_CLOSURE_20261112.md).

- `verification.json`: keputusan, hasil gate/integritas dan hash kode/DDL yang diuji.
- `before-checksums.json`: baseline kedua database asli dan 133 tabel yang sudah ada di database uji.
- `http-regression.log`: lima kelompok regresi P3 dan tiga kelompok P4, beserta pembersihan fixture.
- `phase6-check.json`: 23 pemeriksaan model eksekusi/BAST, dengan rollback.
- `p2-regression.log`: sembilan kelompok regresi P2.
- `schema-smoke.json`: 97 tabel + 1 view kosong, replay DDL dan kontrak P2/P3/P4.
- `ui-script-check.json`: pemeriksaan sintaks JavaScript dan token UUID.

`check_workflow.py` memperluas fixture HTTP P3 yang sama dengan progres, BAST, dan pembayaran. Tanggal/periode fixture disesuaikan ke 12 November 2026. Helper menggunakan server terisolasi di `127.0.0.1:18761`, konfigurasi database testing `kiucoid_kiupo_merge_p0_20261110`, dan cookie `kiupo_p1_test`. Counter PO Jasa harus kosong sebelum pengujian; skrip menolak mulai bila tidak kosong. Jangan arahkan server ke database asli. Untuk pengulangan di tanggal lain, selaraskan tanggal/periode fixture dengan kalender aplikasi.

Pembersihan fixture menggunakan kode pengguna acak dan mengikuti foreign key; mencakup audit pembayaran baru. Berkas unggahan yang dihapus hanya berkas sintetis milik request uji. Aplikasi terisolasi yang digunakan adalah `/tmp/kiupo-p1-http.it7zn4`, dengan kode aktif yang sama dengan workspace.

Pemeriksaan struktur memakai database lokal sementara, tanpa data, lalu menghapus database yang dibuatnya sendiri:

```sh
python3 database/verification/p4_system_20261112/check_schema.py
python3 database/verification/p2_system_20261111/check_integrity.py \
  database/verification/p4_system_20261112/before-checksums.json
```

`p4_pojasa_structure_20261112.sql` adalah upgrade struktur; baseline pada `database/schema/` digunakan untuk membuat database kosong. Keduanya tidak mengatasi duplikasi invoice/data historis. Penyesuaian historis dilakukan saat pekerjaan migrasi nanti.
