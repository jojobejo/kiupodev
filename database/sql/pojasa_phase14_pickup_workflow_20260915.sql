-- PO Jasa Phase 14: controlled pickup after all material is ready.
-- PO header status remains owned by tbpo_po_nk; these statuses are only for handover.
CREATE TABLE IF NOT EXISTS `tbpo_jasa_pickup_request` (
  `id_pickup_request` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kd_po_jasa` varchar(30) NOT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'MENUNGGU_ACC_KADEP',
  `requested_by` int(11) NOT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL,
  `prepared_at` datetime DEFAULT NULL,
  `preparation_note` text DEFAULT NULL,
  `idempotency_token` char(36) NOT NULL,
  PRIMARY KEY (`id_pickup_request`),
  UNIQUE KEY `uk_jasa_pickup_token` (`idempotency_token`),
  KEY `idx_jasa_pickup_request` (`kd_po_jasa`,`status`),
  CONSTRAINT `fk_jasa_pickup_request` FOREIGN KEY (`kd_po_jasa`) REFERENCES `tbpo_jasa_request` (`kd_po_jasa`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_jasa_pickup_detail` (
  `id_pickup_detail` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_pickup_request` bigint unsigned NOT NULL,
  `id_material` bigint unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id_pickup_detail`),
  UNIQUE KEY `uk_jasa_pickup_material` (`id_pickup_request`,`id_material`),
  CONSTRAINT `fk_jasa_pickup_detail_header` FOREIGN KEY (`id_pickup_request`) REFERENCES `tbpo_jasa_pickup_request` (`id_pickup_request`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_jasa_pickup_detail_material` FOREIGN KEY (`id_material`) REFERENCES `tbpo_jasa_material` (`id_material`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
