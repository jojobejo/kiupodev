-- Dokumen pendukung wajib untuk pengajuan request barang non-komersil oleh PIC.
-- File fisik disimpan pada: assets/request-pendukung/{DEPARTEMEN}/{YYYY-MM-DD}/

CREATE TABLE IF NOT EXISTS tbpo_req_nk_supporting_file (
    id_supporting_file INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kd_po_nk VARCHAR(255) NOT NULL,
    departemen VARCHAR(100) NOT NULL,
    uploaded_by VARCHAR(100) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_original VARCHAR(180) NOT NULL,
    mime_type VARCHAR(150) NOT NULL,
    file_size_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_supporting_file),
    KEY idx_reqpic_supporting_kd_po_nk (kd_po_nk),
    KEY idx_reqpic_supporting_departemen (departemen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
