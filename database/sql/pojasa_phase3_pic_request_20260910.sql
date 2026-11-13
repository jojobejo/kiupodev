-- PO Jasa Phase 3: PIC request workspace
-- Target: MariaDB 10.4+, CodeIgniter 3
-- Safety: additive/idempotent only; no DROP, DELETE, TRUNCATE, or destructive backfill.

SET NAMES utf8mb4;

ALTER TABLE `tbpo_jasa_request`
  ADD COLUMN IF NOT EXISTS `vendor_usulan` varchar(180) DEFAULT NULL AFTER `kd_vendor_jasa`,
  ADD COLUMN IF NOT EXISTS `tgl_mulai_pekerjaan` date DEFAULT NULL AFTER `tgl_target`,
  ADD COLUMN IF NOT EXISTS `tgl_selesai_pekerjaan` date DEFAULT NULL AFTER `tgl_mulai_pekerjaan`,
  ADD COLUMN IF NOT EXISTS `catatan_pic` text DEFAULT NULL AFTER `tujuan_pekerjaan`,
  ADD COLUMN IF NOT EXISTS `edit_revision_no` smallint unsigned DEFAULT NULL AFTER `revision_no`,
  ADD COLUMN IF NOT EXISTS `draft_saved_at` datetime DEFAULT NULL AFTER `submitted_at_purchasing`,
  ADD COLUMN IF NOT EXISTS `submitted_at` datetime DEFAULT NULL AFTER `draft_saved_at`,
  ADD INDEX IF NOT EXISTS `idx_jasa_request_owner_deleted` (`kd_user`, `deleted_at`, `id_po_jasa`),
  ADD INDEX IF NOT EXISTS `idx_jasa_request_owner_status_date` (`kd_user`, `status`, `tgl_request`);

ALTER TABLE `tbpo_jasa_scope`
  ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `legacy_detail_id`,
  ADD COLUMN IF NOT EXISTS `archived_at` datetime DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `archived_by` int(11) DEFAULT NULL AFTER `archived_at`,
  ADD INDEX IF NOT EXISTS `idx_jasa_scope_revision_active` (`kd_po_jasa`, `revision_no`, `is_active`, `line_no`);

ALTER TABLE `tbpo_jasa_material`
  ADD COLUMN IF NOT EXISTS `revision_no` smallint unsigned NOT NULL DEFAULT 1 AFTER `kd_po_jasa`,
  ADD COLUMN IF NOT EXISTS `line_no` int unsigned NOT NULL DEFAULT 1 AFTER `revision_no`,
  ADD COLUMN IF NOT EXISTS `harga_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00 AFTER `satuan`,
  ADD COLUMN IF NOT EXISTS `total_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00 AFTER `harga_estimasi`,
  ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `created_by`,
  ADD COLUMN IF NOT EXISTS `archived_at` datetime DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `archived_by` int(11) DEFAULT NULL AFTER `archived_at`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_material_line` (`kd_po_jasa`, `revision_no`, `line_no`),
  ADD INDEX IF NOT EXISTS `idx_jasa_material_revision_active` (`kd_po_jasa`, `revision_no`, `is_active`, `line_no`);
