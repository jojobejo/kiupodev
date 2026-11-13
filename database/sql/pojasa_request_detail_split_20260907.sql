ALTER TABLE `tbpo_jasa_request`
  ADD COLUMN `estimasi_total_jasa` decimal(18,2) NOT NULL DEFAULT 0.00 AFTER `estimasi_total`,
  ADD COLUMN `estimasi_total_bahan` decimal(18,2) NOT NULL DEFAULT 0.00 AFTER `estimasi_total_jasa`;

ALTER TABLE `tbpo_jasa_request_detail`
  ADD COLUMN `jenis_detail` enum('JASA','BAHAN') NOT NULL DEFAULT 'JASA' AFTER `kd_po_jasa`;

UPDATE `tbpo_jasa_request` r
LEFT JOIN (
  SELECT
    `kd_po_jasa`,
    SUM(CASE WHEN `jenis_detail` = 'JASA' THEN `total_harga` ELSE 0 END) AS total_jasa,
    SUM(CASE WHEN `jenis_detail` = 'BAHAN' THEN `total_harga` ELSE 0 END) AS total_bahan
  FROM `tbpo_jasa_request_detail`
  GROUP BY `kd_po_jasa`
) d ON d.`kd_po_jasa` = r.`kd_po_jasa`
SET
  r.`estimasi_total_jasa` = COALESCE(d.`total_jasa`, 0),
  r.`estimasi_total_bahan` = COALESCE(d.`total_bahan`, 0),
  r.`estimasi_total` = COALESCE(d.`total_jasa`, 0) + COALESCE(d.`total_bahan`, 0);
