-- Prevent different PIC submissions from sharing one non-commercial request.
-- Apply once, after resolving every row returned by the preflight query.

-- This query MUST return no rows before the UNIQUE index is added.
SELECT kd_po_nk, COUNT(*) AS total
FROM tbpo_req_nk
GROUP BY kd_po_nk
HAVING COUNT(*) > 1;

-- The database is the final concurrency guard. The application retries with
-- another number if a simultaneous request has already claimed the same code.
ALTER TABLE tbpo_req_nk
    ADD UNIQUE KEY uk_tbpo_req_nk_kd_po_nk (kd_po_nk);

-- Optional diagnostic: every request detail must belong to exactly one header.
SELECT d.kd_po_nk, COUNT(*) AS detail_total
FROM tbpo_detail_req d
LEFT JOIN tbpo_req_nk r ON r.kd_po_nk = d.kd_po_nk
WHERE r.id_po_nk IS NULL
GROUP BY d.kd_po_nk;
