-- P4 structure only. Select the LOCAL TEST database explicitly before running.
-- Historical duplicates must be dealt with in the later data migration.
ALTER TABLE tbpo_jasa_bast
    ADD COLUMN IF NOT EXISTS revision_no SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS spk_version_no SMALLINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS idempotency_token CHAR(36) NULL,
    ADD COLUMN IF NOT EXISTS payload_hash CHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS processed_by INT NULL,
    ADD UNIQUE INDEX IF NOT EXISTS uk_jasa_bast_token (idempotency_token);
ALTER TABLE tbpo_jasa_payment
    ADD COLUMN IF NOT EXISTS row_version INT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS updated_by INT NULL,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL,
    ADD UNIQUE INDEX IF NOT EXISTS uk_jasa_payment_invoice (kd_po_jasa,no_invoice);
CREATE TABLE IF NOT EXISTS tbpo_jasa_payment_audit (
    id_payment_audit BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_payment_jasa INT NOT NULL,
    kd_po_jasa VARCHAR(30) NOT NULL,
    event_type VARCHAR(20) NOT NULL,
    before_json LONGTEXT NULL,
    after_json LONGTEXT NOT NULL,
    actor_id INT NOT NULL,
    idempotency_token CHAR(36) NOT NULL,
    payload_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_payment_audit),
    UNIQUE KEY uk_jasa_payment_audit_token (idempotency_token),
    KEY idx_jasa_payment_audit_request (kd_po_jasa,id_payment_audit),
    CONSTRAINT fk_jasa_payment_audit_invoice FOREIGN KEY (id_payment_jasa) REFERENCES tbpo_jasa_payment(id_payment_jasa),
    CONSTRAINT fk_jasa_payment_audit_request FOREIGN KEY (kd_po_jasa) REFERENCES tbpo_jasa_request(kd_po_jasa),
    CONSTRAINT fk_jasa_payment_audit_actor FOREIGN KEY (actor_id) REFERENCES tbpo_user(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
