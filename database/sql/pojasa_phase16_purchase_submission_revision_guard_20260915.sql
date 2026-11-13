-- PO Jasa Phase 16: prevent obsolete purchasing drafts from being submitted.
-- Safe to execute repeatedly on MariaDB 10.4+.

ALTER TABLE `tbpo_jasa_draft_pembelian`
  ADD INDEX IF NOT EXISTS `idx_jasa_draft_request_revision_status` (`kd_po_jasa`, `revision_no`, `status_draft`);
