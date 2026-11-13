-- PO Jasa Phase 5: vendor comparison, stock reservation, purchase draft, and SPK print support
-- Target: MariaDB 10.4+, CodeIgniter 3
-- Safety: additive/idempotent only; no DROP, DELETE, TRUNCATE, or destructive backfill.

SET NAMES utf8mb4;

ALTER TABLE `tbpo_jasa_request`
  ADD COLUMN IF NOT EXISTS `vendor_selection_reason` text DEFAULT NULL AFTER `kd_vendor_jasa`;

ALTER TABLE `tbpo_jasa_vendor`
  ADD COLUMN IF NOT EXISTS `source_type` varchar(20) NOT NULL DEFAULT 'MANUAL' AFTER `kd_vendor_jasa`,
  ADD COLUMN IF NOT EXISTS `source_supplier_code` varchar(25) DEFAULT NULL AFTER `source_type`,
  ADD COLUMN IF NOT EXISTS `vendor_type` varchar(20) NOT NULL DEFAULT 'VENDOR' AFTER `source_supplier_code`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_vendor_supplier_source` (`source_supplier_code`),
  ADD INDEX IF NOT EXISTS `idx_jasa_vendor_source_type` (`source_type`, `status_vendor`);

CREATE TABLE IF NOT EXISTS `tbpo_jasa_vendor_comparison` (
  `id_vendor_comparison` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint unsigned NOT NULL DEFAULT 1,
  `id_vendor_jasa` int(11) NOT NULL,
  `vendor_name_snapshot` varchar(180) NOT NULL,
  `quotation_no` varchar(100) DEFAULT NULL,
  `quotation_date` date DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `lead_time_days` int unsigned DEFAULT NULL,
  `offer_detail` text DEFAULT NULL,
  `id_dokumen` bigint unsigned DEFAULT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `selection_reason` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `version` int unsigned NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`id_vendor_comparison`),
  UNIQUE KEY `uk_jasa_vendor_comparison` (`kd_po_jasa`, `revision_no`, `id_vendor_jasa`),
  KEY `idx_jasa_vendor_comparison_selected` (`kd_po_jasa`, `revision_no`, `is_selected`),
  KEY `fk_jasa_vendor_comparison_document` (`id_dokumen`),
  KEY `fk_jasa_vendor_comparison_creator` (`created_by`),
  CONSTRAINT `fk_jasa_vendor_comparison_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_vendor_comparison_vendor` FOREIGN KEY (`id_vendor_jasa`) REFERENCES `tbpo_jasa_vendor` (`id_vendor_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_vendor_comparison_document` FOREIGN KEY (`id_dokumen`) REFERENCES `tbpo_jasa_dokumen` (`id_dokumen`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_vendor_comparison_creator` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_vendor_comparison_updater` FOREIGN KEY (`updated_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tbpo_jasa_vendor_comparison`
  ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `selection_reason`,
  ADD COLUMN IF NOT EXISTS `archived_by` int(11) DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `archived_at` datetime DEFAULT NULL AFTER `archived_by`,
  ADD COLUMN IF NOT EXISTS `archive_reason` text DEFAULT NULL AFTER `archived_at`,
  ADD INDEX IF NOT EXISTS `idx_jasa_vendor_comparison_active` (`kd_po_jasa`, `revision_no`, `is_active`);

ALTER TABLE `tbpo_jasa_stock_allocation`
  ADD COLUMN IF NOT EXISTS `allocation_token` char(36) DEFAULT NULL AFTER `expired_at`,
  ADD COLUMN IF NOT EXISTS `release_token` char(36) DEFAULT NULL AFTER `released_at`,
  ADD COLUMN IF NOT EXISTS `release_reason` text DEFAULT NULL AFTER `release_token`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_allocation_token` (`allocation_token`),
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_allocation_release_token` (`release_token`),
  ADD INDEX IF NOT EXISTS `idx_jasa_allocation_material_status` (`id_material`, `status_allocation`);

ALTER TABLE `tbpo_jasa_draft_pembelian`
  ADD COLUMN IF NOT EXISTS `revision_no` smallint unsigned NOT NULL DEFAULT 1 AFTER `kd_po_jasa`,
  ADD COLUMN IF NOT EXISTS `version` int unsigned NOT NULL DEFAULT 1 AFTER `status_draft`,
  ADD COLUMN IF NOT EXISTS `idempotency_token` char(36) DEFAULT NULL AFTER `version`,
  ADD COLUMN IF NOT EXISTS `cancelled_by` int(11) DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `cancelled_at` datetime DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN IF NOT EXISTS `cancel_reason` text DEFAULT NULL AFTER `cancelled_at`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_draft_origin` (`kd_po_jasa`, `id_material`),
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_draft_token` (`idempotency_token`),
  ADD INDEX IF NOT EXISTS `idx_jasa_draft_material_status` (`id_material`, `status_draft`);
