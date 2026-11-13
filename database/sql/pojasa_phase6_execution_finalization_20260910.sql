-- PO Jasa Phase 6: execution, stock receipt hardening, actual cost, and finalization
-- Target: MariaDB 10.4+, CodeIgniter 3
-- Safety: additive/idempotent only; no DROP, DELETE, TRUNCATE, or automatic user creation.

SET NAMES utf8mb4;

ALTER TABLE `tbpo_jasa_request`
  ADD COLUMN IF NOT EXISTS `estimasi_disetujui` decimal(18,2) DEFAULT NULL AFTER `estimasi_total_bahan`,
  ADD COLUMN IF NOT EXISTS `closed_at` datetime DEFAULT NULL AFTER `completed_at`,
  ADD COLUMN IF NOT EXISTS `closed_by` int(11) DEFAULT NULL AFTER `closed_at`,
  ADD INDEX IF NOT EXISTS `idx_jasa_request_execution` (`status`, `completed_at`, `closed_at`);

ALTER TABLE `tbpo_jasa_progress`
  ADD COLUMN IF NOT EXISTS `aktivitas` text DEFAULT NULL AFTER `milestone`,
  ADD COLUMN IF NOT EXISTS `kendala` text DEFAULT NULL AFTER `aktivitas`,
  ADD COLUMN IF NOT EXISTS `tindak_lanjut` text DEFAULT NULL AFTER `kendala`,
  ADD COLUMN IF NOT EXISTS `id_dokumen_evidence` bigint unsigned DEFAULT NULL AFTER `catatan`,
  ADD COLUMN IF NOT EXISTS `created_by_user_id` int(11) DEFAULT NULL AFTER `id_dokumen_evidence`,
  ADD COLUMN IF NOT EXISTS `payload_hash` char(64) DEFAULT NULL AFTER `idempotency_token`,
  ADD INDEX IF NOT EXISTS `idx_jasa_progress_status_date` (`kd_po_jasa`, `status_progress`, `tgl_progress`),
  ADD INDEX IF NOT EXISTS `idx_jasa_progress_evidence` (`id_dokumen_evidence`),
  ADD INDEX IF NOT EXISTS `idx_jasa_progress_user` (`created_by_user_id`);

ALTER TABLE `tbpo_jasa_biaya_aktual`
  ADD COLUMN IF NOT EXISTS `cost_source` varchar(30) NOT NULL DEFAULT 'PEMBELIAN_BARU' AFTER `tgl_biaya`,
  ADD COLUMN IF NOT EXISTS `id_draft_pembelian` bigint unsigned DEFAULT NULL AFTER `id_dokumen_bukti`,
  ADD COLUMN IF NOT EXISTS `payload_hash` char(64) DEFAULT NULL AFTER `idempotency_token`,
  ADD INDEX IF NOT EXISTS `idx_jasa_biaya_source` (`kd_po_jasa`, `cost_source`, `tgl_biaya`),
  ADD INDEX IF NOT EXISTS `idx_jasa_biaya_draft` (`id_draft_pembelian`);

ALTER TABLE `tbpo_jasa_stock_receipt`
  ADD COLUMN IF NOT EXISTS `payload_hash` char(64) DEFAULT NULL AFTER `idempotency_token`;

-- Add nullable foreign keys only when they are not present yet.
SET @pojasa_fk_progress_doc := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tbpo_jasa_progress` ADD CONSTRAINT `fk_jasa_progress_document` FOREIGN KEY (`id_dokumen_evidence`) REFERENCES `tbpo_jasa_dokumen` (`id_dokumen`) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1')
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbpo_jasa_progress' AND CONSTRAINT_NAME = 'fk_jasa_progress_document'
);
PREPARE pojasa_stmt FROM @pojasa_fk_progress_doc; EXECUTE pojasa_stmt; DEALLOCATE PREPARE pojasa_stmt;

SET @pojasa_fk_progress_user := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tbpo_jasa_progress` ADD CONSTRAINT `fk_jasa_progress_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1')
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbpo_jasa_progress' AND CONSTRAINT_NAME = 'fk_jasa_progress_user'
);
PREPARE pojasa_stmt FROM @pojasa_fk_progress_user; EXECUTE pojasa_stmt; DEALLOCATE PREPARE pojasa_stmt;

SET @pojasa_fk_cost_draft := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tbpo_jasa_biaya_aktual` ADD CONSTRAINT `fk_jasa_biaya_draft` FOREIGN KEY (`id_draft_pembelian`) REFERENCES `tbpo_jasa_draft_pembelian` (`id_draft_pembelian`) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1')
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbpo_jasa_biaya_aktual' AND CONSTRAINT_NAME = 'fk_jasa_biaya_draft'
);
PREPARE pojasa_stmt FROM @pojasa_fk_cost_draft; EXECUTE pojasa_stmt; DEALLOCATE PREPARE pojasa_stmt;

SET @pojasa_fk_request_closed_user := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tbpo_jasa_request` ADD CONSTRAINT `fk_jasa_request_closed_user` FOREIGN KEY (`closed_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1')
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbpo_jasa_request' AND CONSTRAINT_NAME = 'fk_jasa_request_closed_user'
);
PREPARE pojasa_stmt FROM @pojasa_fk_request_closed_user; EXECUTE pojasa_stmt; DEALLOCATE PREPARE pojasa_stmt;

-- Director Operational level 6 is intentionally not created here.
-- Its setup remains an explicit prerequisite for workflow testing in an environment that needs this role.
