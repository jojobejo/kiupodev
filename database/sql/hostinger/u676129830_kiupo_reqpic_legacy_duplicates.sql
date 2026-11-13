-- The attached database has historical duplicate tbpo_req_nk.kd_po_nk values.
-- Do NOT run reqpic_prevent_cross_pic_merge_20260916.sql until each duplicate
-- has been reconciled manually; its UNIQUE index would reject this history.
--
-- This non-unique index keeps Request PIC lookups performant and preserves all
-- existing request headers. New request generation still checks whether a code
-- exists before it writes a header.

SET @database_name = DATABASE();
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tbpo_req_nk` ADD INDEX `idx_req_nk_kd_po_nk` (`kd_po_nk`)',
    'SELECT "Index idx_req_nk_kd_po_nk already exists"'
  )
  FROM information_schema.statistics
  WHERE table_schema = @database_name
    AND table_name = 'tbpo_req_nk'
    AND index_name = 'idx_req_nk_kd_po_nk'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT kd_po_nk, COUNT(*) AS historical_duplicate_count
FROM tbpo_req_nk
GROUP BY kd_po_nk
HAVING COUNT(*) > 1
ORDER BY kd_po_nk;
