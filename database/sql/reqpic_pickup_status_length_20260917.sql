-- The old 25-character column silently truncated
-- "MENUNGGU PENYERAHAN BARANG" to "MENUNGGU PENYERAHAN BARAN".
-- Keep the full workflow status and repair records affected before this migration.
ALTER TABLE `tbpo_req_nk`
    MODIFY `status` VARCHAR(50) NOT NULL;

UPDATE `tbpo_req_nk`
SET `status` = 'MENUNGGU PENYERAHAN BARANG'
WHERE TRIM(`status`) = 'MENUNGGU PENYERAHAN BARAN';
