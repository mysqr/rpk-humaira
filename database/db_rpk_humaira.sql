-- ==============================================================
-- DATABASE: db_rpk_humaira
-- SISTEM INFORMASI PENJUALAN DAN PERSEDIAAN BERAS RPK HUMAIRA
-- MITRA RESMI PERUM BULOG
-- ==============================================================

SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `db_rpk_humaira` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_rpk_humaira`;

-- 1. TABEL PENGATURAN TOKO
DROP TABLE IF EXISTS `pengaturan`;
CREATE TABLE `pengaturan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_toko` VARCHAR(100) NOT NULL DEFAULT 'RPK Humaira',
  `mitra` VARCHAR(100) NOT NULL DEFAULT 'Rumah Pangan Kita - Perum BULOG',
  `alamat` TEXT NOT NULL,
  `telepon` VARCHAR(30) NOT NULL DEFAULT '0812-3456-7890',
  `nama_pemilik` VARCHAR(100) NOT NULL DEFAULT 'Hj. Humaira',
  `maks_sphp_harian_per_orang` INT NOT NULL DEFAULT 5, -- Maks 5 kemasan/hari/orang
  `kuota_mingguan_sphp_kg` DECIMAL(10,2) NOT NULL DEFAULT 2000.00, -- Batas 2 Ton / minggu
  `pesan_struk` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pengaturan` (`id`, `nama_toko`, `mitra`, `alamat`, `telepon`, `nama_pemilik`, `maks_sphp_harian_per_orang`, `kuota_mingguan_sphp_kg`, `pesan_struk`) VALUES
(1, 'RPK Humaira', 'Sahabat Rumah Pangan Kita (RPK) - Perum BULOG', 'Jl. Raya Pasar Induk No. 45, Sukamaju', '0812-9876-5432', 'Hj. Humaira', 5, 2000.00, 'PERHATIAN: Beras SPHP khusus untuk pembeli akhir (rumah tangga), dilarang dijual kembali ke pengecer/di atas HET. Terima Kasih!');

-- 2. TABEL USERS (MANAJEMEN PENGGUNA)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('pemilik', 'kasir') NOT NULL DEFAULT 'kasir',
  `no_hp` VARCHAR(25) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password hash:
-- pemilik / admin123 -> $2y$10$7W9y/iI5oh59HCbISZjdfuYlV21CnV0H6bjI/CVMQhhrWto8CwwFC
-- kasir1 / kasir123  -> $2y$10$FZAgwYin2hwioi1eoPwSWOrf5ST0RRBWLoeyPrOBD9vhUnGt6jwf6
INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `no_hp`, `is_active`) VALUES
(1, 'pemilik', '$2y$10$7W9y/iI5oh59HCbISZjdfuYlV21CnV0H6bjI/CVMQhhrWto8CwwFC', 'Hj. Humaira (Pemilik)', 'pemilik', '081298765432', 1),
(2, 'kasir1', '$2y$10$FZAgwYin2hwioi1eoPwSWOrf5ST0RRBWLoeyPrOBD9vhUnGt6jwf6', 'Siti Rahma (Kasir 1)', 'kasir', '085712345678', 1);

-- 3. TABEL KATEGORI BERAS
DROP TABLE IF EXISTS `kategori_beras`;
CREATE TABLE `kategori_beras` (
  `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_kategori` VARCHAR(20) NOT NULL UNIQUE,
  `nama_kategori` VARCHAR(50) NOT NULL,
  `keterangan` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `kategori_beras` (`id_kategori`, `kode_kategori`, `nama_kategori`, `keterangan`) VALUES
(1, 'SPHP', 'Beras SPHP (BULOG)', 'Beras Stabilisasi Pasokan dan Harga Pangan resmi BULOG. Tersedia varian kemasan 5 Kg (HET Rp 62.500) dan eceran per Kg (HET Rp 12.500).'),
(2, 'MEDIUM', 'Beras Medium', 'Beras kualitas medium hasil giling sendiri. Tersedia kemasan dan eceran liter.'),
(3, 'PREMIUM', 'Beras Premium', 'Beras kualitas premium hasil giling sendiri berbulir utuh. Tersedia kemasan dan eceran liter.');

-- 4. TABEL PRODUK
DROP TABLE IF EXISTS `produk`;
CREATE TABLE `produk` (
  `id_produk` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kategori` INT NOT NULL,
  `kode_produk` VARCHAR(20) NOT NULL UNIQUE,
  `nama_produk` VARCHAR(100) NOT NULL,
  `satuan` VARCHAR(30) NOT NULL,
  `berat_kg` DECIMAL(8,3) NOT NULL DEFAULT 5.000,
  `is_eceran` TINYINT(1) NOT NULL DEFAULT 0,
  `harga_beli` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `harga_jual` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `stok` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `stok_minimum` DECIMAL(10,2) NOT NULL DEFAULT 10,
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_kategori`) REFERENCES `kategori_beras`(`id_kategori`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `produk` (`id_produk`, `id_kategori`, `kode_produk`, `nama_produk`, `satuan`, `berat_kg`, `is_eceran`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `keterangan`) VALUES
(1, 1, 'SPHP-5KG', 'Beras SPHP BULOG 5 Kg', 'Kemasan 5 Kg', 5.000, 0, 55000.00, 62500.00, 348.00, 20.00, 'Beras Program SPHP BULOG, kemasan resmi 5kg utuh (HET Rp 62.500/kemasan).'),
(2, 2, 'MED-5KG', 'Beras Medium Cap Melati 5 Kg', 'Kemasan 5 Kg', 5.000, 0, 60000.00, 68000.00, 49.00, 10.00, 'Hasil giling sendiri, kemasan sak 5kg.'),
(3, 2, 'MED-10KG', 'Beras Medium Cap Melati 10 Kg', 'Kemasan 10 Kg', 10.000, 0, 118000.00, 135000.00, 25.00, 5.00, 'Hasil giling sendiri, kemasan sak 10kg.'),
(4, 2, 'MED-25KG', 'Beras Medium Cap Melati 25 Kg', 'Kemasan 25 Kg', 25.000, 0, 290000.00, 330000.00, 15.00, 5.00, 'Hasil giling sendiri, kemasan sak karung 25kg.'),
(5, 2, 'MED-LTR', 'Beras Medium Curah (Liter)', 'Liter', 0.753, 1, 11000.00, 13000.00, 145.00, 30.00, 'Beras medium eceran literan untuk pembeli harian (1 liter = 0.753 kg).'),
(6, 3, 'PRE-5KG', 'Beras Premium Pandan Wangi 5 Kg', 'Kemasan 5 Kg', 5.000, 0, 66000.00, 75000.00, 40.00, 10.00, 'Hasil giling sendiri padi pilihan, kemasan sak 5kg.'),
(7, 3, 'PRE-10KG', 'Beras Premium Pandan Wangi 10 Kg', 'Kemasan 10 Kg', 10.000, 0, 130000.00, 148000.00, 20.00, 5.00, 'Hasil giling sendiri, kemasan sak 10kg.'),
(8, 3, 'PRE-25KG', 'Beras Premium Pandan Wangi 25 Kg', 'Kemasan 25 Kg', 25.000, 0, 320000.00, 365000.00, 12.00, 5.00, 'Hasil giling sendiri, kemasan karung 25kg.'),
(9, 3, 'PRE-LTR', 'Beras Premium Curah (Liter)', 'Liter', 0.753, 1, 12500.00, 15000.00, 85.00, 25.00, 'Beras premium eceran literan pulen dan wangi (1 liter = 0.753 kg).'),
(10, 1, 'SPHP-1KG', 'Beras SPHP BULOG (Per Kg)', 'Kg', 1.000, 1, 11000.00, 12500.00, 100.00, 20.00, 'Beras Program SPHP BULOG eceran per kg (HET Rp 12.500/kg).');

-- 5. TABEL PRODUKSI GILING (MEDIUM & PREMIUM GILING SENDIRI)
DROP TABLE IF EXISTS `produksi_giling`;
CREATE TABLE `produksi_giling` (
  `id_produksi` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_produksi` VARCHAR(30) NOT NULL UNIQUE,
  `id_produk` INT NOT NULL,
  `tanggal_giling` DATE NOT NULL,
  `jumlah_gabah_kg` DECIMAL(10,2) NULL,
  `jumlah_hasil` DECIMAL(10,2) NOT NULL,
  `biaya_giling` DECIMAL(12,2) DEFAULT 0,
  `catatan` TEXT NULL,
  `id_user` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`) ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `produksi_giling` (`id_produksi`, `kode_produksi`, `id_produk`, `tanggal_giling`, `jumlah_gabah_kg`, `jumlah_hasil`, `biaya_giling`, `catatan`, `id_user`) VALUES
(1, 'PRD-20260920-001', 2, '2026-09-20', 350.00, 50.00, 50000.00, 'Giling padi Ciherang panen kelompok tani, dikemas sak 5kg', 1),
(2, 'PRD-20260921-002', 5, '2026-09-21', 200.00, 150.00, 30000.00, 'Giling beras medium untuk stok curah literan', 1),
(3, 'PRD-20260922-003', 6, '2026-09-22', 300.00, 40.00, 45000.00, 'Giling padi menthik wangi super, kemas sak 5kg', 1);

-- 6. TABEL PENGADAAN BERAS SPHP DARI BULOG
DROP TABLE IF EXISTS `pengadaan_sphp`;
CREATE TABLE `pengadaan_sphp` (
  `id_pengadaan` INT AUTO_INCREMENT PRIMARY KEY,
  `no_pengadaan` VARCHAR(30) NOT NULL UNIQUE,
  `no_ref_bulog` VARCHAR(50) NULL,
  `tanggal_order` DATE NOT NULL,
  `cabang_bulog` VARCHAR(100) NOT NULL DEFAULT 'Kantor Cabang BULOG Kota/Kab',
  `id_produk` INT NOT NULL,
  `jumlah_sak` INT NOT NULL,
  `total_berat_kg` DECIMAL(10,2) NOT NULL,
  `harga_satuan` DECIMAL(12,2) NOT NULL DEFAULT 55000.00,
  `total_biaya` DECIMAL(12,2) NOT NULL,
  `status` ENUM('diajukan', 'disetujui', 'dibayar', 'surat_jalan', 'selesai', 'batal') NOT NULL DEFAULT 'diajukan',
  `tanggal_disetujui` DATE NULL,
  `tanggal_bayar` DATE NULL,
  `bank_tujuan` VARCHAR(50) NULL,
  `no_rekening` VARCHAR(50) NULL,
  `no_surat_pengambilan` VARCHAR(50) NULL,
  `tanggal_ambil` DATE NULL,
  `tanggal_diterima` DATETIME NULL,
  `petugas_pengambil` VARCHAR(100) NULL,
  `catatan` TEXT NULL,
  `id_user_created` INT NOT NULL,
  `id_user_approved` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`),
  FOREIGN KEY (`id_user_created`) REFERENCES `users`(`id_user`),
  FOREIGN KEY (`id_user_approved`) REFERENCES `users`(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pengadaan_sphp` (`id_pengadaan`, `no_pengadaan`, `no_ref_bulog`, `tanggal_order`, `cabang_bulog`, `id_produk`, `jumlah_sak`, `total_berat_kg`, `harga_satuan`, `total_biaya`, `status`, `tanggal_disetujui`, `tanggal_bayar`, `bank_tujuan`, `no_rekening`, `no_surat_pengambilan`, `tanggal_ambil`, `tanggal_diterima`, `petugas_pengambil`, `catatan`, `id_user_created`, `id_user_approved`) VALUES
(1, 'SPHP-ORD-20260915-01', 'BLG-APP-99812', '2026-09-15', 'Kancab BULOG Subdivre 01', 1, 200, 1000.00, 55000.00, 11000000.00, 'selesai', '2026-09-15', '2026-09-16', 'Bank BRI Kantor Cabang', '0123-01-000456-30-1', 'DO/BULOG/SPHP/IX/2026/0412', '2026-09-17', '2026-09-17 14:30:00', 'Budi Santoso (Driver Pick-up)', 'Pengadaan 1 ton pertama September minggu ke-3', 1, 1),
(2, 'SPHP-ORD-20260925-02', 'BLG-APP-99885', '2026-09-25', 'Kancab BULOG Subdivre 01', 1, 150, 750.00, 55000.00, 8250000.00, 'selesai', '2026-09-25', '2026-09-26', 'Bank BRI Kantor Cabang', '0123-01-000456-30-1', 'DO/BULOG/SPHP/IX/2026/0498', '2026-09-27', '2026-09-27 10:15:00', 'Budi Santoso', 'Alokasi minggu ke-4 September (750 kg dari kuota 2 ton)', 1, 1);

-- 7. TABEL PENJUALAN
DROP TABLE IF EXISTS `penjualan`;
CREATE TABLE `penjualan` (
  `id_penjualan` INT AUTO_INCREMENT PRIMARY KEY,
  `no_faktur` VARCHAR(30) NOT NULL UNIQUE,
  `tanggal_penjualan` DATETIME NOT NULL,
  `total_item` INT NOT NULL DEFAULT 1,
  `total_kuantiti` DECIMAL(10,2) NOT NULL DEFAULT 1,
  `total_harga` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `uang_bayar` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `uang_kembali` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `metode_pembayaran` VARCHAR(20) NOT NULL DEFAULT 'TUNAI',
  `ada_sphp` TINYINT(1) NOT NULL DEFAULT 0,
  `nik_pembeli` VARCHAR(25) NULL,
  `nama_pembeli` VARCHAR(100) NULL,
  `no_hp_pembeli` VARCHAR(25) NULL,
  `status_konsumen_akhir` TINYINT(1) NOT NULL DEFAULT 1,
  `id_user` INT NOT NULL,
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. TABEL PENJUALAN DETAIL
DROP TABLE IF EXISTS `penjualan_detail`;
CREATE TABLE `penjualan_detail` (
  `id_detail` INT AUTO_INCREMENT PRIMARY KEY,
  `id_penjualan` INT NOT NULL,
  `id_produk` INT NOT NULL,
  `jumlah` DECIMAL(10,2) NOT NULL,
  `harga_beli` DECIMAL(12,2) NOT NULL,
  `harga_satuan` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `laba` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan`(`id_penjualan`) ON DELETE CASCADE,
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. TABEL RIWAYAT / KARTU STOK
DROP TABLE IF EXISTS `riwayat_stok`;
CREATE TABLE `riwayat_stok` (
  `id_riwayat` INT AUTO_INCREMENT PRIMARY KEY,
  `id_produk` INT NOT NULL,
  `jenis_transaksi` ENUM('MASUK', 'KELUAR', 'PENYESUAIAN') NOT NULL,
  `sumber` ENUM('PRODUKSI', 'PENGADAAN_BULOG', 'PENJUALAN', 'PENYESUAIAN') NOT NULL,
  `referensi_kode` VARCHAR(50) NOT NULL,
  `jumlah` DECIMAL(10,2) NOT NULL,
  `stok_sebelum` DECIMAL(10,2) NOT NULL,
  `stok_sesudah` DECIMAL(10,2) NOT NULL,
  `keterangan` TEXT NULL,
  `id_user` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`),
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample transaksi penjualan
INSERT INTO `penjualan` (`id_penjualan`, `no_faktur`, `tanggal_penjualan`, `total_item`, `total_kuantiti`, `total_harga`, `uang_bayar`, `uang_kembali`, `metode_pembayaran`, `ada_sphp`, `nama_pembeli`, `no_hp_pembeli`, `status_konsumen_akhir`, `id_user`, `catatan`) VALUES
(1, 'TRX-20260927-001', '2026-09-27 08:30:15', 1, 2.00, 125000.00, 150000.00, 25000.00, 'TUNAI', 1, 'Ibu Aminah', '081234567899', 1, 2, 'Pembelian beras SPHP untuk konsumsi keluarga'),
(2, 'TRX-20260927-002', '2026-09-27 10:14:02', 2, 6.00, 133000.00, 150000.00, 17000.00, 'TUNAI', 0, 'Pak RT Slamet', '085299887766', 1, 2, 'Medium 5kg + Eceran liter');

INSERT INTO `penjualan_detail` (`id_detail`, `id_penjualan`, `id_produk`, `jumlah`, `harga_beli`, `harga_satuan`, `subtotal`, `laba`) VALUES
(1, 1, 1, 2.00, 55000.00, 62500.00, 125000.00, 15000.00),
(2, 2, 2, 1.00, 60000.00, 68000.00, 68000.00, 8000.00),
(3, 2, 5, 5.00, 11000.00, 13000.00, 65000.00, 10000.00);

-- Catatan riwayat mutasi stok awal
INSERT INTO `riwayat_stok` (`id_produk`, `jenis_transaksi`, `sumber`, `referensi_kode`, `jumlah`, `stok_sebelum`, `stok_sesudah`, `keterangan`, `id_user`, `created_at`) VALUES
(1, 'MASUK', 'PENGADAAN_BULOG', 'SPHP-ORD-20260915-01', 200.00, 0.00, 200.00, 'Penerimaan beras SPHP BULOG DO/BULOG/SPHP/IX/2026/0412', 1, '2026-09-17 14:30:00'),
(2, 'MASUK', 'PRODUKSI', 'PRD-20260920-001', 50.00, 0.00, 50.00, 'Hasil giling beras Medium kemasan 5kg', 1, '2026-09-20 11:00:00'),
(5, 'MASUK', 'PRODUKSI', 'PRD-20260921-002', 150.00, 0.00, 150.00, 'Hasil giling beras Medium curah liter', 1, '2026-09-21 15:00:00'),
(6, 'MASUK', 'PRODUKSI', 'PRD-20260922-003', 40.00, 0.00, 40.00, 'Hasil giling beras Premium kemasan 5kg', 1, '2026-09-22 16:00:00'),
(1, 'MASUK', 'PENGADAAN_BULOG', 'SPHP-ORD-20260925-02', 150.00, 200.00, 350.00, 'Penerimaan beras SPHP BULOG DO/BULOG/SPHP/IX/2026/0498', 1, '2026-09-27 10:15:00'),
(1, 'KELUAR', 'PENJUALAN', 'TRX-20260927-001', 2.00, 350.00, 348.00, 'Penjualan ke Ibu Aminah (081234567899)', 2, '2026-09-27 08:30:15'),
(2, 'KELUAR', 'PENJUALAN', 'TRX-20260927-002', 1.00, 50.00, 49.00, 'Penjualan ke Pak RT Slamet', 2, '2026-09-27 10:14:02'),
(5, 'KELUAR', 'PENJUALAN', 'TRX-20260927-002', 5.00, 150.00, 145.00, 'Penjualan ke Pak RT Slamet', 2, '2026-09-27 10:14:02');

-- 10. TABEL MANAJEMEN MENU & HAK AKSES ROLE
DROP TABLE IF EXISTS `app_menu`;
CREATE TABLE `app_menu` (
  `id_menu` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_menu` VARCHAR(50) NOT NULL UNIQUE,
  `nama_menu` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `icon` VARCHAR(50) NOT NULL DEFAULT 'bi-link-45deg',
  `kategori` VARCHAR(50) NOT NULL DEFAULT 'Lainnya',
  `urutan` INT NOT NULL DEFAULT 0,
  `akses_pemilik` TINYINT(1) NOT NULL DEFAULT 1,
  `akses_kasir` TINYINT(1) NOT NULL DEFAULT 0,
  `is_bawaan` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `app_menu` (`kode_menu`, `nama_menu`, `url`, `icon`, `kategori`, `urutan`, `akses_pemilik`, `akses_kasir`, `is_bawaan`, `is_active`) VALUES
('dashboard', 'Dashboard Utama', '/modules/dashboard/index.php', 'bi-grid-1x2', 'Ringkasan Utama', 1, 1, 1, 1, 1),
('kasir', 'Kasir Penjualan (POS)', '/modules/kasir/index.php', 'bi-cart-check', 'Operasional Penjualan', 2, 1, 1, 1, 1),
('riwayat_penjualan', 'Riwayat Transaksi', '/modules/kasir/riwayat.php', 'bi-receipt', 'Operasional Penjualan', 3, 1, 1, 1, 1),
('produk', 'Data Master Produk', '/modules/produk/index.php', 'bi-box-seam', 'Master Data & Pasokan', 4, 1, 0, 1, 1),
('pengadaan_sphp', 'Pengadaan SPHP BULOG', '/modules/pengadaan_sphp/index.php', 'bi-truck', 'Master Data & Pasokan', 5, 1, 0, 1, 1),
('produksi', 'Giling Sendiri (Med/Pre)', '/modules/produksi/index.php', 'bi-gear-wide-connected', 'Master Data & Pasokan', 6, 1, 1, 1, 1),
('stok', 'Monitoring & Kartu Stok', '/modules/stok/index.php', 'bi-boxes', 'Master Data & Pasokan', 7, 1, 1, 1, 1),
('lap_penjualan', 'Laporan Penjualan', '/modules/laporan/penjualan.php', 'bi-file-earmark-bar-graph', 'Laporan & Evaluasi', 8, 1, 0, 1, 1),
('lap_kuota', 'Realisasi Kuota SPHP (2T)', '/modules/laporan/kuota_sphp.php', 'bi-pie-chart', 'Laporan & Evaluasi', 9, 1, 0, 1, 1),
('lap_stok', 'Laporan Mutasi Stok', '/modules/laporan/stok.php', 'bi-arrow-left-right', 'Laporan & Evaluasi', 10, 1, 0, 1, 1),
('lap_omzet', 'Laporan Omzet & Laba', '/modules/laporan/omzet.php', 'bi-cash-stack', 'Laporan & Evaluasi', 11, 1, 0, 1, 1),
('users', 'Manajemen Pengguna', '/modules/users/index.php', 'bi-people', 'Pengaturan Sistem', 12, 1, 0, 1, 1),
('menu_manajemen', 'Kelola Menu & Akses', '/modules/menu/index.php', 'bi-menu-button-wide-fill', 'Pengaturan Sistem', 13, 1, 0, 1, 1),
('profil', 'Pengaturan Toko & Profil', '/profil.php', 'bi-sliders', 'Pengaturan Sistem', 14, 1, 1, 1, 1),
('manual_book', 'Buku Panduan Sistem', '/manual_book.php', 'bi-journal-bookmark-fill', 'Dokumentasi', 15, 1, 1, 1, 1);

SET FOREIGN_KEY_CHECKS = 1;
