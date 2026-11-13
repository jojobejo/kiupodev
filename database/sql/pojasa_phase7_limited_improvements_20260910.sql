-- PO Jasa Phase 7: limited purchasing fixes and SPK schedule revision
-- Target: MariaDB 10.4+, CodeIgniter 3
-- Safety: additive only; no existing business row is deleted.

SET NAMES utf8mb4;

ALTER TABLE `tbpo_jasa_vendor_comparison`
  MODIFY COLUMN `id_vendor_jasa` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `manual_vendor_name` varchar(180) DEFAULT NULL AFTER `id_vendor_jasa`,
  ADD INDEX IF NOT EXISTS `idx_jasa_comparison_manual_vendor` (`kd_po_jasa`, `revision_no`, `manual_vendor_name`);

ALTER TABLE `tbpo_jasa_spk`
  ADD COLUMN IF NOT EXISTS `spk_version_no` smallint unsigned NOT NULL DEFAULT 1 AFTER `revision_no`;

ALTER TABLE `tbpo_jasa_progress`
  ADD COLUMN IF NOT EXISTS `spk_version_no` smallint unsigned DEFAULT NULL AFTER `revision_no`,
  ADD INDEX IF NOT EXISTS `idx_jasa_progress_spk_version` (`kd_po_jasa`, `spk_version_no`);

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_archive` (
  `id_spk_archive` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_spk_source` bigint unsigned NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `request_revision_no` smallint unsigned NOT NULL,
  `spk_version_no` smallint unsigned NOT NULL,
  `issued_by` int(11) NOT NULL,
  `issued_at` datetime NOT NULL,
  `snapshot_json` longtext NOT NULL,
  `template_version` varchar(30) NOT NULL,
  `archived_by` int(11) NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_spk_archive`),
  UNIQUE KEY `uk_jasa_spk_archive_version` (`kd_po_jasa`, `spk_version_no`),
  KEY `idx_jasa_spk_archive_number` (`no_spk`, `spk_version_no`),
  CONSTRAINT `fk_jasa_spk_archive_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_spk_archive_actor` FOREIGN KEY (`archived_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_revision` (
  `id_spk_revision` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `from_version_no` smallint unsigned NOT NULL,
  `old_start_date` date NOT NULL,
  `old_end_date` date NOT NULL,
  `new_start_date` date NOT NULL,
  `new_end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'MENUNGGU',
  `requested_by` int(11) NOT NULL,
  `requested_name` varchar(120) NOT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `decision_by` int(11) DEFAULT NULL,
  `decision_name` varchar(120) DEFAULT NULL,
  `decision_at` datetime DEFAULT NULL,
  `decision_note` text DEFAULT NULL,
  `submit_count` smallint unsigned NOT NULL DEFAULT 1,
  `row_version` int unsigned NOT NULL DEFAULT 1,
  `submit_token` char(36) NOT NULL,
  `decision_token` char(36) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_spk_revision`),
  UNIQUE KEY `uk_jasa_spk_revision_submit_token` (`submit_token`),
  UNIQUE KEY `uk_jasa_spk_revision_decision_token` (`decision_token`),
  KEY `idx_jasa_spk_revision_work` (`kd_po_jasa`, `status`, `id_spk_revision`),
  CONSTRAINT `fk_jasa_spk_revision_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_spk_revision_requester` FOREIGN KEY (`requested_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_spk_revision_decider` FOREIGN KEY (`decision_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
