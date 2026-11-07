<?php
// database/migrate_menu.php - Migrasi Tabel Manajemen Menu & Seed Menu Default
require_once __DIR__ . '/../config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS `app_menu` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql)) {
    echo "Tabel app_menu berhasil dibuat / sudah ada.\n";
} else {
    die("Gagal membuat tabel app_menu: " . $conn->error . "\n");
}

// Cek apakah tabel masih kosong
$cek = query_single("SELECT COUNT(*) as total FROM app_menu");
if (($cek['total'] ?? 0) == 0) {
    $menus = [
        // Ringkasan Utama
        ['dashboard', 'Dashboard Utama', '/modules/dashboard/index.php', 'bi-grid-1x2', 'Ringkasan Utama', 1, 1, 1, 1],
        
        // Operasional Penjualan
        ['kasir', 'Kasir Penjualan (POS)', '/modules/kasir/index.php', 'bi-cart-check', 'Operasional Penjualan', 2, 1, 1, 1],
        ['riwayat_penjualan', 'Riwayat Transaksi', '/modules/kasir/riwayat.php', 'bi-receipt', 'Operasional Penjualan', 3, 1, 1, 1],
        
        // Master Data & Pasokan
        ['produk', 'Data Master Produk', '/modules/produk/index.php', 'bi-box-seam', 'Master Data & Pasokan', 4, 1, 0, 1],
        ['pengadaan_sphp', 'Pengadaan SPHP BULOG', '/modules/pengadaan_sphp/index.php', 'bi-truck', 'Master Data & Pasokan', 5, 1, 0, 1],
        ['produksi', 'Giling Sendiri (Med/Pre)', '/modules/produksi/index.php', 'bi-gear-wide-connected', 'Master Data & Pasokan', 6, 1, 1, 1],
        ['stok', 'Monitoring & Kartu Stok', '/modules/stok/index.php', 'bi-boxes', 'Master Data & Pasokan', 7, 1, 1, 1],
        
        // Laporan & Audit
        ['lap_penjualan', 'Laporan Penjualan', '/modules/laporan/penjualan.php', 'bi-file-earmark-bar-graph', 'Laporan & Evaluasi', 8, 1, 0, 1],
        ['lap_kuota', 'Realisasi Kuota SPHP (2T)', '/modules/laporan/kuota_sphp.php', 'bi-pie-chart', 'Laporan & Evaluasi', 9, 1, 0, 1],
        ['lap_stok', 'Laporan Mutasi Stok', '/modules/laporan/stok.php', 'bi-arrow-left-right', 'Laporan & Evaluasi', 10, 1, 0, 1],
        ['lap_omzet', 'Laporan Omzet & Laba', '/modules/laporan/omzet.php', 'bi-cash-stack', 'Laporan & Evaluasi', 11, 1, 0, 1],
        
        // Pengaturan Sistem
        ['users', 'Manajemen Pengguna', '/modules/users/index.php', 'bi-people', 'Pengaturan Sistem', 12, 1, 0, 1],
        ['menu_manajemen', 'Kelola Menu & Akses', '/modules/menu/index.php', 'bi-menu-button-wide-fill', 'Pengaturan Sistem', 13, 1, 0, 1],
        ['profil', 'Pengaturan Toko & Profil', '/profil.php', 'bi-sliders', 'Pengaturan Sistem', 14, 1, 1, 1],
        
        // Dokumentasi
        ['manual_book', 'Buku Panduan Sistem', '/manual_book.php', 'bi-journal-bookmark-fill', 'Dokumentasi', 15, 1, 1, 1],
    ];

    foreach ($menus as $m) {
        $kode = escape($m[0]);
        $nama = escape($m[1]);
        $url = escape($m[2]);
        $icon = escape($m[3]);
        $kategori = escape($m[4]);
        $urutan = (int)$m[5];
        $akses_p = (int)$m[6];
        $akses_k = (int)$m[7];
        $is_bawaan = (int)$m[8];

        $conn->query("INSERT INTO app_menu (kode_menu, nama_menu, url, icon, kategori, urutan, akses_pemilik, akses_kasir, is_bawaan, is_active) 
                      VALUES ('$kode', '$nama', '$url', '$icon', '$kategori', $urutan, $akses_p, $akses_k, $is_bawaan, 1)");
    }
    echo "Seed data menu berhasil diisi (" . count($menus) . " menu).\n";
} else {
    echo "Tabel app_menu sudah memiliki " . $cek['total'] . " data menu.\n";
}
