<?php
// config/app.php - Konfigurasi Aplikasi & Fungsi Utility
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

// Base URL otomatis mendeteksi folder aplikasi (baik /pos lokal, /capstone di hosting, atau domain utama)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = preg_replace('#/(modules|config|includes|assets)(/.*)?$#i', '', $script_dir);
$base_path = rtrim($base_path, '/');
define('BASE_URL', $protocol . $host . $base_path);

// Ambil Pengaturan Toko dari Database
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/menu_helper.php';
$toko_info = query_single("SELECT * FROM pengaturan WHERE id = 1 LIMIT 1");
if (!$toko_info) {
    $toko_info = [
        'nama_toko' => 'RPK Humaira',
        'mitra' => 'Rumah Pangan Kita - Perum BULOG',
        'alamat' => 'Jl. Raya Pasar Induk No. 45',
        'telepon' => '0812-9876-5432',
        'nama_pemilik' => 'Hj. Humaira',
        'maks_sphp_harian_per_orang' => 5,
        'kuota_mingguan_sphp_kg' => 2000,
        'pesan_struk' => 'Beras SPHP khusus untuk pembeli akhir.'
    ];
}

// Fungsi Format Rupiah
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Fungsi Format Tanggal Indonesia
function tgl_indo($tanggal, $cetak_waktu = false) {
    if (empty($tanggal) || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $bln  = $bulan[(int)date('m', $timestamp)];
    $thn  = date('Y', $timestamp);
    
    $hasil = "$hari $bln $thn";
    if ($cetak_waktu) {
        $hasil .= ' ' . date('H:i', $timestamp) . ' WIB';
    }
    return $hasil;
}

// Fungsi Notifikasi Flash Session
function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Fungsi Hitung Kuota Mingguan SPHP yang terpakai
function get_kuota_sphp_minggu_ini() {
    global $conn, $toko_info;
    // Hitung dari hari Senin minggu berjalan hingga Minggu
    $senin = date('Y-m-d', strtotime('monday this week'));
    $minggu = date('Y-m-d', strtotime('sunday this week'));
    
    // Total pengadaan SPHP yang diajukan/disetujui/selesai dalam minggu ini
    $sql = "SELECT COALESCE(SUM(total_berat_kg), 0) as total_kg 
            FROM pengadaan_sphp 
            WHERE tanggal_order BETWEEN '$senin' AND '$minggu' 
            AND status != 'batal'";
    $row = query_single($sql);
    $terpakai_kg = (float)($row['total_kg'] ?? 0);
    $batas_kg = (float)$toko_info['kuota_mingguan_sphp_kg'];
    $sisa_kg = max(0, $batas_kg - $terpakai_kg);
    $persen = $batas_kg > 0 ? min(100, round(($terpakai_kg / $batas_kg) * 100, 1)) : 0;
    
    return [
        'senin' => $senin,
        'minggu' => $minggu,
        'terpakai_kg' => $terpakai_kg,
        'batas_kg' => $batas_kg,
        'sisa_kg' => $sisa_kg,
        'persen' => $persen
    ];
}
