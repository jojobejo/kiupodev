-- PO Jasa Phase 2 foundation
-- Target: MariaDB 10.4+, CodeIgniter 3
-- Safety: additive/idempotent only; no DROP, DELETE, TRUNCATE, or destructive backfill.

SET NAMES utf8mb4;
SET @pojasa_old_fk_checks := @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE `tbpo_jasa_request`
  MODIFY COLUMN `kd_vendor_jasa` varchar(20) DEFAULT NULL,
  MODIFY COLUMN `status` varchar(40) NOT NULL DEFAULT 'DRAFT',
  ADD COLUMN IF NOT EXISTS `status_version` int unsigned NOT NULL DEFAULT 1 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `revision_no` smallint unsigned NOT NULL DEFAULT 1 AFTER `status_version`,
  ADD COLUMN IF NOT EXISTS `requires_dirut_ops` tinyint(1) NOT NULL DEFAULT 0 AFTER `revision_no`,
  ADD COLUMN IF NOT EXISTS `dirut_ops_approved_revision` smallint unsigned DEFAULT NULL AFTER `requires_dirut_ops`,
  ADD COLUMN IF NOT EXISTS `acc_with_direktur_oprasional` varchar(50) DEFAULT NULL AFTER `acc_at_kadep`,
  ADD COLUMN IF NOT EXISTS `acc_at_direktur_oprasional` datetime DEFAULT NULL AFTER `acc_with_direktur_oprasional`,
  ADD COLUMN IF NOT EXISTS `estimasi_total_jasa` decimal(18,2) NOT NULL DEFAULT 0.00 AFTER `estimasi_total`,
  ADD COLUMN IF NOT EXISTS `estimasi_total_bahan` decimal(18,2) NOT NULL DEFAULT 0.00 AFTER `estimasi_total_jasa`,
  ADD COLUMN IF NOT EXISTS `deleted_at` datetime DEFAULT NULL AFTER `completed_at`,
  ADD COLUMN IF NOT EXISTS `deleted_by` int(11) DEFAULT NULL AFTER `deleted_at`,
  ADD INDEX IF NOT EXISTS `idx_jasa_request_status_dept` (`status`, `departemen`),
  ADD INDEX IF NOT EXISTS `idx_jasa_request_user_status` (`kd_user`, `status`),
  ADD INDEX IF NOT EXISTS `idx_jasa_request_dirop_status` (`requires_dirut_ops`, `status`);

ALTER TABLE `tbpo_jasa_vendor`
  ADD COLUMN IF NOT EXISTS `nama_vendor_normalized` varchar(150) DEFAULT NULL AFTER `nama_vendor`,
  ADD COLUMN IF NOT EXISTS `version` int unsigned NOT NULL DEFAULT 1 AFTER `status_vendor`,
  ADD COLUMN IF NOT EXISTS `deleted_at` datetime DEFAULT NULL AFTER `update_at`,
  ADD COLUMN IF NOT EXISTS `deleted_by` int(11) DEFAULT NULL AFTER `deleted_at`,
  ADD INDEX IF NOT EXISTS `idx_jasa_vendor_status_name` (`status_vendor`, `nama_vendor`);

ALTER TABLE `tbpo_jasa_progress`
  ADD COLUMN IF NOT EXISTS `revision_no` smallint unsigned NOT NULL DEFAULT 1 AFTER `kd_po_jasa`,
  ADD COLUMN IF NOT EXISTS `status_sebelum` varchar(40) DEFAULT NULL AFTER `status_progress`,
  ADD COLUMN IF NOT EXISTS `status_sesudah` varchar(40) DEFAULT NULL AFTER `status_sebelum`,
  ADD COLUMN IF NOT EXISTS `idempotency_token` char(36) DEFAULT NULL AFTER `status_sesudah`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_progress_token` (`idempotency_token`),
  ADD INDEX IF NOT EXISTS `idx_jasa_progress_request_date` (`kd_po_jasa`, `tgl_progress`, `id_progress_jasa`);

CREATE TABLE IF NOT EXISTS `tbpo_jasa_scope` (
  `id_scope` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint unsigned NOT NULL DEFAULT 1,
  `line_no` int unsigned NOT NULL,
  `jenis_scope` varchar(10) NOT NULL DEFAULT 'JASA',
  `nama_scope` varchar(180) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(40) DEFAULT NULL,
  `harga_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_nyata` decimal(18,2) NOT NULL DEFAULT 0.00,
  `keterangan_purchasing` text DEFAULT NULL,
  `legacy_detail_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_scope`),
  UNIQUE KEY `uk_jasa_scope_line` (`kd_po_jasa`, `revision_no`, `line_no`),
  UNIQUE KEY `uk_jasa_scope_legacy` (`legacy_detail_id`),
  KEY `idx_jasa_scope_request_type` (`kd_po_jasa`, `jenis_scope`),
  CONSTRAINT `fk_jasa_scope_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_material` (
  `id_material` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_scope` bigint unsigned DEFAULT NULL,
  `id_brg_nk` int(11) DEFAULT NULL,
  `nama_material` varchar(180) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `qty_kebutuhan` decimal(18,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(40) DEFAULT NULL,
  `sumber_material` varchar(20) NOT NULL DEFAULT 'STOK',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_material`),
  KEY `idx_jasa_material_request` (`kd_po_jasa`),
  KEY `idx_jasa_material_barang` (`id_brg_nk`),
  CONSTRAINT `fk_jasa_material_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_material_scope` FOREIGN KEY (`id_scope`) REFERENCES `tbpo_jasa_scope` (`id_scope`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_material_barang` FOREIGN KEY (`id_brg_nk`) REFERENCES `tbpo_barang_nk` (`id_brg_nk`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_material_creator` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_dokumen` (
  `id_dokumen` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint unsigned NOT NULL DEFAULT 1,
  `jenis_dokumen` varchar(60) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_original` varchar(180) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size_bytes` bigint unsigned DEFAULT NULL,
  `sha256` char(64) DEFAULT NULL,
  `version_no` smallint unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `supersedes_id` bigint unsigned DEFAULT NULL,
  `legacy_file_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_dokumen`),
  UNIQUE KEY `uk_jasa_dokumen_legacy` (`legacy_file_id`),
  KEY `idx_jasa_dokumen_request_active` (`kd_po_jasa`, `is_active`, `jenis_dokumen`),
  KEY `idx_jasa_dokumen_supersedes` (`supersedes_id`),
  CONSTRAINT `fk_jasa_dokumen_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_dokumen_supersedes` FOREIGN KEY (`supersedes_id`) REFERENCES `tbpo_jasa_dokumen` (`id_dokumen`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_dokumen_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_stock_allocation` (
  `id_allocation` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_material` bigint unsigned NOT NULL,
  `id_brg_nk` int(11) NOT NULL,
  `qty_allocation` decimal(18,2) NOT NULL DEFAULT 0.00,
  `qty_received` decimal(18,2) NOT NULL DEFAULT 0.00,
  `qty_released` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status_allocation` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `version` int unsigned NOT NULL DEFAULT 1,
  `expired_at` datetime DEFAULT NULL,
  `allocated_by` int(11) NOT NULL,
  `allocated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `released_by` int(11) DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_allocation`),
  KEY `idx_jasa_allocation_stock` (`id_brg_nk`, `status_allocation`),
  KEY `idx_jasa_allocation_request` (`kd_po_jasa`, `status_allocation`),
  CONSTRAINT `fk_jasa_allocation_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_allocation_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_allocation_barang` FOREIGN KEY (`id_brg_nk`) REFERENCES `tbpo_barang_nk` (`id_brg_nk`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_allocation_user` FOREIGN KEY (`allocated_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_draft_pembelian` (
  `id_draft_pembelian` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_material` bigint unsigned NOT NULL,
  `id_vendor_jasa` int(11) DEFAULT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `status_draft` varchar(20) NOT NULL DEFAULT 'DRAFT',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_draft_pembelian`),
  KEY `idx_jasa_draft_request` (`kd_po_jasa`, `status_draft`),
  CONSTRAINT `fk_jasa_draft_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_draft_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_draft_vendor` FOREIGN KEY (`id_vendor_jasa`) REFERENCES `tbpo_jasa_vendor` (`id_vendor_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_draft_user` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_approval` (
  `id_approval` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint unsigned NOT NULL DEFAULT 1,
  `approval_stage` varchar(30) NOT NULL,
  `action` varchar(10) NOT NULL,
  `from_status` varchar(40) NOT NULL,
  `to_status` varchar(40) NOT NULL,
  `actor_id` int(11) NOT NULL,
  `actor_code` varchar(25) NOT NULL,
  `actor_role` varchar(30) NOT NULL,
  `actor_departemen` varchar(100) NOT NULL,
  `catatan` text DEFAULT NULL,
  `action_token` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_approval`),
  UNIQUE KEY `uk_jasa_approval_token` (`action_token`),
  KEY `idx_jasa_approval_stage` (`kd_po_jasa`, `revision_no`, `approval_stage`),
  CONSTRAINT `fk_jasa_approval_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_approval_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_revisi` (
  `id_revisi` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint unsigned NOT NULL,
  `revision_source` varchar(40) NOT NULL,
  `alasan_revisi` text NOT NULL,
  `snapshot_json` longtext DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_revisi`),
  UNIQUE KEY `uk_jasa_revisi_number` (`kd_po_jasa`, `revision_no`),
  CONSTRAINT `fk_jasa_revisi_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_revisi_requester` FOREIGN KEY (`requested_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_revisi_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk` (
  `id_spk` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `revision_no` smallint unsigned NOT NULL DEFAULT 1,
  `issued_by` int(11) NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `snapshot_json` longtext NOT NULL,
  `template_version` varchar(30) NOT NULL DEFAULT 'DRAFT-FOUNDATION',
  `document_status` varchar(20) NOT NULL DEFAULT 'ISSUED',
  PRIMARY KEY (`id_spk`),
  UNIQUE KEY `uk_jasa_spk_request` (`kd_po_jasa`),
  UNIQUE KEY `uk_jasa_spk_number` (`no_spk`),
  CONSTRAINT `fk_jasa_spk_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_spk_issuer` FOREIGN KEY (`issued_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_biaya_aktual` (
  `id_biaya_aktual` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint unsigned NOT NULL DEFAULT 1,
  `tgl_biaya` date NOT NULL,
  `jenis_biaya` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `nominal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `id_dokumen_bukti` bigint unsigned NOT NULL,
  `input_by` int(11) NOT NULL,
  `input_name` varchar(120) NOT NULL,
  `verification_status` varchar(50) NOT NULL DEFAULT 'BELUM_DIVERIFIKASI',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_note` text DEFAULT NULL,
  `idempotency_token` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_biaya_aktual`),
  UNIQUE KEY `uk_jasa_biaya_token` (`idempotency_token`),
  KEY `idx_jasa_biaya_request_date` (`kd_po_jasa`, `tgl_biaya`),
  CONSTRAINT `fk_jasa_biaya_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_biaya_document` FOREIGN KEY (`id_dokumen_bukti`) REFERENCES `tbpo_jasa_dokumen` (`id_dokumen`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_biaya_inputter` FOREIGN KEY (`input_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_biaya_verifier` FOREIGN KEY (`verified_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_log_aktivitas` (
  `id_log_aktivitas` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) DEFAULT NULL,
  `revision_no` smallint unsigned DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `from_status` varchar(40) DEFAULT NULL,
  `to_status` varchar(40) DEFAULT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `actor_code` varchar(25) DEFAULT NULL,
  `actor_name` varchar(120) DEFAULT NULL,
  `actor_role` varchar(30) DEFAULT NULL,
  `actor_departemen` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log_aktivitas`),
  KEY `idx_jasa_log_request` (`kd_po_jasa`, `id_log_aktivitas`),
  KEY `idx_jasa_log_event_date` (`event_type`, `created_at`),
  CONSTRAINT `fk_jasa_log_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_log_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_notifikasi` (
  `id_notifikasi` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(500) NOT NULL,
  `target_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notifikasi`),
  KEY `idx_jasa_notif_recipient` (`recipient_user_id`, `is_read`, `id_notifikasi`),
  KEY `idx_jasa_notif_request_event` (`kd_po_jasa`, `event_type`),
  CONSTRAINT `fk_jasa_notif_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_notif_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_number_counter` (
  `counter_type` varchar(20) NOT NULL,
  `period_key` varchar(20) NOT NULL,
  `last_number` bigint unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`counter_type`, `period_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_stock_receipt` (
  `id_receipt` bigint unsigned NOT NULL AUTO_INCREMENT,
  `no_receipt` varchar(40) NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `receipt_at` datetime NOT NULL,
  `confirmed_by` int(11) NOT NULL,
  `confirmed_name` varchar(120) NOT NULL,
  `catatan` text DEFAULT NULL,
  `idempotency_token` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_receipt`),
  UNIQUE KEY `uk_jasa_receipt_number` (`no_receipt`),
  UNIQUE KEY `uk_jasa_receipt_token` (`idempotency_token`),
  KEY `idx_jasa_receipt_request` (`kd_po_jasa`, `receipt_at`),
  CONSTRAINT `fk_jasa_receipt_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_receipt_user` FOREIGN KEY (`confirmed_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_stock_receipt_detail` (
  `id_receipt_detail` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_receipt` bigint unsigned NOT NULL,
  `id_allocation` bigint unsigned NOT NULL,
  `id_material` bigint unsigned NOT NULL,
  `id_brg_nk` int(11) NOT NULL,
  `qty_received` decimal(18,2) NOT NULL DEFAULT 0.00,
  `id_transnk` int(11) DEFAULT NULL,
  `posting_token` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_receipt_detail`),
  UNIQUE KEY `uk_jasa_receipt_posting` (`posting_token`),
  UNIQUE KEY `uk_jasa_receipt_transaction` (`id_transnk`),
  KEY `idx_jasa_receipt_allocation` (`id_allocation`),
  CONSTRAINT `fk_jasa_receipt_detail_header` FOREIGN KEY (`id_receipt`) REFERENCES `tbpo_jasa_stock_receipt` (`id_receipt`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_receipt_detail_allocation` FOREIGN KEY (`id_allocation`) REFERENCES `tbpo_jasa_stock_allocation` (`id_allocation`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_receipt_detail_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_receipt_detail_barang` FOREIGN KEY (`id_brg_nk`) REFERENCES `tbpo_barang_nk` (`id_brg_nk`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_receipt_detail_transaction` FOREIGN KEY (`id_transnk`) REFERENCES `tbpo_transaksi` (`id_transnk`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = @pojasa_old_fk_checks;
