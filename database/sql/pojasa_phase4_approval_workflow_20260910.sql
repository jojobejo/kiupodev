-- PO Jasa Phase 4: approval and revision workflow
-- Target: MariaDB 10.4+, CodeIgniter 3
-- Safety: additive/idempotent only; no DROP, DELETE, TRUNCATE, or destructive backfill.

SET NAMES utf8mb4;

ALTER TABLE `tbpo_jasa_request`
  ADD COLUMN IF NOT EXISTS `purchasing_note` text DEFAULT NULL AFTER `reviewed_at_purchasing`,
  ADD COLUMN IF NOT EXISTS `purchasing_revision_saved_at` datetime DEFAULT NULL AFTER `purchasing_note`,
  ADD COLUMN IF NOT EXISTS `purchasing_revision_status` varchar(40) DEFAULT NULL AFTER `purchasing_revision_saved_at`,
  ADD INDEX IF NOT EXISTS `idx_jasa_request_workflow` (`status`, `departemen`, `deleted_at`, `id_po_jasa`);

ALTER TABLE `tbpo_jasa_revisi`
  ADD INDEX IF NOT EXISTS `idx_jasa_revisi_open` (`kd_po_jasa`, `revision_no`, `submitted_at`);
