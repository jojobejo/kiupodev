CREATE TABLE IF NOT EXISTS tbpo_tmp_po_shipment (
    kd_suplier VARCHAR(25) NOT NULL,
    kd_printout_note VARCHAR(25) NOT NULL DEFAULT '',
    PRIMARY KEY (kd_suplier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
