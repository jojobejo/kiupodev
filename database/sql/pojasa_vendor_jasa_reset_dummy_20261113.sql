-- One-time reset explicitly requested: remove old jasa vendor master and seed exactly three dummy vendors.
-- Keep historical names in request/comparison/evaluation snapshots; leave commercial suppliers untouched.
START TRANSACTION;
UPDATE tbpo_jasa_request r JOIN tbpo_vendor_jasa v ON v.kd_vendor_jasa=r.kd_vendor_jasa
SET r.vendor_usulan=COALESCE(NULLIF(r.vendor_usulan,''),v.nama_vendor), r.kd_vendor_jasa=NULL;
UPDATE tbpo_jasa_vendor_comparison c JOIN tbpo_vendor_jasa v ON v.id_vendor_jasa=c.id_vendor_jasa
SET c.vendor_name_snapshot=COALESCE(NULLIF(c.vendor_name_snapshot,''),v.nama_vendor),c.id_vendor_jasa=NULL;
UPDATE tbpo_jasa_evaluation e JOIN tbpo_vendor_jasa v ON v.kd_vendor_jasa=e.kd_vendor_jasa
SET e.vendor_name_snapshot=COALESCE(NULLIF(e.vendor_name_snapshot,''),v.nama_vendor),e.kd_vendor_jasa=NULL;
UPDATE tbpo_jasa_draft_pembelian SET id_vendor_jasa=NULL WHERE id_vendor_jasa IS NOT NULL;
DELETE FROM tbpo_vendor_jasa;
INSERT INTO tbpo_vendor_jasa
(kd_vendor_jasa,source_type,vendor_type,nama_vendor,nama_vendor_normalized,alamat_vendor,no_telpon,nama_pic,email,no_fax,status_vendor,created_by,created_name)
VALUES
('VJDUMMY01','MANUAL','VENDOR','PT Dummy Jasa Teknik','PT DUMMY JASA TEKNIK','Jl. Contoh Teknik No. 1, Jakarta','0215550101','Budi Contoh','teknik@example.com','0215550102','AKTIF','SYSTEM','Dummy vendor jasa'),
('VJDUMMY02','MANUAL','VENDOR','CV Dummy Mitra Bangunan','CV DUMMY MITRA BANGUNAN','Jl. Contoh Bangunan No. 2, Bandung','0225550201','Sari Contoh','bangunan@example.com','0225550202','AKTIF','SYSTEM','Dummy vendor jasa'),
('VJDUMMY03','MANUAL','VENDOR','PT Dummy Solusi Perawatan','PT DUMMY SOLUSI PERAWATAN','Jl. Contoh Perawatan No. 3, Surabaya','0315550301','Andi Contoh','perawatan@example.com','0315550302','AKTIF','SYSTEM','Dummy vendor jasa');
COMMIT;
