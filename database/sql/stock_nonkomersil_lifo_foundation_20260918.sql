-- Fondasi penilaian persediaan non-komersil dengan metode LIFO.
-- Jalankan sekali pada database target sebelum menjalankan:
-- php index.php cli/StockLifoRebuild --confirm

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `tbpo_stock_lifo_batch_nk` (
  `id_batch` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_transnk_masuk` int(11) NOT NULL,
  `id_detail_po_nk` int(11) DEFAULT NULL,
  `kd_barang` varchar(25) NOT NULL,
  `kd_barangsys` varchar(25) NOT NULL DEFAULT '',
  `jenis_sumber` enum('PO','SALDO_AWAL','ADJUSTMENT','OPNAME','LAINNYA') NOT NULL,
  `referensi_sumber` varchar(100) NOT NULL DEFAULT '',
  `tgl_efektif` datetime NOT NULL,
  `qty_awal` decimal(18,3) NOT NULL,
  `qty_sisa` decimal(18,3) NOT NULL,
  `harga_satuan` decimal(18,2) DEFAULT NULL,
  `dasar_harga` enum('REALISASI','PENGAJUAN','MANUAL','BELUM_ADA') NOT NULL DEFAULT 'BELUM_ADA',
  `status_harga` enum('VALID','PERLU_HARGA') NOT NULL DEFAULT 'PERLU_HARGA',
  `status_batch` enum('AKTIF','HABIS','BATAL') NOT NULL DEFAULT 'AKTIF',
  `catatan_harga` varchar(255) NOT NULL DEFAULT '',
  `dibuat_oleh` varchar(25) NOT NULL DEFAULT 'SYSTEM',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_batch`),
  UNIQUE KEY `uk_lifo_batch_transaksi_masuk` (`id_transnk_masuk`),
  KEY `idx_lifo_batch_barang_aktif` (`kd_barang`,`status_batch`,`tgl_efektif`,`id_batch`),
  KEY `idx_lifo_batch_harga` (`status_harga`,`kd_barang`),
  KEY `idx_lifo_batch_detail_po` (`id_detail_po_nk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_stock_lifo_allocation` (
  `id_alokasi` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_transnk_keluar` int(11) NOT NULL,
  `id_batch` bigint unsigned NOT NULL,
  `qty_alokasi` decimal(18,3) NOT NULL,
  `harga_satuan_snapshot` decimal(18,2) DEFAULT NULL,
  `nilai_alokasi` decimal(18,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_alokasi`),
  UNIQUE KEY `uk_lifo_alokasi_keluar_batch` (`id_transnk_keluar`,`id_batch`),
  KEY `idx_lifo_alokasi_batch` (`id_batch`),
  KEY `idx_lifo_alokasi_keluar` (`id_transnk_keluar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_stock_lifo_harga_log` (
  `id_log` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_batch` bigint unsigned NOT NULL,
  `harga_lama` decimal(18,2) DEFAULT NULL,
  `harga_baru` decimal(18,2) DEFAULT NULL,
  `dasar_harga_lama` varchar(20) NOT NULL DEFAULT '',
  `dasar_harga_baru` varchar(20) NOT NULL DEFAULT '',
  `alasan` varchar(255) NOT NULL,
  `kd_user` varchar(25) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `idx_lifo_harga_log_batch` (`id_batch`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbpo_stock_lifo_rebuild_issue` (
  `id_issue` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_transnk` int(11) DEFAULT NULL,
  `kd_barang` varchar(25) NOT NULL DEFAULT '',
  `jenis_issue` enum('PERLU_HARGA','STOK_MINUS_HISTORIS') NOT NULL,
  `qty_terdampak` decimal(18,3) NOT NULL DEFAULT 0.000,
  `pesan` varchar(255) NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` varchar(25) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_issue`),
  KEY `idx_lifo_issue_terbuka` (`resolved_at`,`jenis_issue`,`kd_barang`),
  KEY `idx_lifo_issue_transaksi` (`id_transnk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
