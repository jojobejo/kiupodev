-- Read-only verification for the PO non-komersil, Request PIC, and PO Jasa deploy.
-- Run this after all migrations in the target database. It makes no changes.

SELECT
    required.table_name AS missing_table
FROM (
    SELECT 'tbpo_user' AS table_name
    UNION ALL SELECT 'tbpo_barang_nk'
    UNION ALL SELECT 'tbpo_po_nk'
    UNION ALL SELECT 'tbpo_detail_po_nk'
    UNION ALL SELECT 'tbpo_req_nk'
    UNION ALL SELECT 'tbpo_detail_req'
    UNION ALL SELECT 'tbpo_req_nk_supporting_file'
    UNION ALL SELECT 'tbpo_transaksi'
    UNION ALL SELECT 'tbpo_jasa_vendor'
    UNION ALL SELECT 'tbpo_jasa_request'
    UNION ALL SELECT 'tbpo_jasa_request_detail'
    UNION ALL SELECT 'tbpo_jasa_scope'
    UNION ALL SELECT 'tbpo_jasa_material'
    UNION ALL SELECT 'tbpo_jasa_dokumen'
    UNION ALL SELECT 'tbpo_jasa_approval'
    UNION ALL SELECT 'tbpo_jasa_draft_pembelian'
    UNION ALL SELECT 'tbpo_jasa_purchase_submission'
    UNION ALL SELECT 'tbpo_jasa_purchase_receipt'
    UNION ALL SELECT 'tbpo_jasa_spk'
    UNION ALL SELECT 'tbpo_jasa_pickup_request'
    UNION ALL SELECT 'tbpo_stock_lifo_batch_nk'
    UNION ALL SELECT 'tbpo_stock_lifo_allocation'
    UNION ALL SELECT 'tbpo_stock_lifo_harga_log'
    UNION ALL SELECT 'tbpo_stock_lifo_rebuild_issue'
    UNION ALL SELECT 'tbpo_realisasi_detail_po_nk'
) AS required
LEFT JOIN information_schema.tables AS existing_table
    ON existing_table.table_schema = DATABASE()
   AND existing_table.table_name = required.table_name
WHERE existing_table.table_name IS NULL
ORDER BY required.table_name;

SELECT
    required.table_name,
    required.column_name AS missing_column
FROM (
    SELECT 'tbpo_req_nk' AS table_name, 'pickup_approval_requested_at' AS column_name
    UNION ALL SELECT 'tbpo_req_nk', 'kadep_submitted_at'
    UNION ALL SELECT 'tbpo_req_nk', 'purchasing_opened_at'
    UNION ALL SELECT 'tbpo_jasa_request', 'status_version'
    UNION ALL SELECT 'tbpo_jasa_request', 'revision_no'
    UNION ALL SELECT 'tbpo_jasa_request', 'estimasi_total_jasa'
    UNION ALL SELECT 'tbpo_jasa_request', 'estimasi_total_bahan'
    UNION ALL SELECT 'tbpo_jasa_request_detail', 'jenis_detail'
    UNION ALL SELECT 'tbpo_jasa_request_detail', 'harga_nyata'
    UNION ALL SELECT 'tbpo_realisasi_detail_po_nk', 'harga_dipakai_lifo'
) AS required
LEFT JOIN information_schema.columns AS existing_column
    ON existing_column.table_schema = DATABASE()
   AND existing_column.table_name = required.table_name
   AND existing_column.column_name = required.column_name
WHERE existing_column.column_name IS NULL
ORDER BY required.table_name, required.column_name;
