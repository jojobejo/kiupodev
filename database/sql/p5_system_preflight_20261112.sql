-- P5 structural gate: read-only, all result sets must be empty. No historical data gate.
SELECT r.t,r.c missing_column FROM (
 SELECT 'tbpo_jasa_pickup_request' t,'received_at' c
 UNION ALL SELECT 'tbpo_jasa_pickup_detail','qty'
 UNION ALL SELECT 'tbpo_jasa_spk_change','payload_hash'
 UNION ALL SELECT 'tbpo_jasa_spk_change','old_snapshot_json'
 UNION ALL SELECT 'tbpo_jasa_spk_change','new_snapshot_json'
 UNION ALL SELECT 'tbpo_jasa_spk_revision','from_version_no'
 UNION ALL SELECT 'tbpo_jasa_spk_archive','snapshot_json'
 UNION ALL SELECT 'tbpo_jasa_evaluation','vendor_name_snapshot'
 UNION ALL SELECT 'tbpo_jasa_evaluation','row_version'
 UNION ALL SELECT 'tbpo_jasa_evaluation_audit','before_json'
 UNION ALL SELECT 'tbpo_jasa_evaluation_audit','after_json'
 UNION ALL SELECT 'tbpo_jasa_evaluation_audit','payload_hash'
) r LEFT JOIN information_schema.columns c ON c.table_schema=DATABASE() AND c.table_name=r.t AND c.column_name=r.c WHERE c.column_name IS NULL;
SELECT r.t,r.cols missing_unique_guard FROM (
 SELECT 'tbpo_jasa_pickup_request' t,'idempotency_token' cols
 UNION ALL SELECT 'tbpo_jasa_pickup_detail','id_pickup_request,id_material'
 UNION ALL SELECT 'tbpo_jasa_spk_change','submit_token'
 UNION ALL SELECT 'tbpo_jasa_spk_change_approval','action_token'
 UNION ALL SELECT 'tbpo_jasa_spk_revision','submit_token'
 UNION ALL SELECT 'tbpo_jasa_spk_revision','decision_token'
 UNION ALL SELECT 'tbpo_jasa_spk_archive','kd_po_jasa,spk_version_no'
 UNION ALL SELECT 'tbpo_jasa_evaluation','kd_po_jasa'
 UNION ALL SELECT 'tbpo_jasa_evaluation_audit','idempotency_token'
) r LEFT JOIN (
 SELECT table_name,GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') cols FROM information_schema.statistics
 WHERE table_schema=DATABASE() AND non_unique=0 GROUP BY table_name,index_name
) i ON i.table_name=r.t AND i.cols=r.cols WHERE i.table_name IS NULL;
SELECT table_name,engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN
 ('tbpo_jasa_pickup_request','tbpo_jasa_pickup_detail','tbpo_jasa_stock_allocation','tbpo_jasa_stock_receipt','tbpo_transaksi',
 'tbpo_jasa_spk','tbpo_jasa_spk_archive','tbpo_jasa_spk_change','tbpo_jasa_spk_change_item','tbpo_jasa_spk_change_approval','tbpo_jasa_spk_revision',
 'tbpo_jasa_evaluation','tbpo_jasa_evaluation_audit') AND engine<>'InnoDB';
SELECT r.c missing_audit_fk FROM (
 SELECT 'kd_po_jasa' c,'tbpo_jasa_request' p,'kd_po_jasa' pc
 UNION ALL SELECT 'id_evaluation_jasa','tbpo_jasa_evaluation','id_evaluation_jasa'
 UNION ALL SELECT 'actor_id','tbpo_user','id_user'
) r LEFT JOIN information_schema.key_column_usage k ON k.table_schema=DATABASE() AND k.table_name='tbpo_jasa_evaluation_audit'
 AND k.column_name=r.c AND k.referenced_table_name=r.p AND k.referenced_column_name=r.pc WHERE k.column_name IS NULL;
SELECT column_name,column_type FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='tbpo_jasa_evaluation'
 AND column_name IN ('kualitas_score','ketepatan_waktu_score','biaya_score','total_score') AND (data_type<>'decimal' OR numeric_scale<>2);
SELECT column_name,is_nullable FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='tbpo_jasa_evaluation' AND column_name='kd_vendor_jasa' AND is_nullable<>'YES';
