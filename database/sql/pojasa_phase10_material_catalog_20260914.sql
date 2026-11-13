-- PO Jasa Phase 10: katalog material dan usulan barang PIC.
-- Additive/idempotent migration for MariaDB 10.4+.
SET NAMES utf8mb4;

ALTER TABLE `tbpo_barang_nk`
  ADD COLUMN IF NOT EXISTS `status_master` varchar(12) NOT NULL DEFAULT 'ACTIVE' AFTER `descnk`,
  ADD INDEX IF NOT EXISTS `idx_barang_nk_status_name` (`status_master`, `nama_barang`(100));

CREATE TABLE IF NOT EXISTS `tbpo_jasa_material_usulan` (
  `id_usulan_barang` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_usulan` varchar(35) NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint unsigned NOT NULL DEFAULT 1,
  `nama_barang_input` varchar(180) NOT NULL,
  `deskripsi_input` text DEFAULT NULL,
  `satuan_input` varchar(40) DEFAULT NULL,
  `status_usulan` varchar(35) NOT NULL DEFAULT 'SUBMITTED',
  `kd_barang_hasil` varchar(25) DEFAULT NULL,
  `id_brg_nk_hasil` int(11) DEFAULT NULL,
  `catatan_purchasing` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_usulan_barang`),
  UNIQUE KEY `uk_jasa_material_usulan_kode` (`kode_usulan`),
  KEY `idx_jasa_material_usulan_work` (`status_usulan`, `created_at`),
  KEY `idx_jasa_material_usulan_request` (`kd_po_jasa`, `revision_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tbpo_jasa_material`
  ADD COLUMN IF NOT EXISTS `reference_type` varchar(12) NOT NULL DEFAULT 'MANUAL' AFTER `id_brg_nk`,
  ADD COLUMN IF NOT EXISTS `id_usulan_barang` bigint unsigned DEFAULT NULL AFTER `reference_type`,
  ADD COLUMN IF NOT EXISTS `kd_barang_snapshot` varchar(25) DEFAULT NULL AFTER `id_usulan_barang`,
  ADD INDEX IF NOT EXISTS `idx_jasa_material_reference` (`reference_type`, `id_brg_nk`, `id_usulan_barang`);
