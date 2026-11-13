SET @db_name := DATABASE();

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tbpo_jasa_request` ADD COLUMN `acc_with_direktur_oprasional` varchar(50) DEFAULT NULL AFTER `acc_at_kadep`',
    'SELECT ''acc_with_direktur_oprasional already exists'''
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'tbpo_jasa_request'
    AND COLUMN_NAME = 'acc_with_direktur_oprasional'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tbpo_jasa_request` ADD COLUMN `acc_at_direktur_oprasional` datetime DEFAULT NULL AFTER `acc_with_direktur_oprasional`',
    'SELECT ''acc_at_direktur_oprasional already exists'''
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'tbpo_jasa_request'
    AND COLUMN_NAME = 'acc_at_direktur_oprasional'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `tbpo_user` (`kode_user`, `nama_user`, `username`, `password`, `aksess_lv`, `departement`)
SELECT
  'DIROP01',
  'Diana Wulandari',
  'dirop',
  '$2y$10$wzRz5Aw59SKjrx8CbBuNZeP765H5Ugxq2XcrW0.oCG9XXPhotlv.6',
  6,
  'DIREKTUR OPRASIONAL'
WHERE NOT EXISTS (
  SELECT 1 FROM `tbpo_user` WHERE `kode_user` = 'DIROP01' OR `username` = 'dirop'
);
