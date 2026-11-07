<?php
// config/menu_helper.php - Pengelolaan Dinamis Navigasi & Hak Akses Menu Aplikasi
require_once __DIR__ . '/database.php';

/**
 * Pastikan tabel app_menu sudah ada dan terisi menu bawaan awal.
 */
function ensure_menu_table_exists() {
    global $conn;
    if (!$conn || !($conn instanceof mysqli)) return;

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
    
    $conn->query($sql);

    // Cek apakah tabel kosong
    $cek = query_single("SELECT COUNT(*) as total FROM app_menu");
    if (($cek['total'] ?? 0) == 0) {
        $default_menus = [
            ['dashboard', 'Dashboard Utama', '/modules/dashboard/index.php', 'bi-grid-1x2', 'Ringkasan Utama', 1, 1, 1, 1],
            ['kasir', 'Kasir Penjualan (POS)', '/modules/kasir/index.php', 'bi-cart-check', 'Operasional Penjualan', 2, 1, 1, 1],
            ['riwayat_penjualan', 'Riwayat Transaksi', '/modules/kasir/riwayat.php', 'bi-receipt', 'Operasional Penjualan', 3, 1, 1, 1],
            ['produk', 'Data Master Produk', '/modules/produk/index.php', 'bi-box-seam', 'Master Data & Pasokan', 4, 1, 0, 1],
            ['pengadaan_sphp', 'Pengadaan SPHP BULOG', '/modules/pengadaan_sphp/index.php', 'bi-truck', 'Master Data & Pasokan', 5, 1, 0, 1],
            ['produksi', 'Giling Sendiri (Med/Pre)', '/modules/produksi/index.php', 'bi-gear-wide-connected', 'Master Data & Pasokan', 6, 1, 1, 1],
            ['stok', 'Monitoring & Kartu Stok', '/modules/stok/index.php', 'bi-boxes', 'Master Data & Pasokan', 7, 1, 1, 1],
            ['lap_penjualan', 'Laporan Penjualan', '/modules/laporan/penjualan.php', 'bi-file-earmark-bar-graph', 'Laporan & Evaluasi', 8, 1, 0, 1],
            ['lap_kuota', 'Realisasi Kuota SPHP (2T)', '/modules/laporan/kuota_sphp.php', 'bi-pie-chart', 'Laporan & Evaluasi', 9, 1, 0, 1],
            ['lap_stok', 'Laporan Mutasi Stok', '/modules/laporan/stok.php', 'bi-arrow-left-right', 'Laporan & Evaluasi', 10, 1, 0, 1],
            ['lap_omzet', 'Laporan Omzet & Laba', '/modules/laporan/omzet.php', 'bi-cash-stack', 'Laporan & Evaluasi', 11, 1, 0, 1],
            ['users', 'Manajemen Pengguna', '/modules/users/index.php', 'bi-people', 'Pengaturan Sistem', 12, 1, 0, 1],
            ['menu_manajemen', 'Kelola Menu & Akses', '/modules/menu/index.php', 'bi-menu-button-wide-fill', 'Pengaturan Sistem', 13, 1, 0, 1],
            ['profil', 'Pengaturan Toko & Profil', '/profil.php', 'bi-sliders', 'Pengaturan Sistem', 14, 1, 1, 1],
            ['manual_book', 'Buku Panduan Sistem', '/manual_book.php', 'bi-journal-bookmark-fill', 'Dokumentasi', 15, 1, 1, 1],
        ];

        foreach ($default_menus as $dm) {
            $kode = escape($dm[0]);
            $nama = escape($dm[1]);
            $url = escape($dm[2]);
            $icon = escape($dm[3]);
            $kat = escape($dm[4]);
            $urut = (int)$dm[5];
            $ap = (int)$dm[6];
            $ak = (int)$dm[7];
            $ib = (int)$dm[8];
            $conn->query("INSERT IGNORE INTO app_menu (kode_menu, nama_menu, url, icon, kategori, urutan, akses_pemilik, akses_kasir, is_bawaan, is_active)
                          VALUES ('$kode', '$nama', '$url', '$icon', '$kat', $urut, $ap, $ak, $ib, 1)");
        }
    }
}

/**
 * Mengambil daftar menu berdasarkan role pengguna yang sedang login
 */
function get_sidebar_menus_by_role($user_role = 'kasir') {
    ensure_menu_table_exists();
    $role = ($user_role === 'pemilik' || $user_role === 'admin') ? 'pemilik' : 'kasir';
    $kolom_akses = ($role === 'pemilik') ? 'akses_pemilik' : 'akses_kasir';

    $sql = "SELECT * FROM app_menu 
            WHERE is_active = 1 AND $kolom_akses = 1 
            ORDER BY urutan ASC, id_menu ASC";
    
    $menus = query($sql);
    if (empty($menus)) {
        // Fallback jika database belum ada
        return [];
    }

    // Kelompokkan berdasarkan kategori
    $grouped = [];
    foreach ($menus as $m) {
        $kat = !empty($m['kategori']) ? $m['kategori'] : 'Menu Lainnya';
        if (!isset($grouped[$kat])) {
            $grouped[$kat] = [];
        }
        $grouped[$kat][] = $m;
    }
    return $grouped;
}

/**
 * Format link URL menu (mendukung relative path /modules/... atau link luar http)
 */
function format_menu_url($url) {
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }
    return BASE_URL . '/' . ltrim($url, '/');
}
