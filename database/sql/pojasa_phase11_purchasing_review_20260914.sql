-- PO Jasa Phase 11: review/perbaikan Purchasing pada workflow detail.
-- Additive/idempotent migration for MariaDB 10.4+.
SET NAMES utf8mb4;

ALTER TABLE `tbpo_jasa_scope`
  ADD COLUMN IF NOT EXISTS `harga_pembanding_purchasing` decimal(18,2) DEFAULT NULL AFTER `harga_estimasi`,
  ADD COLUMN IF NOT EXISTS `added_by_purchasing` tinyint(1) NOT NULL DEFAULT 0 AFTER `keterangan_purchasing`,
  ADD COLUMN IF NOT EXISTS `deleted_reason_purchasing` text DEFAULT NULL AFTER `archived_by`;

ALTER TABLE `tbpo_jasa_material`
  ADD COLUMN IF NOT EXISTS `harga_pembanding_purchasing` decimal(18,2) DEFAULT NULL AFTER `harga_estimasi`,
  ADD COLUMN IF NOT EXISTS `added_by_purchasing` tinyint(1) NOT NULL DEFAULT 0 AFTER `created_by`,
  ADD COLUMN IF NOT EXISTS `keterangan_purchasing` text DEFAULT NULL AFTER `harga_pembanding_purchasing`,
  ADD COLUMN IF NOT EXISTS `deleted_reason_purchasing` text DEFAULT NULL AFTER `archived_by`;
