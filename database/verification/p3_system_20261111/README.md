# Gate P3 PO Jasa lokal

Cakupan dan keputusan: [laporan P3](../../../docs/P3_SYSTEM_CLOSURE_20261111.md).

Artefak hasil:

- `verification.json`: keputusan, hash kode/DDL yang diuji, dan hasil integritas.
- `before-checksums.json`: baseline kedua database asli serta database uji.
- `http-regression.log`: lima kelompok pengujian HTTP PO Jasa serta pembersihan fixture.
- `phase3-check.json`, `phase9-check.json`: 24 dan 8 pemeriksaan CLI, dengan rollback.
- `p2-regression.log`: sembilan kelompok pengujian P2 setelah perubahan model stok.
- `schema-smoke.json`: 96 tabel dan 1 view kosong; gate P2/P3 serta replay DDL lulus.

`check_workflow.py` membuat pengguna, material, PO Jasa, dokumen dan transaksi sintetis melalui aplikasi HTTP terisolasi pada `127.0.0.1:18761`. SQL fixture dikunci ke `kiucoid_kiupo_merge_p0_20261110`. Konfigurasi aplikasi testing menunjuk database yang sama, dengan cookie `kiupo_p1_test`. Pengujian bertanggal 11 November 2026; sebelum pengulangan di tanggal lain, selaraskan tanggal fixture dan periode pembersihan counter. Counter PO Jasa diasumsikan kosong sebelum pengujian; baseline tidak kosong tidak direset otomatis.

Pembersihan fixture memakai kode pemilik acak, menghapus detail anak sebelum induk sesuai foreign key, dan menghapus hanya unggahan sintetis yang tercatat pada request tersebut. Jangan arahkan server HTTP pengujian ke database asli.

`check_schema.py` membuat database lokal sementara dengan nama acak, memuat struktur tanpa data, menjalankan upgrade/fondasi berulang, memeriksa gate P2/P3, dan menghapus database yang dibuatnya. Contoh dari root repository:

```sh
python3 database/verification/p3_system_20261111/check_schema.py
python3 database/verification/p2_system_20261111/check_integrity.py \
  database/verification/p3_system_20261111/before-checksums.json
```

Uji HTTP dan CLI sudah dijalankan melalui aplikasi terisolasi `/tmp/kiupo-p1-http.it7zn4` dengan kode aktif yang sama dengan workspace. Pemeriksaan CLI dipanggil dengan `CI_ENV=testing` dan tanpa opsi `--persist`.
