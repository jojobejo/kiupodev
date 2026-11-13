-- P4 structural acceptance. Read-only; all result sets must be empty.
-- Run after P2/P3 preflight. Historical data completeness is a separate migration gate.

SELECT r.table_name,r.column_name missing_column FROM (
SELECT 'tbpo_jasa_request' table_name,'completed_at' column_name
UNION ALL SELECT 'tbpo_jasa_request','closed_at'
UNION ALL SELECT 'tbpo_jasa_request','closed_by'
UNION ALL SELECT 'tbpo_jasa_request','status_version'
UNION ALL SELECT 'tbpo_jasa_progress','aktivitas'
UNION ALL SELECT 'tbpo_jasa_progress','id_dokumen_evidence'
UNION ALL SELECT 'tbpo_jasa_progress','created_by_user_id'
UNION ALL SELECT 'tbpo_jasa_progress','payload_hash'
UNION ALL SELECT 'tbpo_jasa_progress','idempotency_token'
UNION ALL SELECT 'tbpo_jasa_bast','revision_no'
UNION ALL SELECT 'tbpo_jasa_bast','spk_version_no'
UNION ALL SELECT 'tbpo_jasa_bast','idempotency_token'
UNION ALL SELECT 'tbpo_jasa_bast','payload_hash'
UNION ALL SELECT 'tbpo_jasa_bast','processed_by'
UNION ALL SELECT 'tbpo_jasa_payment','row_version'
UNION ALL SELECT 'tbpo_jasa_payment','updated_by'
UNION ALL SELECT 'tbpo_jasa_payment','updated_at'
UNION ALL SELECT 'tbpo_jasa_payment_audit','id_payment_jasa'
UNION ALL SELECT 'tbpo_jasa_payment_audit','kd_po_jasa'
UNION ALL SELECT 'tbpo_jasa_payment_audit','before_json'
UNION ALL SELECT 'tbpo_jasa_payment_audit','after_json'
UNION ALL SELECT 'tbpo_jasa_payment_audit','actor_id'
UNION ALL SELECT 'tbpo_jasa_payment_audit','idempotency_token'
UNION ALL SELECT 'tbpo_jasa_payment_audit','payload_hash'
) r LEFT JOIN information_schema.columns c
 ON c.table_schema=DATABASE() AND c.table_name=r.table_name AND c.column_name=r.column_name WHERE c.column_name IS NULL;

SELECT r.table_name,r.cols missing_unique_guard FROM (
SELECT 'tbpo_jasa_bast' table_name,'kd_po_jasa' cols
UNION ALL SELECT 'tbpo_jasa_bast','idempotency_token'
UNION ALL SELECT 'tbpo_jasa_payment','kd_po_jasa,no_invoice'
UNION ALL SELECT 'tbpo_jasa_payment_audit','idempotency_token'
UNION ALL SELECT 'tbpo_jasa_progress','idempotency_token'
) r LEFT JOIN (
 SELECT table_name,GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') cols
 FROM information_schema.statistics WHERE table_schema=DATABASE() AND non_unique=0 GROUP BY table_name,index_name
) i ON i.table_name=r.table_name AND i.cols=r.cols WHERE i.table_name IS NULL;

SELECT table_name,engine non_transactional_engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('tbpo_jasa_request','tbpo_jasa_progress','tbpo_jasa_bast','tbpo_jasa_payment','tbpo_jasa_payment_audit','tbpo_jasa_log_aktivitas','tbpo_jasa_notifikasi') AND engine<>'InnoDB';

SELECT table_name,column_name,column_type incorrect_money_type FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='tbpo_jasa_payment' AND column_name IN ('nominal_tagihan','nominal_bayar') AND (data_type<>'decimal' OR numeric_scale<>2 OR numeric_precision<>18);

SELECT r.col missing_payment_audit_foreign_key FROM (
SELECT 'id_payment_jasa' col,'tbpo_jasa_payment' parent,'id_payment_jasa' parent_col
UNION ALL SELECT 'kd_po_jasa','tbpo_jasa_request','kd_po_jasa'
UNION ALL SELECT 'actor_id','tbpo_user','id_user'
) r LEFT JOIN information_schema.key_column_usage k ON k.table_schema=DATABASE()
 AND k.table_name='tbpo_jasa_payment_audit' AND k.column_name=r.col AND k.referenced_table_name=r.parent AND k.referenced_column_name=r.parent_col WHERE k.column_name IS NULL;
