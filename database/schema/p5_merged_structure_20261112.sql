-- P5 combined application structure only; no historical rows, no source schema references.

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `tbpo_akun_tr` (
  `id_akun` int(11) NOT NULL AUTO_INCREMENT,
  `kd_akun` varchar(25) NOT NULL,
  `ket_akun` text NOT NULL,
  `input_by` varchar(25) NOT NULL,
  `create_at` datetime NOT NULL,
  `update_by` varchar(25) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_akun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_barang` (
  `id_barang` int(11) NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(25) NOT NULL,
  `kd_suplier` varchar(25) NOT NULL,
  `nama_barang` text NOT NULL,
  `produsen` varchar(150) DEFAULT NULL,
  `spesifikasi_merk` varchar(255) DEFAULT NULL,
  `golongan` varchar(100) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `komposisi` text DEFAULT NULL,
  `grup` varchar(100) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `panjang` int(11) NOT NULL DEFAULT 0,
  `lebar` int(11) NOT NULL DEFAULT 0,
  `tinggi` int(11) NOT NULL DEFAULT 0,
  `berat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kubikasi` decimal(12,6) NOT NULL DEFAULT 0.000000,
  `isi` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kemasan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `stock_minimum` int(11) NOT NULL DEFAULT 0,
  `merk_barang` text NOT NULL,
  `kelompok_dagang` text DEFAULT NULL,
  `kelompok_barang` text DEFAULT NULL,
  `kategori_barang` text DEFAULT NULL,
  `bhn_aktif` text DEFAULT NULL,
  `produk_fokus` text DEFAULT NULL,
  `is_active` enum('T','F') NOT NULL DEFAULT 'T',
  `is_lot` enum('T','F') NOT NULL DEFAULT 'F',
  `is_inventori` enum('T','F') NOT NULL DEFAULT 'T',
  `is_beli` enum('T','F') NOT NULL DEFAULT 'T',
  `is_jual` enum('T','F') NOT NULL DEFAULT 'T',
  `hpp_average` enum('T','F') NOT NULL DEFAULT 'T',
  `hpp_fifo` enum('T','F') NOT NULL DEFAULT 'F',
  `hpp_lifo` enum('T','F') NOT NULL DEFAULT 'F',
  `kode_akun_harga_pokok` varchar(30) DEFAULT '51030',
  `kode_akun_penjualan` varchar(30) DEFAULT '41032',
  `kode_akun_persediaan` varchar(30) DEFAULT '14030',
  `kode_akun_pengiriman_beli` varchar(30) DEFAULT '51032',
  `kode_akun_pengiriman_jual` varchar(30) DEFAULT '64030',
  `kode_akun_retur_penjualan` varchar(30) DEFAULT '41034',
  PRIMARY KEY (`id_barang`),
  KEY `idx_kode_barang` (`kode_barang`),
  KEY `idx_kd_suplier` (`kd_suplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_barang_akun` (
  `kode_barang` varchar(30) NOT NULL,
  `kode_akun_penjualan` varchar(30) DEFAULT NULL,
  `kode_akun_persediaan` varchar(30) DEFAULT NULL,
  `kode_akun_harga_pokok` varchar(30) DEFAULT NULL,
  `kode_akun_retur_penjualan` varchar(30) DEFAULT NULL,
  `kode_akun_pengiriman_beli` varchar(30) DEFAULT NULL,
  `kode_akun_pengiriman_jual` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`kode_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_barang_nk` (
  `id_brg_nk` int(11) NOT NULL AUTO_INCREMENT,
  `kd_barang` varchar(25) NOT NULL,
  `kd_br_adm` varchar(25) NOT NULL,
  `kat_barang` varchar(25) NOT NULL,
  `nama_barang` text NOT NULL,
  `descnk` text NOT NULL,
  `status_master` varchar(12) NOT NULL DEFAULT 'ACTIVE',
  `satuan` int(5) NOT NULL,
  `minimum_stock` decimal(18,2) NOT NULL DEFAULT 0.00,
  `gbr_barang` text NOT NULL,
  `qrcode_path` text NOT NULL,
  `qrcode_data` varchar(50) NOT NULL,
  `inputer` varchar(25) NOT NULL,
  `create_at` datetime NOT NULL,
  `last_updated` varchar(25) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `kd_lokasi` int(11) NOT NULL,
  PRIMARY KEY (`id_brg_nk`),
  KEY `idx_barang_nk_kd_barang` (`kd_barang`),
  KEY `idx_barang_nk_kd_lokasi` (`kd_lokasi`),
  KEY `idx_barang_nk_status_name` (`status_master`,`nama_barang`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_barang_nk_lokasi` (
  `id_lokasi` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lokasi` text NOT NULL,
  PRIMARY KEY (`id_lokasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_barang_packaging` (
  `id_packaging` int(11) NOT NULL AUTO_INCREMENT,
  `id_barang` int(11) NOT NULL,
  `isi_kemasan` decimal(20,6) DEFAULT NULL,
  `satuan_kemasan` varchar(20) DEFAULT NULL,
  `isi_per_dos` decimal(20,6) DEFAULT NULL,
  `isi_per_inner` decimal(20,6) DEFAULT NULL,
  `inner_per_dos` decimal(20,6) DEFAULT NULL,
  `satuan_dasar` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_packaging`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_detail_po` (
  `id_det_po` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po` varchar(255) NOT NULL,
  `no_po` varchar(255) NOT NULL,
  `tgl_transaksi` date NOT NULL,
  `kd_suplier` varchar(25) NOT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `nama_barang` text NOT NULL,
  `satuan` text NOT NULL,
  `qty` double NOT NULL,
  `isi` decimal(15,2) DEFAULT 0.00,
  `kemasan` decimal(15,2) DEFAULT 0.00,
  `qty_kecil` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `hrg_satuan` double NOT NULL,
  `harga_satuan_exclude` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `harga_satuan_kecil` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `harga_satuan_kecil_exclude` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `hrg_diskon` double NOT NULL DEFAULT 0,
  `hrg_total` double NOT NULL,
  `hrg_total_diskon` double NOT NULL DEFAULT 0,
  `id_diskon_merk` int(11) DEFAULT NULL,
  `satuan_diskon` varchar(10) DEFAULT NULL,
  `nominal_diskon` decimal(15,2) NOT NULL DEFAULT 0.00,
  `diskon_satuan_kecil` decimal(15,2) NOT NULL DEFAULT 0.00,
  `harga_satuan_kecil_setelah_diskon` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_harga_setelah_diskon` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_bonus` tinyint(1) NOT NULL DEFAULT 0,
  `keterangan_bonus` varchar(255) NOT NULL DEFAULT '',
  `keterangan_harga_ppn` varchar(20) NOT NULL DEFAULT '',
  `kd_user` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_det_po`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_detail_po_nk` (
  `id_det_po_nk` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_nk` varchar(25) NOT NULL,
  `kd_po_req` varchar(25) NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `tgl_transaksi` text NOT NULL,
  `kd_bsys` varchar(25) NOT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `nama_barang` text NOT NULL,
  `deskripsi` text NOT NULL,
  `keterangan` text NOT NULL,
  `qty` int(12) NOT NULL,
  `satuan` int(12) NOT NULL,
  `kat_barang` varchar(25) NOT NULL,
  `hrg_satuan` int(25) NOT NULL,
  `hrg_nyata` int(25) NOT NULL,
  `total_harga` int(25) NOT NULL,
  `total_nyata` int(25) NOT NULL,
  `gbr_produk` varchar(255) NOT NULL,
  `source_module` varchar(30) DEFAULT NULL,
  `source_reference` varchar(120) DEFAULT NULL,
  `source_material_id` bigint(20) unsigned DEFAULT NULL,
  `source_draft_id` bigint(20) unsigned DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_det_po_nk`),
  UNIQUE KEY `uk_detponk_source_reference` (`source_module`,`source_reference`),
  KEY `idx_detponk_source_material` (`source_module`,`source_material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_detail_req` (
  `id_det_po_nk` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_nk` varchar(25) NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `tgl_transaksi` text NOT NULL,
  `kd_bsys` varchar(25) NOT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `nama_barang` text NOT NULL,
  `deskripsi` text NOT NULL,
  `keterangan` text NOT NULL,
  `qty` int(12) NOT NULL,
  `satuan` int(2) NOT NULL,
  `kat_barang` varchar(25) NOT NULL,
  `status` int(11) NOT NULL,
  `sts_done` int(2) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_det_po_nk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_diskon` (
  `id_diskon` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po` varchar(255) NOT NULL,
  `kd_suplier` varchar(25) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `nominal` double NOT NULL,
  PRIMARY KEY (`id_diskon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_diskon_merk` (
  `id_diskon` int(11) NOT NULL AUTO_INCREMENT,
  `no_po` varchar(50) DEFAULT NULL,
  `merk_barang` varchar(255) NOT NULL,
  `satuan_diskon` enum('BOX','PCS','LTR','KG') NOT NULL,
  `nominal_diskon` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_diskon`),
  KEY `idx_merk_barang` (`merk_barang`),
  KEY `idx_no_po` (`no_po`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_file_bukti_beli` (
  `id_fk_bukti` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_nk` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `user_upload` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_uploaded` varchar(255) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_fk_bukti`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_file_nk` (
  `id_file_nk` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_nk` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `user_upload` varchar(25) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_uploaded` varchar(255) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_file_nk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_formula` (
  `id_formula` int(11) NOT NULL AUTO_INCREMENT,
  `kode_formula` varchar(50) NOT NULL,
  `nama_formula` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `formula_expression` text NOT NULL,
  `output_label` varchar(100) NOT NULL,
  `output_unit` varchar(50) DEFAULT NULL,
  `rounding_mode` enum('none','round','ceil','floor') DEFAULT 'none',
  `decimal_place` int(11) DEFAULT 2,
  `status` tinyint(4) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_formula`),
  UNIQUE KEY `kode_formula` (`kode_formula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_formula_result` (
  `id_result` int(11) NOT NULL AUTO_INCREMENT,
  `id_po_detail` int(11) DEFAULT NULL,
  `id_formula` int(11) NOT NULL,
  `input_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `formula_expression` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `result_value` decimal(20,6) NOT NULL,
  `result_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_result`),
  KEY `id_formula` (`id_formula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbpo_formula_variable` (
  `id_variable` int(11) NOT NULL AUTO_INCREMENT,
  `id_formula` int(11) NOT NULL,
  `variable_key` varchar(100) NOT NULL,
  `variable_label` varchar(150) NOT NULL,
  `input_type` enum('number','decimal','currency') DEFAULT 'decimal',
  `unit` varchar(50) DEFAULT NULL,
  `default_value` decimal(20,6) DEFAULT NULL,
  `is_required` tinyint(4) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_variable`),
  KEY `id_formula` (`id_formula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_generateqrcode` (
  `id_gqrcode` int(11) NOT NULL AUTO_INCREMENT,
  `kd_qrcode` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_gqrcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_generate_kd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kd_barang` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_generate_kd_ponk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kd_barang` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_approval` (
  `id_approval` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
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
  KEY `idx_jasa_approval_stage` (`kd_po_jasa`,`revision_no`,`approval_stage`),
  KEY `fk_jasa_approval_actor` (`actor_id`),
  CONSTRAINT `fk_jasa_approval_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_approval_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_bast` (
  `id_bast_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `no_bast` varchar(80) NOT NULL,
  `tgl_bast` date NOT NULL,
  `penerima_pekerjaan` varchar(120) NOT NULL,
  `catatan_bast` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_name` varchar(120) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `spk_version_no` smallint(5) unsigned DEFAULT NULL,
  `idempotency_token` char(36) DEFAULT NULL,
  `payload_hash` char(64) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_bast_jasa`),
  UNIQUE KEY `uk_tbpo_jasa_bast_kd_po` (`kd_po_jasa`),
  UNIQUE KEY `uk_jasa_bast_token` (`idempotency_token`),
  KEY `idx_tbpo_jasa_bast_no` (`no_bast`),
  CONSTRAINT `fk_tbpo_jasa_bast_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_biaya` (
  `id_biaya_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `tgl_biaya` date NOT NULL,
  `jenis_biaya` varchar(100) NOT NULL,
  `deskripsi_biaya` text NOT NULL,
  `nominal_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `nominal_realisasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `selisih` decimal(18,2) NOT NULL DEFAULT 0.00,
  `no_invoice_vendor` varchar(100) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_name` varchar(120) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_biaya_jasa`),
  KEY `idx_tbpo_jasa_biaya_kd_po` (`kd_po_jasa`),
  KEY `idx_tbpo_jasa_biaya_tgl` (`tgl_biaya`),
  CONSTRAINT `fk_tbpo_jasa_biaya_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_biaya_aktual` (
  `id_biaya_aktual` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `tgl_biaya` date NOT NULL,
  `cost_source` varchar(30) NOT NULL DEFAULT 'PEMBELIAN_BARU',
  `source_type` varchar(30) NOT NULL DEFAULT 'MANUAL',
  `record_type` varchar(20) NOT NULL DEFAULT 'MANUAL',
  `record_status` varchar(40) NOT NULL DEFAULT 'DICATAT',
  `id_scope` bigint(20) unsigned DEFAULT NULL,
  `id_material` bigint(20) unsigned DEFAULT NULL,
  `id_spk` bigint(20) unsigned DEFAULT NULL,
  `spk_version_no` smallint(5) unsigned DEFAULT NULL,
  `source_po_nk_id` int(12) DEFAULT NULL,
  `source_po_nk_code` varchar(25) DEFAULT NULL,
  `source_detail_po_nk_id` int(11) DEFAULT NULL,
  `source_receipt_reference` varchar(120) DEFAULT NULL,
  `source_document_reference` varchar(255) DEFAULT NULL,
  `qty_actual` decimal(18,2) DEFAULT NULL,
  `unit_price_actual` decimal(18,2) DEFAULT NULL,
  `parent_cost_id` bigint(20) unsigned DEFAULT NULL,
  `row_version` int(10) unsigned NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `jenis_biaya` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `nominal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `id_dokumen_bukti` bigint(20) unsigned DEFAULT NULL,
  `id_draft_pembelian` bigint(20) unsigned DEFAULT NULL,
  `input_by` int(11) NOT NULL,
  `input_name` varchar(120) NOT NULL,
  `verification_status` varchar(50) NOT NULL DEFAULT 'BELUM_DIVERIFIKASI',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_note` text DEFAULT NULL,
  `idempotency_token` char(36) NOT NULL,
  `payload_hash` char(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_biaya_aktual`),
  UNIQUE KEY `uk_jasa_biaya_token` (`idempotency_token`),
  UNIQUE KEY `uk_jasa_cost_receipt_reference` (`source_receipt_reference`),
  KEY `idx_jasa_biaya_request_date` (`kd_po_jasa`,`tgl_biaya`),
  KEY `fk_jasa_biaya_document` (`id_dokumen_bukti`),
  KEY `fk_jasa_biaya_inputter` (`input_by`),
  KEY `fk_jasa_biaya_verifier` (`verified_by`),
  KEY `idx_jasa_biaya_source` (`kd_po_jasa`,`cost_source`,`tgl_biaya`),
  KEY `idx_jasa_biaya_draft` (`id_draft_pembelian`),
  KEY `idx_jasa_cost_record_status` (`kd_po_jasa`,`record_type`,`record_status`),
  KEY `idx_jasa_cost_material` (`id_material`,`record_status`),
  KEY `fk_jasa_cost_scope` (`id_scope`),
  KEY `fk_jasa_cost_spk` (`id_spk`),
  CONSTRAINT `fk_jasa_biaya_document` FOREIGN KEY (`id_dokumen_bukti`) REFERENCES `tbpo_jasa_dokumen` (`id_dokumen`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_biaya_draft` FOREIGN KEY (`id_draft_pembelian`) REFERENCES `tbpo_jasa_draft_pembelian` (`id_draft_pembelian`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_biaya_inputter` FOREIGN KEY (`input_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_biaya_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_biaya_verifier` FOREIGN KEY (`verified_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_cost_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_cost_scope` FOREIGN KEY (`id_scope`) REFERENCES `tbpo_jasa_scope` (`id_scope`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_cost_spk` FOREIGN KEY (`id_spk`) REFERENCES `tbpo_jasa_spk` (`id_spk`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_cost_audit` (
  `id_cost_audit` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_biaya_aktual` bigint(20) unsigned NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `event_type` varchar(30) NOT NULL,
  `before_json` longtext DEFAULT NULL,
  `after_json` longtext DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `actor_id` int(11) NOT NULL,
  `actor_role` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cost_audit`),
  KEY `idx_jasa_cost_audit_cost` (`id_biaya_aktual`,`created_at`),
  KEY `fk_jasa_cost_audit_request` (`kd_po_jasa`),
  KEY `fk_jasa_cost_audit_actor` (`actor_id`),
  CONSTRAINT `fk_jasa_cost_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_cost_audit_cost` FOREIGN KEY (`id_biaya_aktual`) REFERENCES `tbpo_jasa_biaya_aktual` (`id_biaya_aktual`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_cost_audit_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_dokumen` (
  `id_dokumen` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `jenis_dokumen` varchar(60) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_original` varchar(180) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size_bytes` bigint(20) unsigned DEFAULT NULL,
  `sha256` char(64) DEFAULT NULL,
  `version_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `supersedes_id` bigint(20) unsigned DEFAULT NULL,
  `legacy_file_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_dokumen`),
  UNIQUE KEY `uk_jasa_dokumen_legacy` (`legacy_file_id`),
  KEY `idx_jasa_dokumen_request_active` (`kd_po_jasa`,`is_active`,`jenis_dokumen`),
  KEY `idx_jasa_dokumen_supersedes` (`supersedes_id`),
  KEY `fk_jasa_dokumen_uploader` (`uploaded_by`),
  CONSTRAINT `fk_jasa_dokumen_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_dokumen_supersedes` FOREIGN KEY (`supersedes_id`) REFERENCES `tbpo_jasa_dokumen` (`id_dokumen`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_dokumen_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_draft_pembelian` (
  `id_draft_pembelian` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `id_material` bigint(20) unsigned NOT NULL,
  `id_vendor_jasa` int(11) DEFAULT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `status_draft` varchar(40) NOT NULL DEFAULT 'DRAFT',
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `idempotency_token` char(36) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `submitted_po_nk_id` int(12) DEFAULT NULL,
  `submitted_po_nk_code` varchar(25) DEFAULT NULL,
  `submitted_detail_po_nk_id` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_draft_pembelian`),
  UNIQUE KEY `uk_jasa_draft_origin` (`kd_po_jasa`,`id_material`),
  UNIQUE KEY `uk_jasa_draft_token` (`idempotency_token`),
  KEY `idx_jasa_draft_request` (`kd_po_jasa`,`status_draft`),
  KEY `fk_jasa_draft_vendor` (`id_vendor_jasa`),
  KEY `fk_jasa_draft_user` (`created_by`),
  KEY `idx_jasa_draft_material_status` (`id_material`,`status_draft`),
  KEY `idx_jasa_draft_submission` (`submitted_po_nk_id`,`status_draft`),
  CONSTRAINT `fk_jasa_draft_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_draft_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_draft_user` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_draft_vendor` FOREIGN KEY (`id_vendor_jasa`) REFERENCES `tbpo_jasa_vendor` (`id_vendor_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_evaluation` (
  `id_evaluation_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `kd_vendor_jasa` varchar(20) DEFAULT NULL,
  `kualitas_score` decimal(4,2) NOT NULL DEFAULT 0.00,
  `ketepatan_waktu_score` decimal(4,2) NOT NULL DEFAULT 0.00,
  `biaya_score` decimal(4,2) NOT NULL DEFAULT 0.00,
  `total_score` decimal(4,2) NOT NULL DEFAULT 0.00,
  `catatan_evaluasi` text DEFAULT NULL,
  `evaluated_by` varchar(50) DEFAULT NULL,
  `evaluated_name` varchar(120) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `vendor_name_snapshot` varchar(180) NOT NULL DEFAULT '',
  `row_version` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_evaluation_jasa`),
  UNIQUE KEY `uk_tbpo_jasa_evaluation_kd_po` (`kd_po_jasa`),
  KEY `idx_tbpo_jasa_evaluation_vendor` (`kd_vendor_jasa`),
  KEY `idx_tbpo_jasa_evaluation_score` (`total_score`),
  CONSTRAINT `fk_tbpo_jasa_evaluation_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON DELETE CASCADE,
  CONSTRAINT `fk_tbpo_jasa_evaluation_vendor` FOREIGN KEY (`kd_vendor_jasa`) REFERENCES `tbpo_jasa_vendor` (`kd_vendor_jasa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_evaluation_audit` (
  `id_evaluation_audit` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_evaluation_jasa` int(11) NOT NULL,
  `actor_id` int(11) NOT NULL,
  `idempotency_token` char(36) NOT NULL,
  `payload_hash` char(64) NOT NULL,
  `before_json` longtext DEFAULT NULL,
  `after_json` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_evaluation_audit`),
  UNIQUE KEY `uk_jasa_evaluation_token` (`idempotency_token`),
  KEY `fk_jasa_evaluation_audit_request` (`kd_po_jasa`),
  KEY `fk_jasa_evaluation_audit_evaluation` (`id_evaluation_jasa`),
  KEY `fk_jasa_evaluation_audit_actor` (`actor_id`),
  CONSTRAINT `fk_jasa_evaluation_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`),
  CONSTRAINT `fk_jasa_evaluation_audit_evaluation` FOREIGN KEY (`id_evaluation_jasa`) REFERENCES `tbpo_jasa_evaluation` (`id_evaluation_jasa`),
  CONSTRAINT `fk_jasa_evaluation_audit_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_file` (
  `id_file_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `jenis_dokumen` varchar(60) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `file_name` varchar(180) NOT NULL,
  `file_original` varchar(180) DEFAULT NULL,
  `file_ext` varchar(20) DEFAULT NULL,
  `file_size` decimal(10,2) DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `uploaded_name` varchar(120) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_file_jasa`),
  KEY `idx_tbpo_jasa_file_kd_po` (`kd_po_jasa`),
  KEY `idx_tbpo_jasa_file_jenis` (`jenis_dokumen`),
  CONSTRAINT `fk_tbpo_jasa_file_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_log_aktivitas` (
  `id_log_aktivitas` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) DEFAULT NULL,
  `revision_no` smallint(5) unsigned DEFAULT NULL,
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
  KEY `idx_jasa_log_request` (`kd_po_jasa`,`id_log_aktivitas`),
  KEY `idx_jasa_log_event_date` (`event_type`,`created_at`),
  KEY `fk_jasa_log_actor` (`actor_id`),
  CONSTRAINT `fk_jasa_log_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_log_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_material` (
  `id_material` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `line_no` int(10) unsigned NOT NULL DEFAULT 1,
  `id_scope` bigint(20) unsigned DEFAULT NULL,
  `id_brg_nk` int(11) DEFAULT NULL,
  `reference_type` varchar(12) NOT NULL DEFAULT 'MANUAL',
  `id_usulan_barang` bigint(20) unsigned DEFAULT NULL,
  `kd_barang_snapshot` varchar(25) DEFAULT NULL,
  `nama_material` varchar(180) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `qty_kebutuhan` decimal(18,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(40) DEFAULT NULL,
  `harga_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_pembanding_purchasing` decimal(18,2) DEFAULT NULL,
  `keterangan_purchasing` text DEFAULT NULL,
  `total_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `sumber_material` varchar(20) NOT NULL DEFAULT 'STOK',
  `created_by` int(11) DEFAULT NULL,
  `added_by_purchasing` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `deleted_reason_purchasing` text DEFAULT NULL,
  PRIMARY KEY (`id_material`),
  UNIQUE KEY `uk_jasa_material_line` (`kd_po_jasa`,`revision_no`,`line_no`),
  KEY `idx_jasa_material_request` (`kd_po_jasa`),
  KEY `idx_jasa_material_barang` (`id_brg_nk`),
  KEY `fk_jasa_material_scope` (`id_scope`),
  KEY `fk_jasa_material_creator` (`created_by`),
  KEY `idx_jasa_material_revision_active` (`kd_po_jasa`,`revision_no`,`is_active`,`line_no`),
  KEY `idx_jasa_material_reference` (`reference_type`,`id_brg_nk`,`id_usulan_barang`),
  CONSTRAINT `fk_jasa_material_barang` FOREIGN KEY (`id_brg_nk`) REFERENCES `tbpo_barang_nk` (`id_brg_nk`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_material_creator` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_material_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_material_scope` FOREIGN KEY (`id_scope`) REFERENCES `tbpo_jasa_scope` (`id_scope`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_material_usulan` (
  `id_usulan_barang` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_usulan` varchar(35) NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
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
  KEY `idx_jasa_material_usulan_work` (`status_usulan`,`created_at`),
  KEY `idx_jasa_material_usulan_request` (`kd_po_jasa`,`revision_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_note` (
  `id_note_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `isi_note` text NOT NULL,
  `kd_user` varchar(50) DEFAULT NULL,
  `nama_user` varchar(120) DEFAULT NULL,
  `aksi_status` varchar(40) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_note_jasa`),
  KEY `idx_tbpo_jasa_note_kd_po` (`kd_po_jasa`),
  CONSTRAINT `fk_tbpo_jasa_note_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_notifikasi` (
  `id_notifikasi` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
  KEY `idx_jasa_notif_recipient` (`recipient_user_id`,`is_read`,`id_notifikasi`),
  KEY `idx_jasa_notif_request_event` (`kd_po_jasa`,`event_type`),
  CONSTRAINT `fk_jasa_notif_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_notif_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_number_counter` (
  `counter_type` varchar(20) NOT NULL,
  `period_key` varchar(20) NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`counter_type`,`period_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_payment` (
  `id_payment_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `tgl_invoice` date NOT NULL,
  `jatuh_tempo` date NOT NULL,
  `no_invoice` varchar(100) NOT NULL,
  `nominal_tagihan` decimal(18,2) NOT NULL DEFAULT 0.00,
  `nominal_bayar` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status_bayar` varchar(30) NOT NULL DEFAULT 'BELUM BAYAR',
  `catatan_payment` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_name` varchar(120) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `row_version` int(10) unsigned NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_payment_jasa`),
  UNIQUE KEY `uk_jasa_payment_invoice` (`kd_po_jasa`,`no_invoice`),
  KEY `idx_tbpo_jasa_payment_kd_po` (`kd_po_jasa`),
  KEY `idx_tbpo_jasa_payment_status` (`status_bayar`),
  KEY `idx_tbpo_jasa_payment_due` (`jatuh_tempo`),
  CONSTRAINT `fk_tbpo_jasa_payment_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_payment_audit` (
  `id_payment_audit` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_payment_jasa` int(11) NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `event_type` varchar(20) NOT NULL,
  `before_json` longtext DEFAULT NULL,
  `after_json` longtext NOT NULL,
  `actor_id` int(11) NOT NULL,
  `idempotency_token` char(36) NOT NULL,
  `payload_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_payment_audit`),
  UNIQUE KEY `uk_jasa_payment_audit_token` (`idempotency_token`),
  KEY `idx_jasa_payment_audit_request` (`kd_po_jasa`,`id_payment_audit`),
  KEY `fk_jasa_payment_audit_invoice` (`id_payment_jasa`),
  KEY `fk_jasa_payment_audit_actor` (`actor_id`),
  CONSTRAINT `fk_jasa_payment_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`),
  CONSTRAINT `fk_jasa_payment_audit_invoice` FOREIGN KEY (`id_payment_jasa`) REFERENCES `tbpo_jasa_payment` (`id_payment_jasa`),
  CONSTRAINT `fk_jasa_payment_audit_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_pickup_detail` (
  `id_pickup_detail` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_pickup_request` bigint(20) unsigned NOT NULL,
  `id_material` bigint(20) unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id_pickup_detail`),
  UNIQUE KEY `uk_jasa_pickup_material` (`id_pickup_request`,`id_material`),
  KEY `fk_jasa_pickup_detail_material` (`id_material`),
  CONSTRAINT `fk_jasa_pickup_detail_header` FOREIGN KEY (`id_pickup_request`) REFERENCES `tbpo_jasa_pickup_request` (`id_pickup_request`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_pickup_detail_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_pickup_request` (
  `id_pickup_request` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'MENUNGGU_ACC_KADEP',
  `requested_by` int(11) NOT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL,
  `prepared_at` datetime DEFAULT NULL,
  `preparation_note` text DEFAULT NULL,
  `idempotency_token` char(36) NOT NULL,
  `received_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pickup_request`),
  UNIQUE KEY `uk_jasa_pickup_token` (`idempotency_token`),
  KEY `idx_jasa_pickup_request` (`kd_po_jasa`,`status`),
  CONSTRAINT `fk_jasa_pickup_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_progress` (
  `id_progress_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `spk_version_no` smallint(5) unsigned DEFAULT NULL,
  `tgl_progress` date NOT NULL,
  `milestone` varchar(180) NOT NULL,
  `aktivitas` text DEFAULT NULL,
  `kendala` text DEFAULT NULL,
  `tindak_lanjut` text DEFAULT NULL,
  `progress_persen` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status_progress` varchar(50) NOT NULL,
  `status_sebelum` varchar(40) DEFAULT NULL,
  `status_sesudah` varchar(40) DEFAULT NULL,
  `idempotency_token` char(36) DEFAULT NULL,
  `payload_hash` char(64) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `id_dokumen_evidence` bigint(20) unsigned DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_name` varchar(120) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_progress_jasa`),
  UNIQUE KEY `uk_jasa_progress_token` (`idempotency_token`),
  KEY `idx_tbpo_jasa_progress_kd_po` (`kd_po_jasa`),
  KEY `idx_tbpo_jasa_progress_tgl` (`tgl_progress`),
  KEY `idx_jasa_progress_request_date` (`kd_po_jasa`,`tgl_progress`,`id_progress_jasa`),
  KEY `idx_jasa_progress_status_date` (`kd_po_jasa`,`status_progress`,`tgl_progress`),
  KEY `idx_jasa_progress_evidence` (`id_dokumen_evidence`),
  KEY `idx_jasa_progress_user` (`created_by_user_id`),
  KEY `idx_jasa_progress_spk_version` (`kd_po_jasa`,`spk_version_no`),
  CONSTRAINT `fk_jasa_progress_document` FOREIGN KEY (`id_dokumen_evidence`) REFERENCES `tbpo_jasa_dokumen` (`id_dokumen`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_progress_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tbpo_jasa_progress_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_purchase_adjustment` (
  `id_adjustment` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_spk_change` bigint(20) unsigned NOT NULL,
  `id_submission_detail` bigint(20) unsigned DEFAULT NULL,
  `adjustment_type` varchar(30) NOT NULL,
  `before_json` longtext DEFAULT NULL,
  `after_json` longtext DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_adjustment`),
  KEY `idx_jasa_adjustment_request` (`kd_po_jasa`,`status`),
  KEY `fk_jasa_adjustment_change` (`id_spk_change`),
  KEY `fk_jasa_adjustment_submission` (`id_submission_detail`),
  KEY `fk_jasa_adjustment_creator` (`created_by`),
  CONSTRAINT `fk_jasa_adjustment_change` FOREIGN KEY (`id_spk_change`) REFERENCES `tbpo_jasa_spk_change` (`id_spk_change`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_adjustment_creator` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_adjustment_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_adjustment_submission` FOREIGN KEY (`id_submission_detail`) REFERENCES `tbpo_jasa_purchase_submission_detail` (`id_submission_detail`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_purchase_receipt` (
  `id_purchase_receipt` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_submission_detail` bigint(20) unsigned NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_material` bigint(20) unsigned NOT NULL,
  `id_detail_po_nk` int(11) NOT NULL,
  `receipt_reference` varchar(120) NOT NULL,
  `record_type` varchar(20) NOT NULL DEFAULT 'RECEIPT',
  `reversal_of_id` bigint(20) unsigned DEFAULT NULL,
  `qty_received` decimal(18,2) NOT NULL,
  `unit_price_actual` decimal(18,2) NOT NULL,
  `total_actual` decimal(18,2) NOT NULL,
  `on_hand_date` date NOT NULL,
  `document_reference` varchar(255) DEFAULT NULL,
  `idempotency_token` char(36) NOT NULL,
  `processed_by` int(11) NOT NULL,
  `id_transnk` int(11) DEFAULT NULL,
  `processed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_purchase_receipt`),
  UNIQUE KEY `uk_jasa_purchase_receipt_ref` (`receipt_reference`),
  UNIQUE KEY `uk_jasa_purchase_receipt_token` (`idempotency_token`),
  UNIQUE KEY `uk_jasa_purchase_receipt_reversal` (`reversal_of_id`),
  UNIQUE KEY `uk_jasa_purchase_receipt_transaction` (`id_transnk`),
  KEY `idx_jasa_purchase_receipt_detail` (`id_submission_detail`,`on_hand_date`),
  KEY `fk_jasa_po_receipt_request` (`kd_po_jasa`),
  KEY `fk_jasa_receipt_material` (`id_material`),
  KEY `fk_jasa_receipt_processor` (`processed_by`),
  CONSTRAINT `fk_jasa_po_receipt_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_purchase_receipt_transaction` FOREIGN KEY (`id_transnk`) REFERENCES `tbpo_transaksi` (`id_transnk`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_processor` FOREIGN KEY (`processed_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_reversal` FOREIGN KEY (`reversal_of_id`) REFERENCES `tbpo_jasa_purchase_receipt` (`id_purchase_receipt`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_submission_detail` FOREIGN KEY (`id_submission_detail`) REFERENCES `tbpo_jasa_purchase_submission_detail` (`id_submission_detail`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_purchase_submission` (
  `id_submission` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_spk` bigint(20) unsigned NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `spk_version_no` smallint(5) unsigned NOT NULL,
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
  UNIQUE KEY `uk_jasa_purchase_spk_version` (`kd_po_jasa`,`spk_version_no`),
  UNIQUE KEY `uk_jasa_purchase_po_header` (`id_po_nk`),
  UNIQUE KEY `uk_jasa_purchase_token` (`idempotency_token`),
  KEY `idx_jasa_purchase_request_status` (`kd_po_jasa`,`status`),
  KEY `fk_jasa_purchase_spk` (`id_spk`),
  KEY `fk_jasa_purchase_creator` (`created_by`),
  CONSTRAINT `fk_jasa_purchase_creator` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_purchase_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_purchase_spk` FOREIGN KEY (`id_spk`) REFERENCES `tbpo_jasa_spk` (`id_spk`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_purchase_submission_detail` (
  `id_submission_detail` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_submission` bigint(20) unsigned NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_material` bigint(20) unsigned NOT NULL,
  `id_draft_pembelian` bigint(20) unsigned NOT NULL,
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
  KEY `idx_jasa_purchase_material` (`id_material`,`fulfillment_status`),
  KEY `fk_jasa_purchase_detail_header` (`id_submission`),
  KEY `fk_jasa_purchase_detail_request` (`kd_po_jasa`),
  CONSTRAINT `fk_jasa_purchase_detail_draft` FOREIGN KEY (`id_draft_pembelian`) REFERENCES `tbpo_jasa_draft_pembelian` (`id_draft_pembelian`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_purchase_detail_header` FOREIGN KEY (`id_submission`) REFERENCES `tbpo_jasa_purchase_submission` (`id_submission`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_purchase_detail_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_purchase_detail_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_request` (
  `id_po_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `no_spk` varchar(30) DEFAULT NULL,
  `kd_vendor_jasa` varchar(20) DEFAULT NULL,
  `vendor_selection_reason` text DEFAULT NULL,
  `vendor_usulan` varchar(180) DEFAULT NULL,
  `kd_user` varchar(50) NOT NULL,
  `nm_user` varchar(120) NOT NULL,
  `departemen` varchar(100) NOT NULL,
  `tgl_request` date NOT NULL,
  `tgl_target` date NOT NULL,
  `tgl_mulai_pekerjaan` date DEFAULT NULL,
  `tgl_selesai_pekerjaan` date DEFAULT NULL,
  `lokasi_pekerjaan` varchar(180) DEFAULT NULL,
  `tujuan_pekerjaan` text NOT NULL,
  `catatan_pic` text DEFAULT NULL,
  `estimasi_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `estimasi_purchasing` decimal(18,2) DEFAULT NULL,
  `estimasi_total_jasa` decimal(18,2) NOT NULL DEFAULT 0.00,
  `estimasi_total_bahan` decimal(18,2) NOT NULL DEFAULT 0.00,
  `estimasi_disetujui` decimal(18,2) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'DRAFT',
  `status_version` int(10) unsigned NOT NULL DEFAULT 1,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `edit_revision_no` smallint(5) unsigned DEFAULT NULL,
  `requires_dirut_ops` tinyint(1) NOT NULL DEFAULT 0,
  `dirut_ops_approved_revision` smallint(5) unsigned DEFAULT NULL,
  `acc_with_kadep` varchar(50) DEFAULT NULL,
  `acc_at_kadep` datetime DEFAULT NULL,
  `acc_with_direktur_oprasional` varchar(50) DEFAULT NULL,
  `acc_at_direktur_oprasional` datetime DEFAULT NULL,
  `reviewed_by_purchasing` varchar(50) DEFAULT NULL,
  `reviewed_at_purchasing` datetime DEFAULT NULL,
  `purchasing_note` text DEFAULT NULL,
  `purchasing_revision_saved_at` datetime DEFAULT NULL,
  `purchasing_revision_status` varchar(40) DEFAULT NULL,
  `submitted_by_purchasing` varchar(50) DEFAULT NULL,
  `submitted_at_purchasing` datetime DEFAULT NULL,
  `draft_saved_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `acc_with_direktur` varchar(50) DEFAULT NULL,
  `acc_at_direktur` datetime DEFAULT NULL,
  `generated_by` varchar(50) DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_po_jasa`),
  UNIQUE KEY `uk_tbpo_jasa_request_kd` (`kd_po_jasa`),
  UNIQUE KEY `uk_tbpo_jasa_request_spk` (`no_spk`),
  KEY `idx_tbpo_jasa_request_vendor` (`kd_vendor_jasa`),
  KEY `idx_tbpo_jasa_request_status` (`status`),
  KEY `idx_tbpo_jasa_request_user` (`kd_user`),
  KEY `idx_tbpo_jasa_request_dept` (`departemen`),
  KEY `idx_tbpo_jasa_request_tgl` (`tgl_request`,`tgl_target`),
  KEY `idx_jasa_request_status_dept` (`status`,`departemen`),
  KEY `idx_jasa_request_user_status` (`kd_user`,`status`),
  KEY `idx_jasa_request_dirop_status` (`requires_dirut_ops`,`status`),
  KEY `idx_jasa_request_owner_deleted` (`kd_user`,`deleted_at`,`id_po_jasa`),
  KEY `idx_jasa_request_owner_status_date` (`kd_user`,`status`,`tgl_request`),
  KEY `idx_jasa_request_workflow` (`status`,`departemen`,`deleted_at`,`id_po_jasa`),
  KEY `idx_jasa_request_execution` (`status`,`completed_at`,`closed_at`),
  KEY `fk_jasa_request_closed_user` (`closed_by`),
  KEY `idx_jasa_request_pic_confirmation` (`status`,`kd_user`,`deleted_at`,`id_po_jasa`),
  CONSTRAINT `fk_jasa_request_closed_user` FOREIGN KEY (`closed_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tbpo_jasa_request_vendor` FOREIGN KEY (`kd_vendor_jasa`) REFERENCES `tbpo_jasa_vendor` (`kd_vendor_jasa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_request_detail` (
  `id_detail_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `jenis_detail` enum('JASA','BAHAN') NOT NULL DEFAULT 'JASA',
  `nama_pekerjaan` varchar(180) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(40) DEFAULT NULL,
  `hrg_satuan` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_harga` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_nyata` decimal(18,2) NOT NULL DEFAULT 0.00,
  `keterangan_purchasing` text DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_detail_jasa`),
  KEY `idx_tbpo_jasa_detail_kd_po` (`kd_po_jasa`),
  CONSTRAINT `fk_tbpo_jasa_detail_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_revisi` (
  `id_revisi` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL,
  `revision_source` varchar(40) NOT NULL,
  `alasan_revisi` text NOT NULL,
  `snapshot_json` longtext DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_revisi`),
  UNIQUE KEY `uk_jasa_revisi_number` (`kd_po_jasa`,`revision_no`),
  KEY `fk_jasa_revisi_requester` (`requested_by`),
  KEY `fk_jasa_revisi_submitter` (`submitted_by`),
  KEY `idx_jasa_revisi_open` (`kd_po_jasa`,`revision_no`,`submitted_at`),
  CONSTRAINT `fk_jasa_revisi_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_revisi_requester` FOREIGN KEY (`requested_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_revisi_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_scope` (
  `id_scope` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `line_no` int(10) unsigned NOT NULL,
  `jenis_scope` varchar(10) NOT NULL DEFAULT 'JASA',
  `nama_scope` varchar(180) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(40) DEFAULT NULL,
  `harga_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_pembanding_purchasing` decimal(18,2) DEFAULT NULL,
  `total_estimasi` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_nyata` decimal(18,2) NOT NULL DEFAULT 0.00,
  `keterangan_purchasing` text DEFAULT NULL,
  `added_by_purchasing` tinyint(1) NOT NULL DEFAULT 0,
  `legacy_detail_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `deleted_reason_purchasing` text DEFAULT NULL,
  PRIMARY KEY (`id_scope`),
  UNIQUE KEY `uk_jasa_scope_line` (`kd_po_jasa`,`revision_no`,`line_no`),
  UNIQUE KEY `uk_jasa_scope_legacy` (`legacy_detail_id`),
  KEY `idx_jasa_scope_request_type` (`kd_po_jasa`,`jenis_scope`),
  KEY `idx_jasa_scope_revision_active` (`kd_po_jasa`,`revision_no`,`is_active`,`line_no`),
  CONSTRAINT `fk_jasa_scope_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk` (
  `id_spk` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `spk_version_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `issued_by` int(11) NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `snapshot_json` longtext NOT NULL,
  `template_version` varchar(30) NOT NULL DEFAULT 'DRAFT-FOUNDATION',
  `document_status` varchar(20) NOT NULL DEFAULT 'ISSUED',
  PRIMARY KEY (`id_spk`),
  UNIQUE KEY `uk_jasa_spk_request` (`kd_po_jasa`),
  UNIQUE KEY `uk_jasa_spk_number` (`no_spk`),
  KEY `fk_jasa_spk_issuer` (`issued_by`),
  CONSTRAINT `fk_jasa_spk_issuer` FOREIGN KEY (`issued_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_spk_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_archive` (
  `id_spk_archive` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_spk_source` bigint(20) unsigned NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `request_revision_no` smallint(5) unsigned NOT NULL,
  `spk_version_no` smallint(5) unsigned NOT NULL,
  `issued_by` int(11) NOT NULL,
  `issued_at` datetime NOT NULL,
  `snapshot_json` longtext NOT NULL,
  `template_version` varchar(30) NOT NULL,
  `archived_by` int(11) NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_spk_archive`),
  UNIQUE KEY `uk_jasa_spk_archive_version` (`kd_po_jasa`,`spk_version_no`),
  KEY `idx_jasa_spk_archive_number` (`no_spk`,`spk_version_no`),
  KEY `fk_jasa_spk_archive_actor` (`archived_by`),
  CONSTRAINT `fk_jasa_spk_archive_actor` FOREIGN KEY (`archived_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_spk_archive_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_change` (
  `id_spk_change` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_spk` bigint(20) unsigned NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `from_version_no` smallint(5) unsigned NOT NULL,
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
  `supersedes_change_id` bigint(20) unsigned DEFAULT NULL,
  `final_decision_by` int(11) DEFAULT NULL,
  `final_decision_at` datetime DEFAULT NULL,
  `final_note` text DEFAULT NULL,
  `row_version` int(10) unsigned NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `payload_hash` char(64) DEFAULT NULL,
  PRIMARY KEY (`id_spk_change`),
  UNIQUE KEY `uk_jasa_spk_change_token` (`submit_token`),
  KEY `idx_jasa_spk_change_work` (`kd_po_jasa`,`status`,`current_stage`),
  KEY `fk_jasa_spk_change_spk` (`id_spk`),
  KEY `fk_jasa_spk_change_requester` (`requested_by`),
  KEY `fk_jasa_spk_change_supersedes` (`supersedes_change_id`),
  KEY `fk_jasa_spk_change_decider` (`final_decision_by`),
  CONSTRAINT `fk_jasa_spk_change_decider` FOREIGN KEY (`final_decision_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_spk_change_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_spk_change_requester` FOREIGN KEY (`requested_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_spk_change_spk` FOREIGN KEY (`id_spk`) REFERENCES `tbpo_jasa_spk` (`id_spk`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_spk_change_supersedes` FOREIGN KEY (`supersedes_change_id`) REFERENCES `tbpo_jasa_spk_change` (`id_spk_change`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_change_approval` (
  `id_change_approval` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_spk_change` bigint(20) unsigned NOT NULL,
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
  KEY `idx_jasa_change_approval_header` (`id_spk_change`,`created_at`),
  KEY `fk_jasa_change_approval_actor` (`actor_id`),
  CONSTRAINT `fk_jasa_change_approval_actor` FOREIGN KEY (`actor_id`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_change_approval_header` FOREIGN KEY (`id_spk_change`) REFERENCES `tbpo_jasa_spk_change` (`id_spk_change`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_change_item` (
  `id_change_item` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_spk_change` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(20) NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `field_name` varchar(60) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `quantity_impact` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cost_impact` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_change_item`),
  KEY `idx_jasa_change_item_header` (`id_spk_change`,`entity_type`,`entity_id`),
  CONSTRAINT `fk_jasa_change_item_header` FOREIGN KEY (`id_spk_change`) REFERENCES `tbpo_jasa_spk_change` (`id_spk_change`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_spk_revision` (
  `id_spk_revision` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `no_spk` varchar(40) NOT NULL,
  `from_version_no` smallint(5) unsigned NOT NULL,
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
  `submit_count` smallint(5) unsigned NOT NULL DEFAULT 1,
  `row_version` int(10) unsigned NOT NULL DEFAULT 1,
  `submit_token` char(36) NOT NULL,
  `decision_token` char(36) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_spk_revision`),
  UNIQUE KEY `uk_jasa_spk_revision_submit_token` (`submit_token`),
  UNIQUE KEY `uk_jasa_spk_revision_decision_token` (`decision_token`),
  KEY `idx_jasa_spk_revision_work` (`kd_po_jasa`,`status`,`id_spk_revision`),
  KEY `fk_jasa_spk_revision_requester` (`requested_by`),
  KEY `fk_jasa_spk_revision_decider` (`decision_by`),
  CONSTRAINT `fk_jasa_spk_revision_decider` FOREIGN KEY (`decision_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_spk_revision_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_spk_revision_requester` FOREIGN KEY (`requested_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_stock_allocation` (
  `id_allocation` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `id_material` bigint(20) unsigned NOT NULL,
  `id_brg_nk` int(11) NOT NULL,
  `qty_allocation` decimal(18,2) NOT NULL DEFAULT 0.00,
  `qty_received` decimal(18,2) NOT NULL DEFAULT 0.00,
  `qty_released` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status_allocation` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `expired_at` datetime DEFAULT NULL,
  `allocation_token` char(36) DEFAULT NULL,
  `allocated_by` int(11) NOT NULL,
  `allocated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `released_by` int(11) DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `release_token` char(36) DEFAULT NULL,
  `release_reason` text DEFAULT NULL,
  PRIMARY KEY (`id_allocation`),
  UNIQUE KEY `uk_jasa_allocation_token` (`allocation_token`),
  UNIQUE KEY `uk_jasa_allocation_release_token` (`release_token`),
  KEY `idx_jasa_allocation_stock` (`id_brg_nk`,`status_allocation`),
  KEY `idx_jasa_allocation_request` (`kd_po_jasa`,`status_allocation`),
  KEY `fk_jasa_allocation_user` (`allocated_by`),
  KEY `idx_jasa_allocation_material_status` (`id_material`,`status_allocation`),
  CONSTRAINT `fk_jasa_allocation_barang` FOREIGN KEY (`id_brg_nk`) REFERENCES `tbpo_barang_nk` (`id_brg_nk`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_allocation_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_allocation_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_allocation_user` FOREIGN KEY (`allocated_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_stock_receipt` (
  `id_receipt` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `no_receipt` varchar(40) NOT NULL,
  `kd_po_jasa` varchar(30) NOT NULL,
  `receipt_at` datetime NOT NULL,
  `confirmed_by` int(11) NOT NULL,
  `confirmed_name` varchar(120) NOT NULL,
  `catatan` text DEFAULT NULL,
  `idempotency_token` char(36) NOT NULL,
  `payload_hash` char(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_receipt`),
  UNIQUE KEY `uk_jasa_receipt_number` (`no_receipt`),
  UNIQUE KEY `uk_jasa_receipt_token` (`idempotency_token`),
  KEY `idx_jasa_receipt_request` (`kd_po_jasa`,`receipt_at`),
  KEY `fk_jasa_receipt_user` (`confirmed_by`),
  CONSTRAINT `fk_jasa_receipt_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_user` FOREIGN KEY (`confirmed_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_stock_receipt_detail` (
  `id_receipt_detail` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_receipt` bigint(20) unsigned NOT NULL,
  `id_allocation` bigint(20) unsigned NOT NULL,
  `id_material` bigint(20) unsigned NOT NULL,
  `id_brg_nk` int(11) NOT NULL,
  `qty_received` decimal(18,2) NOT NULL DEFAULT 0.00,
  `id_transnk` int(11) DEFAULT NULL,
  `posting_token` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_receipt_detail`),
  UNIQUE KEY `uk_jasa_receipt_posting` (`posting_token`),
  UNIQUE KEY `uk_jasa_receipt_transaction` (`id_transnk`),
  KEY `idx_jasa_receipt_allocation` (`id_allocation`),
  KEY `fk_jasa_receipt_detail_header` (`id_receipt`),
  KEY `fk_jasa_receipt_detail_material` (`id_material`),
  KEY `fk_jasa_receipt_detail_barang` (`id_brg_nk`),
  CONSTRAINT `fk_jasa_receipt_detail_allocation` FOREIGN KEY (`id_allocation`) REFERENCES `tbpo_jasa_stock_allocation` (`id_allocation`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_detail_barang` FOREIGN KEY (`id_brg_nk`) REFERENCES `tbpo_barang_nk` (`id_brg_nk`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_detail_header` FOREIGN KEY (`id_receipt`) REFERENCES `tbpo_jasa_stock_receipt` (`id_receipt`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_detail_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_receipt_detail_transaction` FOREIGN KEY (`id_transnk`) REFERENCES `tbpo_transaksi` (`id_transnk`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_vendor` (
  `id_vendor_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_vendor_jasa` varchar(20) NOT NULL,
  `source_type` varchar(20) NOT NULL DEFAULT 'MANUAL',
  `source_supplier_code` varchar(25) DEFAULT NULL,
  `vendor_type` varchar(20) NOT NULL DEFAULT 'VENDOR',
  `nama_vendor` varchar(150) NOT NULL,
  `nama_vendor_normalized` varchar(150) DEFAULT NULL,
  `kategori_jasa` varchar(100) DEFAULT NULL,
  `nama_pic` varchar(100) DEFAULT NULL,
  `no_telpon` varchar(50) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `alamat_vendor` text DEFAULT NULL,
  `npwp` varchar(80) DEFAULT NULL,
  `status_vendor` varchar(20) NOT NULL DEFAULT 'AKTIF',
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `created_name` varchar(120) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `requested_by` varchar(50) DEFAULT NULL,
  `requested_name` varchar(120) DEFAULT NULL,
  `requested_departemen` varchar(100) DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT NULL,
  `approved_name` varchar(120) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_vendor_jasa`),
  UNIQUE KEY `uk_tbpo_jasa_vendor_kd` (`kd_vendor_jasa`),
  UNIQUE KEY `uk_jasa_vendor_supplier_source` (`source_supplier_code`),
  KEY `idx_tbpo_jasa_vendor_status` (`status_vendor`),
  KEY `idx_tbpo_jasa_vendor_kategori` (`kategori_jasa`),
  KEY `idx_jasa_vendor_status_name` (`status_vendor`,`nama_vendor`),
  KEY `idx_jasa_vendor_source_type` (`source_type`,`status_vendor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_vendor_comparison` (
  `id_vendor_comparison` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `revision_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `id_vendor_jasa` int(11) DEFAULT NULL,
  `manual_vendor_name` varchar(180) DEFAULT NULL,
  `vendor_name_snapshot` varchar(180) NOT NULL,
  `quotation_no` varchar(100) DEFAULT NULL,
  `quotation_date` date DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `lead_time_days` int(10) unsigned DEFAULT NULL,
  `offer_detail` text DEFAULT NULL,
  `id_dokumen` bigint(20) unsigned DEFAULT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `selection_reason` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`id_vendor_comparison`),
  UNIQUE KEY `uk_jasa_vendor_comparison` (`kd_po_jasa`,`revision_no`,`id_vendor_jasa`),
  KEY `idx_jasa_vendor_comparison_selected` (`kd_po_jasa`,`revision_no`,`is_selected`),
  KEY `fk_jasa_vendor_comparison_document` (`id_dokumen`),
  KEY `fk_jasa_vendor_comparison_creator` (`created_by`),
  KEY `fk_jasa_vendor_comparison_vendor` (`id_vendor_jasa`),
  KEY `fk_jasa_vendor_comparison_updater` (`updated_by`),
  KEY `idx_jasa_vendor_comparison_active` (`kd_po_jasa`,`revision_no`,`is_active`),
  KEY `idx_jasa_comparison_manual_vendor` (`kd_po_jasa`,`revision_no`,`manual_vendor_name`),
  CONSTRAINT `fk_jasa_vendor_comparison_creator` FOREIGN KEY (`created_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_vendor_comparison_document` FOREIGN KEY (`id_dokumen`) REFERENCES `tbpo_jasa_dokumen` (`id_dokumen`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_vendor_comparison_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_vendor_comparison_updater` FOREIGN KEY (`updated_by`) REFERENCES `tbpo_user` (`id_user`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jasa_vendor_comparison_vendor` FOREIGN KEY (`id_vendor_jasa`) REFERENCES `tbpo_jasa_vendor` (`id_vendor_jasa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_kat_br` (
  `id_kat_br` int(11) NOT NULL AUTO_INCREMENT,
  `kd_kat` varchar(25) NOT NULL,
  `nama_kategori` text NOT NULL,
  `keterangan` text NOT NULL,
  PRIMARY KEY (`id_kat_br`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_notetemplate` (
  `id_nt_template` int(11) NOT NULL AUTO_INCREMENT,
  `kd_nt_template` varchar(255) NOT NULL,
  `nama_note` text NOT NULL,
  `shipment_to` text NOT NULL,
  `alamat_ship` text NOT NULL,
  `cp_shipment` text NOT NULL,
  `no_cp` text NOT NULL,
  `ket_1` text NOT NULL,
  `ket_2` text NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_nt_template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_note_barang` (
  `id_nt_barang` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po` varchar(25) NOT NULL,
  `kd_suplier` varchar(25) NOT NULL,
  `isi_note` text NOT NULL,
  `color_box` text NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_nt_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_note_direktur` (
  `id_note` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po` varchar(25) NOT NULL,
  `isi_note` text NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `nama_user` text NOT NULL,
  `note_for` int(11) NOT NULL,
  `update_status` int(11) NOT NULL,
  `create_at` text NOT NULL,
  `log_create` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_note`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_note_pembelian` (
  `id_nt_pembelian` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po` varchar(25) NOT NULL,
  `keterangan` text NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_nt_pembelian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_nt_tmp_pembelian` (
  `id_tmp_nt_pembelian` int(11) NOT NULL AUTO_INCREMENT,
  `keterangan` text NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tmp_nt_pembelian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_po` (
  `id_po` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po` varchar(255) NOT NULL,
  `no_po` varchar(255) NOT NULL,
  `tgl_transaksi` date NOT NULL,
  `kd_suplier` varchar(25) NOT NULL,
  `jml_item` int(11) NOT NULL,
  `total_harga` double NOT NULL,
  `total_harga_diskon` double NOT NULL DEFAULT 0,
  `keterangan_harga_ppn` varchar(20) NOT NULL DEFAULT '',
  `total_harga_include` double NOT NULL DEFAULT 0,
  `total_harga_exlude` double NOT NULL DEFAULT 0,
  `total_harga_diskon_include` double NOT NULL DEFAULT 0,
  `total_harga_diskon_exlude` double NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL,
  `tax` int(11) NOT NULL,
  `hrg_pajak` double NOT NULL,
  `tmpo_pembayaran` int(11) NOT NULL,
  `gdg_pengiriman` varchar(255) NOT NULL,
  `acc_with` varchar(25) NOT NULL,
  `kd_printout_note` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tgl_perubahan_po` date DEFAULT NULL,
  `top` int(11) DEFAULT 0 COMMENT 'Term of Payment dalam hari',
  `id_suplier` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_po`),
  KEY `fk_tbpo_po_suplier` (`id_suplier`),
  CONSTRAINT `fk_tbpo_po_suplier` FOREIGN KEY (`id_suplier`) REFERENCES `tbpo_suplier` (`id_suplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_po_nk` (
  `id_po_nk` int(12) NOT NULL AUTO_INCREMENT,
  `jns_po` int(5) NOT NULL,
  `kd_po_nk` varchar(25) NOT NULL,
  `kd_po_req` varchar(25) NOT NULL,
  `nopo` text NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `nm_user` text NOT NULL,
  `tgl_transaksi` text NOT NULL,
  `jml_item` int(12) NOT NULL,
  `total_harga` int(25) NOT NULL,
  `status` varchar(25) NOT NULL,
  `departemen` text NOT NULL,
  `tj_pembelian` text NOT NULL,
  `tax` int(3) NOT NULL,
  `hrg_pajak` int(25) NOT NULL,
  `hrg_nyata` int(25) NOT NULL,
  `status_hrg_nyata` int(11) NOT NULL,
  `acc_with` varchar(25) NOT NULL,
  `acc_with_kadep` varchar(255) NOT NULL,
  `source_module` varchar(30) DEFAULT NULL,
  `source_reference` varchar(120) DEFAULT NULL,
  `source_spk_no` varchar(40) DEFAULT NULL,
  `source_spk_version` smallint(5) unsigned DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_po_nk`),
  UNIQUE KEY `uk_ponk_source_reference` (`source_module`,`source_reference`),
  KEY `idx_ponk_source_spk` (`source_module`,`source_spk_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_ratings` (
  `id_rating` int(11) NOT NULL AUTO_INCREMENT,
  `kd_user` varchar(25) NOT NULL,
  `kdq_rate` varchar(25) NOT NULL,
  `u_rate` int(11) NOT NULL,
  `inpt_by` varchar(25) NOT NULL,
  `create_at` datetime NOT NULL,
  `update_by` varchar(25) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_realisasi_detail_po_nk` (
  `id_realisasi_detail` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_nk` varchar(25) NOT NULL,
  `id_det_po_nk` int(11) NOT NULL,
  `qty_pengajuan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `harga_pengajuan` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_pengajuan` decimal(18,2) NOT NULL DEFAULT 0.00,
  `qty_nyata` decimal(15,2) NOT NULL DEFAULT 0.00,
  `harga_nyata` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_dipakai_lifo` enum('REALISASI','PENGAJUAN') NOT NULL DEFAULT 'REALISASI',
  `total_nyata` decimal(18,2) NOT NULL DEFAULT 0.00,
  `selisih_harga` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status_approval_harga` varchar(30) NOT NULL DEFAULT 'BELUM_INPUT',
  `alasan_realisasi` text DEFAULT NULL,
  `catatan_keputusan_harga` text DEFAULT NULL,
  `created_by` varchar(25) NOT NULL DEFAULT '',
  `updated_by` varchar(25) NOT NULL DEFAULT '',
  `approved_by` varchar(25) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_realisasi_detail`),
  UNIQUE KEY `uq_realisasi_detail_po_nk` (`id_det_po_nk`),
  KEY `idx_realisasi_detail_kd_po_nk` (`kd_po_nk`),
  KEY `idx_realisasi_detail_status_approval` (`status_approval_harga`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_realisasi_harganyata_log` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_nk` varchar(25) NOT NULL,
  `id_det_po_nk` int(11) NOT NULL DEFAULT 0,
  `aksi` varchar(40) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `kd_user` varchar(25) NOT NULL DEFAULT '',
  `nama_user` varchar(100) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_realisasi_po_nk` (`kd_po_nk`),
  KEY `idx_log_realisasi_detail` (`id_det_po_nk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_realisasi_po_nk` (
  `id_realisasi_po` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po_nk` varchar(25) NOT NULL,
  `status_realisasi` varchar(30) NOT NULL DEFAULT 'DRAFT',
  `created_by` varchar(25) NOT NULL DEFAULT '',
  `updated_by` varchar(25) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_realisasi_po`),
  UNIQUE KEY `uq_realisasi_po_nk` (`kd_po_nk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_req_masterbarang` (
  `id_reqmbarang` int(11) NOT NULL AUTO_INCREMENT,
  `nama_barang` text NOT NULL,
  `deskripsi` text NOT NULL,
  `satuan` int(11) NOT NULL,
  `req_by` varchar(25) NOT NULL,
  PRIMARY KEY (`id_reqmbarang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_req_nk` (
  `id_po_nk` int(12) NOT NULL AUTO_INCREMENT,
  `jns_po` int(5) NOT NULL,
  `kd_po_nk` varchar(25) NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `nm_user` text NOT NULL,
  `tgl_transaksi` text NOT NULL,
  `tgl_ambil` text NOT NULL,
  `jml_item` int(12) NOT NULL,
  `status` varchar(50) NOT NULL,
  `kadep_submitted_at` datetime DEFAULT NULL,
  `pickup_approval_requested_at` datetime DEFAULT NULL,
  `purchasing_opened_at` datetime DEFAULT NULL,
  `departemen` text NOT NULL,
  `tj_pembelian` text NOT NULL,
  `acc_with` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_po_nk`),
  UNIQUE KEY `uk_tbpo_req_nk_kd_po_nk` (`kd_po_nk`),
  KEY `idx_reqpic_kadep_timeout` (`status`,`kadep_submitted_at`),
  KEY `idx_reqpic_pickup_timeout` (`status`,`pickup_approval_requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_req_nk_supporting_file` (
  `id_supporting_file` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_nk` varchar(255) NOT NULL,
  `departemen` varchar(100) NOT NULL,
  `uploaded_by` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_original` varchar(180) NOT NULL,
  `mime_type` varchar(150) NOT NULL,
  `file_size_bytes` bigint(20) unsigned NOT NULL,
  `sha256` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_supporting_file`),
  KEY `idx_reqpic_supporting_kd_po_nk` (`kd_po_nk`),
  KEY `idx_reqpic_supporting_departemen` (`departemen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_satuan` (
  `id_satuan` int(11) NOT NULL AUTO_INCREMENT,
  `nm_satuan` text NOT NULL,
  PRIMARY KEY (`id_satuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_set_note` (
  `id_set_note` int(11) NOT NULL AUTO_INCREMENT,
  `note` text NOT NULL,
  PRIMARY KEY (`id_set_note`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_set_tax` (
  `id_tax` int(11) NOT NULL AUTO_INCREMENT,
  `nm_tax` int(11) NOT NULL,
  PRIMARY KEY (`id_tax`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_sosialisasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` int(11) NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `status_nkomersil` int(11) NOT NULL,
  `create_at` datetime NOT NULL,
  `done_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_stock_lifo_allocation` (
  `id_alokasi` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_transnk_keluar` int(11) NOT NULL,
  `id_batch` bigint(20) unsigned NOT NULL,
  `qty_alokasi` decimal(18,3) NOT NULL,
  `harga_satuan_snapshot` decimal(18,2) DEFAULT NULL,
  `nilai_alokasi` decimal(18,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_alokasi`),
  UNIQUE KEY `uk_lifo_alokasi_keluar_batch` (`id_transnk_keluar`,`id_batch`),
  KEY `idx_lifo_alokasi_batch` (`id_batch`),
  KEY `idx_lifo_alokasi_keluar` (`id_transnk_keluar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_stock_lifo_batch_nk` (
  `id_batch` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_transnk_masuk` int(11) NOT NULL,
  `id_detail_po_nk` int(11) DEFAULT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `kd_barangsys` varchar(25) NOT NULL DEFAULT '',
  `jenis_sumber` enum('PO','SALDO_AWAL','ADJUSTMENT','OPNAME','LAINNYA') NOT NULL,
  `referensi_sumber` varchar(100) NOT NULL DEFAULT '',
  `tgl_efektif` datetime NOT NULL,
  `qty_awal` decimal(18,3) NOT NULL,
  `qty_sisa` decimal(18,3) NOT NULL,
  `harga_satuan` decimal(18,2) DEFAULT NULL,
  `dasar_harga` enum('REALISASI','PENGAJUAN','MANUAL','BELUM_ADA') NOT NULL DEFAULT 'BELUM_ADA',
  `status_harga` enum('VALID','PERLU_HARGA') NOT NULL DEFAULT 'PERLU_HARGA',
  `status_batch` enum('AKTIF','HABIS','BATAL') NOT NULL DEFAULT 'AKTIF',
  `catatan_harga` varchar(255) NOT NULL DEFAULT '',
  `dibuat_oleh` varchar(25) NOT NULL DEFAULT 'SYSTEM',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_batch`),
  UNIQUE KEY `uk_lifo_batch_transaksi_masuk` (`id_transnk_masuk`),
  KEY `idx_lifo_batch_barang_aktif` (`kd_barang`,`status_batch`,`tgl_efektif`,`id_batch`),
  KEY `idx_lifo_batch_harga` (`status_harga`,`kd_barang`),
  KEY `idx_lifo_batch_detail_po` (`id_detail_po_nk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_stock_lifo_harga_log` (
  `id_log` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_batch` bigint(20) unsigned NOT NULL,
  `harga_lama` decimal(18,2) DEFAULT NULL,
  `harga_baru` decimal(18,2) DEFAULT NULL,
  `dasar_harga_lama` varchar(20) NOT NULL DEFAULT '',
  `dasar_harga_baru` varchar(20) NOT NULL DEFAULT '',
  `alasan` varchar(255) NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `idx_lifo_harga_log_batch` (`id_batch`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_stock_lifo_rebuild_issue` (
  `id_issue` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_transnk` int(11) DEFAULT NULL,
  `kd_barang` varchar(25) NOT NULL DEFAULT '',
  `jenis_issue` enum('PERLU_HARGA','STOK_MINUS_HISTORIS') NOT NULL,
  `qty_terdampak` decimal(18,3) NOT NULL DEFAULT 0.000,
  `pesan` varchar(255) NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` varchar(25) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_issue`),
  KEY `idx_lifo_issue_terbuka` (`resolved_at`,`jenis_issue`,`kd_barang`),
  KEY `idx_lifo_issue_transaksi` (`id_transnk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_suplier` (
  `id_suplier` int(11) NOT NULL AUTO_INCREMENT,
  `kd_suplier` varchar(25) NOT NULL,
  `nama_suplier` text NOT NULL,
  `alamat_suplier` text NOT NULL,
  `no_telpon` text NOT NULL,
  `no_fax` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `gbr_logo` varchar(255) NOT NULL,
  PRIMARY KEY (`id_suplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_tmp_diskon` (
  `id_tmp_diskon` int(11) NOT NULL AUTO_INCREMENT,
  `kd_suplier` varchar(25) NOT NULL,
  `nama_diskon` text NOT NULL,
  `nominal` double NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tmp_diskon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_tmp_item` (
  `id_tmp` int(11) NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(255) NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `nama_barang` text NOT NULL,
  `kode_suplier` varchar(255) NOT NULL,
  `satuan` varchar(255) NOT NULL,
  `qty` double NOT NULL,
  `isi` decimal(15,2) DEFAULT 0.00,
  `kemasan` decimal(15,2) DEFAULT 0.00,
  `qty_kecil` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `harga_satuan` double NOT NULL,
  `harga_satuan_exclude` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `harga_satuan_kecil` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `harga_satuan_kecil_exclude` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_harga` double NOT NULL,
  `is_bonus` varchar(50) NOT NULL DEFAULT '0',
  `keterangan_bonus` varchar(255) NOT NULL DEFAULT '',
  `keterangan_harga_ppn` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_tmp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_tmp_item_nk` (
  `id_tmp_nk` int(11) NOT NULL AUTO_INCREMENT,
  `jnis_po` int(11) NOT NULL,
  `nama_barang` text NOT NULL,
  `deskripsi` text NOT NULL,
  `keterangan` text NOT NULL,
  `qty` int(11) NOT NULL,
  `satuan` int(11) NOT NULL,
  `hrg_satuan` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `kd_bsys` varchar(25) NOT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `kat_barang` varchar(25) NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tmp_nk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_tmp_note_barang` (
  `id_nt_tmp_barang` int(11) NOT NULL AUTO_INCREMENT,
  `kd_suplier` varchar(25) NOT NULL,
  `isi_note` text NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_nt_tmp_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_tmp_po_shipment` (
  `kd_suplier` varchar(25) NOT NULL,
  `kd_printout_note` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`kd_suplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_tmp_tax` (
  `id_tmp_tax` int(11) NOT NULL AUTO_INCREMENT,
  `kd_suplier` varchar(25) NOT NULL,
  `tax` int(11) NOT NULL,
  PRIMARY KEY (`id_tmp_tax`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `tbpo_tracking_po` (
  `id_po_tracking` int(11) NOT NULL AUTO_INCREMENT,
  `kd_po` varchar(255) NOT NULL,
  `status` text NOT NULL,
  `createat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_po_tracking`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_transaksi` (
  `id_transnk` int(11) NOT NULL AUTO_INCREMENT,
  `kd_akun` varchar(25) NOT NULL,
  `kd_po_nk` varchar(25) NOT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `kd_barangsys` varchar(25) NOT NULL,
  `keterangan` text NOT NULL,
  `kat_barang` varchar(25) NOT NULL,
  `tr_qty` double NOT NULL,
  `satuan` int(11) NOT NULL,
  `inputer` varchar(25) NOT NULL,
  `req_by` varchar(25) NOT NULL,
  `tgl_transaksi` text NOT NULL,
  `create_at` date NOT NULL,
  `last_updated_by` varchar(25) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_transnk`),
  KEY `idx_transaksi_barang_akun` (`kd_barang`,`kd_akun`),
  KEY `idx_transaksi_barang_tanggal` (`kd_barang`,`tgl_transaksi`(10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_transaksi_tmp` (
  `id_transnk` int(11) NOT NULL AUTO_INCREMENT,
  `kd_akun` varchar(25) NOT NULL,
  `kd_po_nk` varchar(25) NOT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `kd_barangsys` varchar(25) NOT NULL,
  `kat_barang` varchar(25) NOT NULL,
  `tr_qty` double NOT NULL,
  `satuan` int(11) NOT NULL,
  `hrg_satuan` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `inputer` varchar(25) NOT NULL,
  `create_at` datetime NOT NULL,
  `last_updated_by` varchar(25) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_transnk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_transaksi_trashbin` (
  `id_trashbin` int(11) NOT NULL AUTO_INCREMENT,
  `kd_akun` varchar(25) NOT NULL,
  `kd_po_nk` varchar(25) NOT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `kd_barangsys` varchar(25) NOT NULL,
  `keterangan` text NOT NULL,
  `kat_barang` varchar(25) NOT NULL,
  `tr_qty` double NOT NULL,
  `satuan` int(11) NOT NULL,
  `inputer` varchar(25) NOT NULL,
  `req_by` varchar(25) NOT NULL,
  `tgl_transaksi` text NOT NULL,
  `create_at` date NOT NULL,
  `last_updated_by` varchar(25) NOT NULL,
  PRIMARY KEY (`id_trashbin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_user` (
  `id_user` int(12) NOT NULL AUTO_INCREMENT,
  `kode_user` varchar(25) NOT NULL,
  `nama_user` text NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `aksess_lv` int(5) NOT NULL,
  `departement` varchar(255) NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbq_module` (
  `id_qmodule` int(11) NOT NULL AUTO_INCREMENT,
  `kd_module` varchar(25) NOT NULL,
  `type_m` int(11) NOT NULL,
  `nm_module` text NOT NULL,
  `m_status` int(11) NOT NULL,
  `create_at` datetime NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lastupdated` varchar(25) NOT NULL,
  PRIMARY KEY (`id_qmodule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbq_nilaim` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kd_reviewq` varchar(25) NOT NULL,
  `kd_module` varchar(25) NOT NULL,
  `nilai` int(11) NOT NULL,
  `inputer` varchar(25) NOT NULL,
  `create_at` datetime NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbq_review_pic` (
  `id_review` int(11) NOT NULL AUTO_INCREMENT,
  `kd_module` varchar(25) NOT NULL,
  `kd_reviewq` varchar(25) NOT NULL,
  `isi_review` text NOT NULL,
  `nilai` int(11) NOT NULL,
  `inputer` varchar(25) NOT NULL,
  `create_at` datetime NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_review`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbq_review_q` (
  `id_reviewq` int(11) NOT NULL AUTO_INCREMENT,
  `kd_reviewq` varchar(25) NOT NULL,
  `kd_module` varchar(25) NOT NULL,
  `question` text NOT NULL,
  `inputer` varchar(25) NOT NULL,
  `create_at` datetime NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_reviewq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_stockbarangnk` AS select `x`.`kode_barangs` AS `kode_barangs`,`x`.`kode_barang` AS `kode_barang`,`x`.`nama_barang` AS `nama_barang`,`x`.`deskripsi` AS `deskripsi`,`x`.`gbr_barang` AS `gbr_barang`,`x`.`nama_lokasi` AS `nama_lokasi`,coalesce(`x`.`qty_in`,0) + coalesce(`x`.`adjqty_in`,0) AS `qty_in`,coalesce(`x`.`qty_out`,0) + coalesce(`x`.`adjqty_out`,0) AS `qty_out`,coalesce(`x`.`qty_in`,0) + coalesce(`x`.`adjqty_in`,0) - (coalesce(`x`.`qty_out`,0) + coalesce(`x`.`adjqty_out`,0)) AS `qty_ready`,`x`.`id_s` AS `id_satuan`,`x`.`satuan` AS `satuan`,`x`.`id_brg_nk` AS `id_brg_nk`,`x`.`kat_barang` AS `kat_barang` from (select `a`.`kd_barang` AS `kode_barangs`,`a`.`kd_br_adm` AS `kode_barang`,`a`.`nama_barang` AS `nama_barang`,`a`.`descnk` AS `deskripsi`,`l`.`nama_lokasi` AS `nama_lokasi`,`b`.`id_satuan` AS `id_s`,`b`.`nm_satuan` AS `satuan`,`a`.`gbr_barang` AS `gbr_barang`,`a`.`id_brg_nk` AS `id_brg_nk`,`a`.`kat_barang` AS `kat_barang`,(select sum(`d`.`tr_qty`) from `tbpo_transaksi` `d` where `d`.`kd_barang` = `a`.`kd_barang` and `d`.`kd_akun` = '11512') AS `qty_out`,(select sum(`d`.`tr_qty`) from `tbpo_transaksi` `d` where `d`.`kd_barang` = `a`.`kd_barang` and `d`.`kd_akun` = '11514') AS `adjqty_out`,(select sum(`e`.`tr_qty`) from `tbpo_transaksi` `e` where `e`.`kd_barang` = `a`.`kd_barang` and `e`.`kd_akun` = '11511') AS `qty_in`,(select sum(`e`.`tr_qty`) from `tbpo_transaksi` `e` where `e`.`kd_barang` = `a`.`kd_barang` and `e`.`kd_akun` = '11513') AS `adjqty_in` from (((`tbpo_barang_nk` `a` join `tbpo_satuan` `b` on(`b`.`id_satuan` = `a`.`satuan`)) join `tbpo_kat_br` `c` on(`c`.`kd_kat` = `a`.`kat_barang`)) left join `tbpo_barang_nk_lokasi` `l` on(`l`.`id_lokasi` = `a`.`kd_lokasi`)) group by `a`.`kd_barang`) `x`;

SET FOREIGN_KEY_CHECKS=1;
