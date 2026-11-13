ALTER TABLE `tbpo_jasa_request_detail`
  ADD COLUMN `harga_nyata` decimal(18,2) NOT NULL DEFAULT 0.00 AFTER `total_harga`,
  ADD COLUMN `keterangan_purchasing` text DEFAULT NULL AFTER `harga_nyata`;
