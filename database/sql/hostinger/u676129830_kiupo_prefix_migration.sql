-- Target: u676129830_kiupo (dump dated 2026-09-22)
-- Purpose: preserve the attached database data while aligning its legacy tb_
--          table names with the tbpo_ names used by this application.
-- Run ONCE immediately after importing the attached dump, before all other
-- PO non-komersil / PO Jasa migration files.
--
-- This is a RENAME operation, not a copy or DELETE operation. Existing data,
-- primary keys, auto increments, and indexes are preserved.

SET FOREIGN_KEY_CHECKS = 0;

RENAME TABLE
  `tb_akun_tr` TO `tbpo_akun_tr`,
  `tb_barang` TO `tbpo_barang`,
  `tb_barang_nk` TO `tbpo_barang_nk`,
  `tb_barang_nk_lokasi` TO `tbpo_barang_nk_lokasi`,
  `tb_detail_po` TO `tbpo_detail_po`,
  `tb_detail_po_nk` TO `tbpo_detail_po_nk`,
  `tb_detail_req` TO `tbpo_detail_req`,
  `tb_diskon` TO `tbpo_diskon`,
  `tb_file_bukti_beli` TO `tbpo_file_bukti_beli`,
  `tb_file_nk` TO `tbpo_file_nk`,
  `tb_generateqrcode` TO `tbpo_generateqrcode`,
  `tb_generate_kd` TO `tbpo_generate_kd`,
  `tb_generate_kd_ponk` TO `tbpo_generate_kd_ponk`,
  `tb_kat_br` TO `tbpo_kat_br`,
  `tb_notetemplate` TO `tbpo_notetemplate`,
  `tb_note_barang` TO `tbpo_note_barang`,
  `tb_note_direktur` TO `tbpo_note_direktur`,
  `tb_note_pembelian` TO `tbpo_note_pembelian`,
  `tb_nt_tmp_pembelian` TO `tbpo_nt_tmp_pembelian`,
  `tb_po` TO `tbpo_po`,
  `tb_po_nk` TO `tbpo_po_nk`,
  `tb_ratings` TO `tbpo_ratings`,
  `tb_req_masterbarang` TO `tbpo_req_masterbarang`,
  `tb_req_nk` TO `tbpo_req_nk`,
  `tb_satuan` TO `tbpo_satuan`,
  `tb_set_note` TO `tbpo_set_note`,
  `tb_set_tax` TO `tbpo_set_tax`,
  `tb_sosialisasi` TO `tbpo_sosialisasi`,
  `tb_suplier` TO `tbpo_suplier`,
  `tb_tmp_diskon` TO `tbpo_tmp_diskon`,
  `tb_tmp_item` TO `tbpo_tmp_item`,
  `tb_tmp_item_nk` TO `tbpo_tmp_item_nk`,
  `tb_tmp_note_barang` TO `tbpo_tmp_note_barang`,
  `tb_tmp_tax` TO `tbpo_tmp_tax`,
  `tb_tracking_po` TO `tbpo_tracking_po`,
  `tb_transaksi` TO `tbpo_transaksi`,
  `tb_transaksi_tmp` TO `tbpo_transaksi_tmp`,
  `tb_transaksi_trashbin` TO `tbpo_transaksi_trashbin`,
  `tb_user` TO `tbpo_user`;

-- The dump's stock view references the old table names. Recreate it without
-- the source server DEFINER so it can be imported by the Hostinger DB user.
DROP VIEW IF EXISTS `v_stockbarangnk`;
CREATE VIEW `v_stockbarangnk` AS
SELECT
  x.kode_barangs,
  x.kode_barang,
  x.nama_barang,
  x.deskripsi,
  x.gbr_barang,
  x.nama_lokasi,
  COALESCE(x.qty_in, 0) + COALESCE(x.adjqty_in, 0) AS qty_in,
  COALESCE(x.qty_out, 0) + COALESCE(x.adjqty_out, 0) AS qty_out,
  COALESCE(x.qty_in, 0) + COALESCE(x.adjqty_in, 0)
    - (COALESCE(x.qty_out, 0) + COALESCE(x.adjqty_out, 0)) AS qty_ready,
  x.id_s AS id_satuan,
  x.satuan,
  x.id_brg_nk,
  x.kat_barang
FROM (
  SELECT
    a.kd_barang AS kode_barangs,
    a.kd_br_adm AS kode_barang,
    a.nama_barang,
    a.descnk AS deskripsi,
    l.nama_lokasi,
    b.id_satuan AS id_s,
    b.nm_satuan AS satuan,
    a.gbr_barang,
    a.id_brg_nk,
    a.kat_barang,
    (SELECT SUM(d.tr_qty) FROM tbpo_transaksi d WHERE d.kd_barang = a.kd_barang AND d.kd_akun = '11512') AS qty_out,
    (SELECT SUM(d.tr_qty) FROM tbpo_transaksi d WHERE d.kd_barang = a.kd_barang AND d.kd_akun = '11514') AS adjqty_out,
    (SELECT SUM(e.tr_qty) FROM tbpo_transaksi e WHERE e.kd_barang = a.kd_barang AND e.kd_akun = '11511') AS qty_in,
    (SELECT SUM(e.tr_qty) FROM tbpo_transaksi e WHERE e.kd_barang = a.kd_barang AND e.kd_akun = '11513') AS adjqty_in
  FROM tbpo_barang_nk a
  JOIN tbpo_satuan b ON b.id_satuan = a.satuan
  JOIN tbpo_kat_br c ON c.kd_kat = a.kat_barang
  LEFT JOIN tbpo_barang_nk_lokasi l ON l.id_lokasi = a.kd_lokasi
  GROUP BY a.kd_barang
) AS x;

SET FOREIGN_KEY_CHECKS = 1;
