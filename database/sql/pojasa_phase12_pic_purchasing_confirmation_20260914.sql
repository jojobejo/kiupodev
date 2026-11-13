-- PO Jasa Phase 12: konfirmasi perubahan Purchasing oleh PIC.
-- Kolom status pada instalasi saat ini berupa VARCHAR; tidak diperlukan ALTER ENUM.
-- Jalankan setelah phase 11. Perubahan alur dicatat melalui tbpo_jasa_approval dan log aktivitas.

SET NAMES utf8mb4;
START TRANSACTION;

ALTER TABLE `tbpo_jasa_request`
  ADD INDEX IF NOT EXISTS `idx_jasa_request_pic_confirmation` (`status`, `kd_user`, `deleted_at`, `id_po_jasa`);

COMMIT;
