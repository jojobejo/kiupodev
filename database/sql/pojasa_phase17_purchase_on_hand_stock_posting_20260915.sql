-- PO Jasa Phase 17: post Purchasing ON_HAND into the stock ledger.
-- An ON_HAND receipt increases non-commercial stock (11511).  Its reversal
-- decreases the same stock through the established adjustment-out account
-- (11514).  The reference makes the posting idempotent and auditable.

SET NAMES utf8mb4;

ALTER TABLE `tbpo_jasa_purchase_receipt`
  ADD COLUMN IF NOT EXISTS `id_transnk` int(11) DEFAULT NULL AFTER `processed_by`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_jasa_purchase_receipt_transaction` (`id_transnk`);

SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='tbpo_jasa_purchase_receipt'
    AND CONSTRAINT_NAME='fk_jasa_purchase_receipt_transaction')=0,
  'ALTER TABLE `tbpo_jasa_purchase_receipt` ADD CONSTRAINT `fk_jasa_purchase_receipt_transaction` FOREIGN KEY (`id_transnk`) REFERENCES `tbpo_transaksi` (`id_transnk`) ON UPDATE CASCADE ON DELETE RESTRICT',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
