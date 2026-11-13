ALTER TABLE tbpo_po
    ADD COLUMN IF NOT EXISTS id_suplier INT(11) NULL,
    ADD FOREIGN KEY IF NOT EXISTS fk_tbpo_po_suplier (id_suplier)
        REFERENCES tbpo_suplier (id_suplier);
