-- Vendor jasa description; follows the same prefix setting as the vendor master migration.
SET @vendor_prefix = COALESCE(@vendor_prefix, 'tbpo_');
SET @vendor_table = CONCAT(@vendor_prefix, 'vendor_jasa');
SET @description_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=@vendor_table AND column_name='deskripsi_vendor'), 'SELECT 1', CONCAT('ALTER TABLE `', @vendor_table, '` ADD COLUMN deskripsi_vendor TEXT NULL AFTER alamat_vendor'));
PREPARE vendor_stmt FROM @description_sql; EXECUTE vendor_stmt; DEALLOCATE PREPARE vendor_stmt;
