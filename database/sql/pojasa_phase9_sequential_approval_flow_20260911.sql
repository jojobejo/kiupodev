-- PO Jasa Phase 9: sequential approval workflow
-- PIC -> Purchasing -> KADEP -> Purchasing -> Direktur Operasional -> Purchasing -> Direktur
-- Target: MariaDB 10.4+, CodeIgniter 3
-- Safe to run repeatedly. Existing active requests are moved only when their history proves
-- that the corresponding new checkpoint has not already been completed.

SET NAMES utf8mb4;
START TRANSACTION;

-- Direktur Operasional kini wajib untuk seluruh departemen pada alur penerbitan awal.
UPDATE `tbpo_jasa_request`
SET `requires_dirut_ops` = 1
WHERE `deleted_at` IS NULL
  AND `status` IN (
    'DRAFT', 'REVISI_PIC', 'MENUNGGU_PURCHASING_AWAL', 'MENUNGGU_KADEP', 'PENDING_KADEP',
    'MENUNGGU_PURCHASING', 'REVISI_PURCHASING_DIROPS', 'MENUNGGU_DIRUT_OPS',
    'MENUNGGU_PURCHASING_DIROPS', 'REVISI_PURCHASING_DIRUT', 'MENUNGGU_DIREKTUR'
  )
  AND `requires_dirut_ops` <> 1;

-- Request lama yang belum diproses KADEP harus melewati pemeriksaan awal Purchasing.
UPDATE `tbpo_jasa_request` `r`
SET `r`.`status` = 'MENUNGGU_PURCHASING_AWAL',
    `r`.`status_version` = `r`.`status_version` + 1
WHERE `r`.`status` = 'MENUNGGU_KADEP'
  AND `r`.`deleted_at` IS NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `tbpo_jasa_approval` `a`
    WHERE `a`.`kd_po_jasa` = `r`.`kd_po_jasa`
      AND `a`.`revision_no` = `r`.`revision_no`
      AND `a`.`from_status` = 'MENUNGGU_PURCHASING_AWAL'
      AND `a`.`action` = 'SUBMIT'
  );

-- Request lama yang sudah mendapat ACC Direktur Operasional dibedakan sebagai review ketiga.
UPDATE `tbpo_jasa_request`
SET `status` = 'MENUNGGU_PURCHASING_DIROPS',
    `status_version` = `status_version` + 1
WHERE `status` = 'MENUNGGU_PURCHASING'
  AND `deleted_at` IS NULL
  AND `dirut_ops_approved_revision` = `revision_no`;

-- Alur lama departemen non-khusus dapat sudah berada di Direktur tanpa melewati DirOps.
UPDATE `tbpo_jasa_request`
SET `status` = 'MENUNGGU_DIRUT_OPS',
    `status_version` = `status_version` + 1
WHERE `status` = 'MENUNGGU_DIREKTUR'
  AND `deleted_at` IS NULL
  AND (`dirut_ops_approved_revision` IS NULL OR `dirut_ops_approved_revision` <> `revision_no`);

COMMIT;
