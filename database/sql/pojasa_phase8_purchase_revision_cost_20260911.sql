-- PO Jasa Phase 8: secure workflow documents, purchase integration, SPK revisions, and actual-cost governance
-- Target: MariaDB 10.4+, CodeIgniter 3
-- Safety: additive/idempotent. Existing business rows are not deleted or overwritten.

SET NAMES utf8mb4;

ALTER TABLE `tbpo_po_nk`
  ADD COLUMN IF NOT EXISTS `source_module` varchar(30) DEFAULT NULL AFTER `acc_with_kadep`,
  ADD COLUMN IF NOT EXISTS `source_reference` varchar(120) DEFAULT NULL AFTER `source_module`,
  ADD COLUMN IF NOT EXISTS `source_spk_no` varchar(40) DEFAULT NULL AFTER `source_reference`,
  ADD COLUMN IF NOT EXISTS `source_spk_version` smallint unsigned DEFAULT NULL AFTER `source_spk_no`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_ponk_source_reference` (`source_module`, `source_reference`),
  ADD INDEX IF NOT EXISTS `idx_ponk_source_spk` (`source_module`, `source_spk_no`);

ALTER TABLE `tbpo_detail_po_nk`
  ADD COLUMN IF NOT EXISTS `source_module` varchar(30) DEFAULT NULL AFTER `gbr_produk`,
  ADD COLUMN IF NOT EXISTS `source_reference` varchar(120) DEFAULT NULL AFTER `source_module`,
  ADD COLUMN IF NOT EXISTS `source_material_id` bigint unsigned DEFAULT NULL AFTER `source_reference`,
  ADD COLUMN IF NOT EXISTS `source_draft_id` bigint unsigned DEFAULT NULL AFTER `source_material_id`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_detponk_source_reference` (`source_module`, `source_reference`),
  ADD INDEX IF NOT EXISTS `idx_detponk_source_material` (`source_module`, `source_material_id`);

ALTER TABLE `tbpo_jasa_draft_pembelian`
  MODIFY COLUMN `status_draft` varchar(40) NOT NULL DEFAULT 'DRAFT',
  ADD COLUMN IF NOT EXISTS `submitted_po_nk_id` int(12) DEFAULT NULL AFTER `cancel_reason`,
  ADD COLUMN IF NOT EXISTS `submitted_po_nk_code` varchar(25) DEFAULT NULL AFTER `submitted_po_nk_id`,
  ADD COLUMN IF NOT EXISTS `submitted_detail_po_nk_id` int(11) DEFAULT NULL AFTER `submitted_po_nk_code`,
  ADD COLUMN IF NOT EXISTS `submitted_at` datetime DEFAULT NULL AFTER `submitted_detail_po_nk_id`,
  ADD INDEX IF NOT EXISTS `idx_jasa_draft_submission` (`submitted_po_nk_id`, `status_draft`);

ALTER TABLE `tbpo_jasa_biaya_aktual`
  MODIFY COLUMN `id_dokumen_bukti` bigint unsigned DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `source_type` varchar(30) NOT NULL DEFAULT 'MANUAL' AFTER `cost_source`,
  ADD COLUMN IF NOT EXISTS `record_type` varchar(20) NOT NULL DEFAULT 'MANUAL' AFTER `source_type`,
  ADD COLUMN IF NOT EXISTS `record_status` varchar(40) NOT NULL DEFAULT 'DICATAT' AFTER `record_type`,
  ADD COLUMN IF NOT EXISTS `id_scope` bigint unsigned DEFAULT NULL AFTER `record_status`,
  ADD COLUMN IF NOT EXISTS `id_material` bigint unsigned DEFAULT NULL AFTER `id_scope`,
  ADD COLUMN IF NOT EXISTS `id_spk` bigint unsigned DEFAULT NULL AFTER `id_material`,
  ADD COLUMN IF NOT EXISTS `spk_version_no` smallint unsigned DEFAULT NULL AFTER `id_spk`,
  ADD COLUMN IF NOT EXISTS `source_po_nk_id` int(12) DEFAULT NULL AFTER `spk_version_no`,
  ADD COLUMN IF NOT EXISTS `source_po_nk_code` varchar(25) DEFAULT NULL AFTER `source_po_nk_id`,
  ADD COLUMN IF NOT EXISTS `source_detail_po_nk_id` int(11) DEFAULT NULL AFTER `source_po_nk_code`,
  ADD COLUMN IF NOT EXISTS `source_receipt_reference` varchar(120) DEFAULT NULL AFTER `source_detail_po_nk_id`,
  ADD COLUMN IF NOT EXISTS `source_document_reference` varchar(255) DEFAULT NULL AFTER `source_receipt_reference`,
  ADD COLUMN IF NOT EXISTS `qty_actual` decimal(18,2) DEFAULT NULL AFTER `source_document_reference`,
  ADD COLUMN IF NOT EXISTS `unit_price_actual` decimal(18,2) DEFAULT NULL AFTER `qty_actual`,
  ADD COLUMN IF NOT EXISTS `parent_cost_id` bigint unsigned DEFAULT NULL AFTER `unit_price_actual`,
  ADD COLUMN IF NOT EXISTS `row_version` int unsigned NOT NULL DEFAULT 1 AFTER `parent_cost_id`,
  ADD COLUMN IF NOT EXISTS `updated_by` int(11) DEFAULT NULL AFTER `row_version`,
  ADD COLUMN IF NOT EXISTS `updated_at` datetime DEFAULT NULL AFTER `updated_by`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_cost_receipt_reference` (`source_receipt_reference`),
  ADD INDEX IF NOT EXISTS `idx_jasa_cost_record_status` (`kd_po_jasa`, `record_type`, `record_status`),
  ADD INDEX IF NOT EXISTS `idx_jasa_cost_material` (`id_material`, `record_status`);

SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='tbpo_jasa_biaya_aktual' AND CONSTRAINT_NAME='fk_jasa_cost_scope')=0,
  'ALTER TABLE `tbpo_jasa_biaya_aktual` ADD CONSTRAINT `fk_jasa_cost_scope` FOREIGN KEY (`id_scope`) REFERENCES `tbpo_jasa_scope` (`id_scope`) ON UPDATE CASCADE ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='tbpo_jasa_biaya_aktual' AND CONSTRAINT_NAME='fk_jasa_cost_material')=0,
  'ALTER TABLE `tbpo_jasa_biaya_aktual` ADD CONSTRAINT `fk_jasa_cost_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='tbpo_jasa_biaya_aktual' AND CONSTRAINT_NAME='fk_jasa_cost_spk')=0,
  'ALTER TABLE `tbpo_jasa_biaya_aktual` ADD CONSTRAINT `fk_jasa_cost_spk` FOREIGN KEY (`id_spk`) REFERENCES `tbpo_jasa_spk` (`id_spk`) ON UPDATE CASCADE ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_purchase_submission` (
  `id_submission` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_spk` bigint unsigned NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `spk_version_no` smallint unsigned NOT NULL,
  `source_reference` varchar(120) NOT NULL,
  `id_po_nk` int(12) NOT NULL,
  `kd_po_nk` varchar(25) NOT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'PROSES_PEMBELIAN',
  `idempotency_token` char(36) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_submission`),
  UNIQUE KEY `uk_jasa_purchase_source` (`source_reference`),
  UNIQUE KEY `uk_jasa_purchase_spk_version` (`kd_po_jasa`, `spk_version_no`),
  UNIQUE KEY `uk_jasa_purchase_po_header` (`id_po_nk`),
  UNIQUE KEY `uk_jasa_purchase_token` (`idempotency_token`),
  KEY `idx_jasa_purchase_request_status` (`kd_po_jasa`, `status`),
  CONSTRAINT `fk_jasa_purchase_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_purchase_spk` FOREIGN KEY (`id_spk`) REFERENCES `tbpo_jasa_spk` (`id_spk`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_purchase_creator` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_purchase_submission_detail` (
  `id_submission_detail` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_submission` bigint unsigned NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_material` bigint unsigned NOT NULL,
  `id_draft_pembelian` bigint unsigned NOT NULL,
  `id_detail_po_nk` int(11) NOT NULL,
  `qty_submitted` decimal(18,2) NOT NULL,
  `estimated_unit_price` decimal(18,2) NOT NULL,
  `qty_received` decimal(18,2) NOT NULL DEFAULT 0.00,
  `fulfillment_status` varchar(30) NOT NULL DEFAULT 'DIAJUKAN',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_submission_detail`),
  UNIQUE KEY `uk_jasa_purchase_draft` (`id_draft_pembelian`),
  UNIQUE KEY `uk_jasa_purchase_detail_po` (`id_detail_po_nk`),
  KEY `idx_jasa_purchase_material` (`id_material`, `fulfillment_status`),
  CONSTRAINT `fk_jasa_purchase_detail_header` FOREIGN KEY (`id_submission`) REFERENCES `tbpo_jasa_purchase_submission` (`id_submission`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_purchase_detail_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_purchase_detail_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_purchase_detail_draft` FOREIGN KEY (`id_draft_pembelian`) REFERENCES `tbpo_jasa_draft_pembelian` (`id_draft_pembelian`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_purchase_receipt` (
  `id_purchase_receipt` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_submission_detail` bigint unsigned NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_material` bigint unsigned NOT NULL,
  `id_detail_po_nk` int(11) NOT NULL,
  `receipt_reference` varchar(120) NOT NULL,
  `record_type` varchar(20) NOT NULL DEFAULT 'RECEIPT',
  `reversal_of_id` bigint unsigned DEFAULT NULL,
  `qty_received` decimal(18,2) NOT NULL,
  `unit_price_actual` decimal(18,2) NOT NULL,
  `total_actual` decimal(18,2) NOT NULL,
  `on_hand_date` date NOT NULL,
  `document_reference` varchar(255) DEFAULT NULL,
  `idempotency_token` char(36) NOT NULL,
  `processed_by` int(11) NOT NULL,
  `processed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_purchase_receipt`),
  UNIQUE KEY `uk_jasa_purchase_receipt_ref` (`receipt_reference`),
  UNIQUE KEY `uk_jasa_purchase_receipt_token` (`idempotency_token`),
  UNIQUE KEY `uk_jasa_purchase_receipt_reversal` (`reversal_of_id`),
  KEY `idx_jasa_purchase_receipt_detail` (`id_submission_detail`, `on_hand_date`),
  CONSTRAINT `fk_jasa_receipt_submission_detail` FOREIGN KEY (`id_submission_detail`) REFERENCES `tbpo_jasa_purchase_submission_detail` (`id_submission_detail`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_po_receipt_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_receipt_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_receipt_reversal` FOREIGN KEY (`reversal_of_id`) REFERENCES `tbpo_jasa_purchase_receipt` (`id_purchase_receipt`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_receipt_processor` FOREIGN KEY (`processed_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_change` (
  `id_spk_change` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_spk` bigint unsigned NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `from_version_no` smallint unsigned NOT NULL,
  `change_type` varchar(20) NOT NULL,
  `old_snapshot_json` longtext NOT NULL,
  `new_snapshot_json` longtext NOT NULL,
  `reason` text NOT NULL,
  `quantity_impact` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cost_impact` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(40) NOT NULL,
  `current_stage` varchar(30) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_name` varchar(120) NOT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submit_token` char(36) NOT NULL,
  `supersedes_change_id` bigint unsigned DEFAULT NULL,
  `final_decision_by` int(11) DEFAULT NULL,
  `final_decision_at` datetime DEFAULT NULL,
  `final_note` text DEFAULT NULL,
  `row_version` int unsigned NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_spk_change`),
  UNIQUE KEY `uk_jasa_spk_change_token` (`submit_token`),
  KEY `idx_jasa_spk_change_work` (`kd_po_jasa`, `status`, `current_stage`),
  CONSTRAINT `fk_jasa_spk_change_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_spk_change_spk` FOREIGN KEY (`id_spk`) REFERENCES `tbpo_jasa_spk` (`id_spk`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_spk_change_requester` FOREIGN KEY (`requested_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_spk_change_supersedes` FOREIGN KEY (`supersedes_change_id`) REFERENCES `tbpo_jasa_spk_change` (`id_spk_change`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_spk_change_decider` FOREIGN KEY (`final_decision_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_change_item` (
  `id_change_item` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_spk_change` bigint unsigned NOT NULL,
  `entity_type` varchar(20) NOT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `field_name` varchar(60) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `quantity_impact` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cost_impact` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_change_item`),
  KEY `idx_jasa_change_item_header` (`id_spk_change`, `entity_type`, `entity_id`),
  CONSTRAINT `fk_jasa_change_item_header` FOREIGN KEY (`id_spk_change`) REFERENCES `tbpo_jasa_spk_change` (`id_spk_change`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_change_approval` (
  `id_change_approval` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_spk_change` bigint unsigned NOT NULL,
  `approval_stage` varchar(30) NOT NULL,
  `action` varchar(10) NOT NULL,
  `from_status` varchar(40) NOT NULL,
  `to_status` varchar(40) NOT NULL,
  `actor_id` int(11) NOT NULL,
  `actor_code` varchar(25) NOT NULL,
  `actor_role` varchar(30) NOT NULL,
  `note` text DEFAULT NULL,
  `action_token` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_change_approval`),
  UNIQUE KEY `uk_jasa_change_approval_token` (`action_token`),
  KEY `idx_jasa_change_approval_header` (`id_spk_change`, `created_at`),
  CONSTRAINT `fk_jasa_change_approval_header` FOREIGN KEY (`id_spk_change`) REFERENCES `tbpo_jasa_spk_change` (`id_spk_change`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_change_approval_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_cost_audit` (
  `id_cost_audit` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_biaya_aktual` bigint unsigned NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `event_type` varchar(30) NOT NULL,
  `before_json` longtext DEFAULT NULL,
  `after_json` longtext DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `actor_id` int(11) NOT NULL,
  `actor_role` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cost_audit`),
  KEY `idx_jasa_cost_audit_cost` (`id_biaya_aktual`, `created_at`),
  CONSTRAINT `fk_jasa_cost_audit_cost` FOREIGN KEY (`id_biaya_aktual`) REFERENCES `tbpo_jasa_biaya_aktual` (`id_biaya_aktual`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_cost_audit_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_cost_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_purchase_adjustment` (
  `id_adjustment` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_spk_change` bigint unsigned NOT NULL,
  `id_submission_detail` bigint unsigned DEFAULT NULL,
  `adjustment_type` varchar(30) NOT NULL,
  `before_json` longtext DEFAULT NULL,
  `after_json` longtext DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_adjustment`),
  KEY `idx_jasa_adjustment_request` (`kd_po_jasa`, `status`),
  CONSTRAINT `fk_jasa_adjustment_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_adjustment_change` FOREIGN KEY (`id_spk_change`) REFERENCES `tbpo_jasa_spk_change` (`id_spk_change`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_adjustment_submission` FOREIGN KEY (`id_submission_detail`) REFERENCES `tbpo_jasa_purchase_submission_detail` (`id_submission_detail`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_adjustment_creator` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
