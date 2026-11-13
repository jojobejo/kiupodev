-- P6 integration contract; read-only. Run together with P2/P3/P4/P5 gates.
-- Every result set must be empty; historical data completeness is deliberately separate.
SELECT required.t missing_table_or_engine FROM (
 SELECT 'tbpo_po' t UNION ALL SELECT 'tbpo_detail_po' UNION ALL SELECT 'tbpo_suplier'
 UNION ALL SELECT 'tbpo_notetemplate' UNION ALL SELECT 'tbpo_tmp_item' UNION ALL SELECT 'tbpo_tmp_po_shipment'
 UNION ALL SELECT 'tbpo_po_nk' UNION ALL SELECT 'tbpo_detail_po_nk'
 UNION ALL SELECT 'tbpo_req_nk' UNION ALL SELECT 'tbpo_detail_req' UNION ALL SELECT 'tbpo_transaksi'
 UNION ALL SELECT 'tbpo_jasa_request' UNION ALL SELECT 'tbpo_jasa_stock_allocation'
 UNION ALL SELECT 'tbpo_stock_lifo_batch_nk' UNION ALL SELECT 'tbpo_stock_lifo_allocation'
) required LEFT JOIN information_schema.tables t ON t.table_schema=DATABASE() AND t.table_name=required.t
 WHERE t.table_name IS NULL OR t.engine<>'InnoDB';
SELECT r.t,r.c missing_integration_column FROM (
 SELECT 'tbpo_po' t,'id_suplier' c UNION ALL SELECT 'tbpo_po','kd_printout_note'
 UNION ALL SELECT 'tbpo_tmp_po_shipment','kd_printout_note'
 UNION ALL SELECT 'tbpo_po_nk','source_module' UNION ALL SELECT 'tbpo_po_nk','source_reference'
 UNION ALL SELECT 'tbpo_detail_po_nk','source_material_id'
 UNION ALL SELECT 'tbpo_req_nk','pickup_approval_requested_at'
 UNION ALL SELECT 'tbpo_detail_req','sts_done' UNION ALL SELECT 'tbpo_transaksi','kd_akun'
 UNION ALL SELECT 'tbpo_jasa_stock_allocation','qty_allocation' UNION ALL SELECT 'tbpo_jasa_stock_allocation','qty_received'
 UNION ALL SELECT 'tbpo_jasa_stock_allocation','qty_released' UNION ALL SELECT 'tbpo_jasa_stock_allocation','status_allocation'
) r LEFT JOIN information_schema.columns c ON c.table_schema=DATABASE() AND c.table_name=r.t AND c.column_name=r.c WHERE c.column_name IS NULL;
SELECT r.t,r.cols missing_unique_guard FROM (
 SELECT 'tbpo_tmp_po_shipment' t,'kd_suplier' cols
 UNION ALL SELECT 'tbpo_req_nk','kd_po_nk'
 UNION ALL SELECT 'tbpo_po_nk','source_module,source_reference'
 UNION ALL SELECT 'tbpo_jasa_stock_allocation','allocation_token'
 UNION ALL SELECT 'tbpo_jasa_stock_receipt','idempotency_token'
) r LEFT JOIN (
 SELECT table_name,GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') cols
 FROM information_schema.statistics WHERE table_schema=DATABASE() AND non_unique=0 GROUP BY table_name,index_name
) i ON i.table_name=r.t AND i.cols=r.cols WHERE i.table_name IS NULL;
SELECT 'v_stockbarangnk' missing_stock_view FROM DUAL WHERE NOT EXISTS
 (SELECT 1 FROM information_schema.views WHERE table_schema=DATABASE() AND table_name='v_stockbarangnk');
SELECT table_name,column_name,referenced_table_schema cross_database_dependency
 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND referenced_table_schema IS NOT NULL AND referenced_table_schema<>DATABASE();
