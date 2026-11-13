-- Structure only; apply to the local test database, never either original.
ALTER TABLE tbpo_jasa_request_detail
    ADD COLUMN IF NOT EXISTS jenis_detail ENUM('JASA','BAHAN') NOT NULL DEFAULT 'JASA' AFTER kd_po_jasa;
ALTER TABLE tbpo_jasa_biaya_aktual
    MODIFY COLUMN verification_status VARCHAR(50) NOT NULL DEFAULT 'BELUM_DIVERIFIKASI';
