-- Set @vendor_prefix before importing for a custom installation (e.g. SET @vendor_prefix='kiupo_';).
-- Default follows this application's tbpo_ table prefix. Existing vendor IDs and FK references are preserved.
SET @vendor_prefix = COALESCE(@vendor_prefix, 'tbpo_');
SET @old_vendor = CONCAT(@vendor_prefix, 'jasa_vendor');
SET @new_vendor = CONCAT(@vendor_prefix, 'vendor_jasa');
SET @rename_vendor = IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=@old_vendor) AND NOT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=@new_vendor), CONCAT('RENAME TABLE `', @old_vendor, '` TO `', @new_vendor, '`'), 'SELECT 1');
PREPARE vendor_stmt FROM @rename_vendor; EXECUTE vendor_stmt; DEALLOCATE PREPARE vendor_stmt;
SET @create_vendor = REPLACE('CREATE TABLE IF NOT EXISTS `tbpo_vendor_jasa` (
  `id_vendor_jasa` int(11) NOT NULL AUTO_INCREMENT,
  `kd_vendor_jasa` varchar(20) NOT NULL,
  `source_type` varchar(20) NOT NULL DEFAULT ''MANUAL'',
  `source_supplier_code` varchar(25) DEFAULT NULL,
  `vendor_type` varchar(20) NOT NULL DEFAULT ''VENDOR'',
  `nama_vendor` varchar(150) NOT NULL,
  `nama_vendor_normalized` varchar(150) DEFAULT NULL,
  `kategori_jasa` varchar(100) DEFAULT NULL,
  `nama_pic` varchar(100) DEFAULT NULL,
  `no_telpon` varchar(50) DEFAULT NULL,
  `no_fax` varchar(50) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `alamat_vendor` text DEFAULT NULL,
  `npwp` varchar(80) DEFAULT NULL,
  `status_vendor` varchar(20) NOT NULL DEFAULT ''AKTIF'',
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
  UNIQUE KEY `uk_tbpo_vendor_jasa_kd` (`kd_vendor_jasa`),
  UNIQUE KEY `uk_jasa_vendor_supplier_source` (`source_supplier_code`),
  KEY `idx_tbpo_vendor_jasa_status` (`status_vendor`),
  KEY `idx_tbpo_vendor_jasa_kategori` (`kategori_jasa`),
  KEY `idx_jasa_vendor_status_name` (`status_vendor`,`nama_vendor`),
  KEY `idx_jasa_vendor_source_type` (`source_type`,`status_vendor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;', 'tbpo_', @vendor_prefix);
PREPARE vendor_stmt FROM @create_vendor; EXECUTE vendor_stmt; DEALLOCATE PREPARE vendor_stmt;
SET @fax_vendor = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=@new_vendor AND column_name='no_fax'), 'SELECT 1', CONCAT('ALTER TABLE `', @new_vendor, '` ADD COLUMN no_fax varchar(50) NULL AFTER email'));
PREPARE vendor_stmt FROM @fax_vendor; EXECUTE vendor_stmt; DEALLOCATE PREPARE vendor_stmt;
