-- P2 structure/system contract. Read-only; every result set must be empty.
-- Historical quantities, prices, or orphan records are migration concerns and
-- intentionally do not participate in this structural acceptance gate.

SELECT required.table_name AS missing_table
FROM (
    SELECT 'tbpo_user' AS table_name
    UNION ALL SELECT 'tbpo_barang_nk'
    UNION ALL SELECT 'tbpo_barang_nk_lokasi'
    UNION ALL SELECT 'tbpo_satuan'
    UNION ALL SELECT 'tbpo_kat_br'
    UNION ALL SELECT 'tbpo_req_nk'
    UNION ALL SELECT 'tbpo_detail_req'
    UNION ALL SELECT 'tbpo_req_nk_supporting_file'
    UNION ALL SELECT 'tbpo_po_nk'
    UNION ALL SELECT 'tbpo_detail_po_nk'
    UNION ALL SELECT 'tbpo_tmp_item_nk'
    UNION ALL SELECT 'tbpo_transaksi'
    UNION ALL SELECT 'tbpo_transaksi_tmp'
    UNION ALL SELECT 'tbpo_transaksi_trashbin'
    UNION ALL SELECT 'tbpo_generate_kd'
    UNION ALL SELECT 'tbpo_generate_kd_ponk'
    UNION ALL SELECT 'tbpo_req_masterbarang'
    UNION ALL SELECT 'tbpo_note_direktur'
    UNION ALL SELECT 'tbpo_note_pembelian'
    UNION ALL SELECT 'tbpo_file_nk'
    UNION ALL SELECT 'tbpo_file_bukti_beli'
    UNION ALL SELECT 'tbpo_realisasi_po_nk'
    UNION ALL SELECT 'tbpo_realisasi_detail_po_nk'
    UNION ALL SELECT 'tbpo_realisasi_harganyata_log'
    UNION ALL SELECT 'tbpo_stock_lifo_batch_nk'
    UNION ALL SELECT 'tbpo_stock_lifo_allocation'
    UNION ALL SELECT 'tbpo_stock_lifo_harga_log'
    UNION ALL SELECT 'tbpo_stock_lifo_rebuild_issue'
    UNION ALL SELECT 'v_stockbarangnk'
) required
LEFT JOIN information_schema.tables t
    ON t.table_schema=DATABASE() AND t.table_name=required.table_name
WHERE t.table_name IS NULL;

SELECT required.table_name, required.column_name AS missing_column
FROM (
    SELECT 'tbpo_req_nk' AS table_name, 'kadep_submitted_at' AS column_name
    UNION ALL SELECT 'tbpo_req_nk','pickup_approval_requested_at'
    UNION ALL SELECT 'tbpo_req_nk','purchasing_opened_at'
    UNION ALL SELECT 'tbpo_req_nk','acc_with'
    UNION ALL SELECT 'tbpo_detail_req','sts_done'
    UNION ALL SELECT 'tbpo_detail_req','status'
    UNION ALL SELECT 'tbpo_detail_req','kd_user'
    UNION ALL SELECT 'tbpo_po_nk','kd_po_req'
    UNION ALL SELECT 'tbpo_po_nk','acc_with_kadep'
    UNION ALL SELECT 'tbpo_detail_po_nk','kd_po_req'
    UNION ALL SELECT 'tbpo_detail_po_nk','hrg_satuan'
    UNION ALL SELECT 'tbpo_detail_po_nk','hrg_nyata'
    UNION ALL SELECT 'tbpo_barang_nk','minimum_stock'
    UNION ALL SELECT 'tbpo_realisasi_detail_po_nk','harga_dipakai_lifo'
    UNION ALL SELECT 'tbpo_stock_lifo_batch_nk','id_transnk_masuk'
    UNION ALL SELECT 'tbpo_stock_lifo_batch_nk','id_detail_po_nk'
    UNION ALL SELECT 'tbpo_stock_lifo_batch_nk','qty_sisa'
    UNION ALL SELECT 'tbpo_stock_lifo_batch_nk','status_harga'
    UNION ALL SELECT 'tbpo_stock_lifo_allocation','id_transnk_keluar'
    UNION ALL SELECT 'tbpo_stock_lifo_allocation','harga_satuan_snapshot'
    UNION ALL SELECT 'tbpo_stock_lifo_allocation','nilai_alokasi'
    UNION ALL SELECT 'v_stockbarangnk','qty_ready'
) required
LEFT JOIN information_schema.columns c
    ON c.table_schema=DATABASE() AND c.table_name=required.table_name AND c.column_name=required.column_name
WHERE c.column_name IS NULL;

SELECT table_name, column_name, column_type AS insufficient_status_capacity
FROM information_schema.columns
WHERE table_schema=DATABASE() AND table_name='tbpo_req_nk' AND column_name='status'
    AND (data_type NOT IN ('varchar','text','mediumtext','longtext') OR character_maximum_length<50);

SELECT required.table_name,required.columns_required AS missing_unique_guard
FROM (
    SELECT 'tbpo_req_nk' AS table_name,'kd_po_nk' AS columns_required
    UNION ALL SELECT 'tbpo_stock_lifo_batch_nk','id_transnk_masuk'
    UNION ALL SELECT 'tbpo_stock_lifo_allocation','id_transnk_keluar,id_batch'
    UNION ALL SELECT 'tbpo_realisasi_detail_po_nk','id_det_po_nk'
) required
LEFT JOIN (
    SELECT table_name,GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') AS columns_required
    FROM information_schema.statistics WHERE table_schema=DATABASE() AND non_unique=0
    GROUP BY table_name,index_name
) existing_guard ON existing_guard.table_name=required.table_name AND existing_guard.columns_required=required.columns_required
WHERE existing_guard.table_name IS NULL;

SELECT table_name,engine AS non_transactional_engine
FROM information_schema.tables WHERE table_schema=DATABASE()
    AND table_name IN ('tbpo_req_nk','tbpo_detail_req','tbpo_po_nk','tbpo_detail_po_nk',
        'tbpo_transaksi','tbpo_transaksi_tmp','tbpo_stock_lifo_batch_nk',
        'tbpo_stock_lifo_allocation','tbpo_stock_lifo_harga_log','tbpo_stock_lifo_rebuild_issue')
    AND engine<>'InnoDB';

SELECT table_name AS stock_view_references_original_database
FROM information_schema.views WHERE table_schema=DATABASE() AND table_name='v_stockbarangnk'
    AND (view_definition LIKE '%`kiucoid_kiupo_baru`.%' OR view_definition LIKE '%`kiucoid_po`.%');
