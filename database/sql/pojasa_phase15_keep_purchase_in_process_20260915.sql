-- PO Jasa Phase 15: pertahankan header PO Pembelian otomatis pada antrian monitoring.
-- Aman dijalankan berulang. Hanya memperbaiki header PO yang memang berasal dari PO Jasa.

SET NAMES utf8mb4;

UPDATE `tbpo_po_nk`
SET `status` = 'PROSES PEMBELIAN'
WHERE `source_module` = 'PO_JASA'
  AND `status` = 'DONE';
