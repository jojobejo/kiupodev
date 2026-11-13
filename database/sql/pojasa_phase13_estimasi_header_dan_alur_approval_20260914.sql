-- PO Jasa Phase 13: pisahkan estimasi PIC/Purchasing dan sederhanakan approval.
-- Jalankan setelah Phase 12. Aman dijalankan berulang pada MariaDB 10.4+.
SET NAMES utf8mb4;
START TRANSACTION;

ALTER TABLE `tbpo_jasa_request`
  ADD COLUMN IF NOT EXISTS `estimasi_purchasing` decimal(18,2) DEFAULT NULL AFTER `estimasi_total`;

-- Review Purchasing hanya dilakukan pada tahap awal sebelum KADEP.
UPDATE `tbpo_jasa_request`
SET `status` = 'MENUNGGU_DIRUT_OPS', `status_version` = `status_version` + 1
WHERE `status` = 'MENUNGGU_PURCHASING'
  AND `deleted_at` IS NULL
  AND `acc_with_kadep` IS NOT NULL;

UPDATE `tbpo_jasa_request`
SET `status` = 'MENUNGGU_DIREKTUR', `status_version` = `status_version` + 1
WHERE `status` = 'MENUNGGU_PURCHASING_DIROPS'
  AND `deleted_at` IS NULL
  AND `acc_with_direktur_oprasional` IS NOT NULL;

UPDATE `tbpo_jasa_request`
SET `status` = 'REVISI_PIC',
    `status_version` = `status_version` + 1,
    `purchasing_revision_saved_at` = NULL,
    `purchasing_revision_status` = NULL
WHERE `status` IN ('REVISI_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT')
  AND `deleted_at` IS NULL;

UPDATE `tbpo_jasa_request`
SET `status` = 'MENUNGGU_KADEP', `status_version` = `status_version` + 1
WHERE `status` = 'MENUNGGU_PERSETUJUAN_PURCHASING'
  AND `deleted_at` IS NULL;

COMMIT;
