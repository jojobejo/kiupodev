-- Structure only. Apply exclusively to an isolated test/target schema.
ALTER TABLE tbpo_jasa_pickup_request ADD COLUMN IF NOT EXISTS received_at datetime NULL;
ALTER TABLE tbpo_jasa_spk_change ADD COLUMN IF NOT EXISTS payload_hash char(64) NULL;
ALTER TABLE tbpo_jasa_evaluation MODIFY kd_vendor_jasa varchar(20) NULL,
 ADD COLUMN IF NOT EXISTS vendor_name_snapshot varchar(180) NOT NULL DEFAULT '',
 ADD COLUMN IF NOT EXISTS row_version int unsigned NOT NULL DEFAULT 1;
CREATE TABLE IF NOT EXISTS tbpo_jasa_evaluation_audit (
 id_evaluation_audit bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
 kd_po_jasa varchar(30) NOT NULL,
 id_evaluation_jasa int NOT NULL,
 actor_id int NOT NULL,
 idempotency_token char(36) NOT NULL,
 payload_hash char(64) NOT NULL,
 before_json longtext NULL,
 after_json longtext NOT NULL,
 created_at datetime NOT NULL DEFAULT current_timestamp(),
 UNIQUE KEY uk_jasa_evaluation_token (idempotency_token),
 CONSTRAINT fk_jasa_evaluation_audit_request FOREIGN KEY(kd_po_jasa) REFERENCES tbpo_jasa_request(kd_po_jasa),
 CONSTRAINT fk_jasa_evaluation_audit_evaluation FOREIGN KEY(id_evaluation_jasa) REFERENCES tbpo_jasa_evaluation(id_evaluation_jasa),
 CONSTRAINT fk_jasa_evaluation_audit_actor FOREIGN KEY(actor_id) REFERENCES tbpo_user(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
