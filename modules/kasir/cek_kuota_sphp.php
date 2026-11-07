<?php
// modules/kasir/cek_kuota_sphp.php - AJAX Endpoint Cek Akumulasi Pembelian SPHP per NIK (Per Hari)
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

$nik    = escape($_GET['nik'] ?? '');
$no_hp  = escape($_GET['no_hp'] ?? '');
$today  = date('Y-m-d');

// Batas maksimal pembelian SPHP: 5 sak (25 kg) per NIK PER HARI
$maks_harian_sak = 5;
$maks_harian_kg  = $maks_harian_sak * 5; // = 25 kg

if (empty($nik) && empty($no_hp)) {
    echo json_encode([
        'status'             => 'empty',
        'sudah_beli_hari_ini'=> 0,
        'sudah_beli_total'   => 0, // kompatibilitas
        'sisa_kuota'         => $maks_harian_sak,
        'maks_harian_sak'    => $maks_harian_sak,
        'maks_harian_kg'     => $maks_harian_kg,
        'nama_pembeli'       => '',
        'no_hp'              => ''
    ]);
    exit;
}

// Utamakan pencarian berdasarkan NIK
if (!empty($nik)) {
    $field_filter = "nik_pembeli = '$nik'";
} else {
    $field_filter = "no_hp_pembeli = '$no_hp'";
}

// Hitung total Kg SPHP yang dibeli oleh NIK ini HARI INI
$sql = "SELECT COALESCE(SUM(pd.jumlah * pr.berat_kg), 0) as total_kg_hari_ini, 
               MAX(p.nama_pembeli)    as nama_terakhir,
               MAX(p.no_hp_pembeli)  as hp_terakhir
        FROM penjualan p
        JOIN penjualan_detail pd ON p.id_penjualan = pd.id_penjualan
        JOIN produk pr ON pd.id_produk = pr.id_produk
        JOIN kategori_beras kb ON pr.id_kategori = kb.id_kategori
        WHERE kb.kode_kategori = 'SPHP'
        AND p.$field_filter
        AND DATE(p.tanggal_penjualan) = '$today'";

$res = query_single($sql);
$sudah_kg     = (float)($res['total_kg_hari_ini'] ?? 0);
$sisa_kg      = max(0, $maks_harian_kg - $sudah_kg);
$sudah_beli_sak = floor($sudah_kg / 5);
$sisa_kuota_sak = floor($sisa_kg / 5);
$nama_pembeli = $res['nama_terakhir'] ?? '';
$hp_pembeli   = $res['hp_terakhir'] ?? '';

// Jika belum ada nama dari transaksi SPHP hari ini, ambil dari riwayat transaksi sebelumnya
if (empty($nama_pembeli)) {
    $res_nama = query_single("SELECT nama_pembeli, no_hp_pembeli FROM penjualan WHERE $field_filter AND nama_pembeli IS NOT NULL AND nama_pembeli != '' ORDER BY id_penjualan DESC LIMIT 1");
    if ($res_nama) {
        $nama_pembeli = $res_nama['nama_pembeli'];
        if (empty($hp_pembeli)) {
            $hp_pembeli = $res_nama['no_hp_pembeli'] ?? '';
        }
    }
}

echo json_encode([
    'status'              => 'success',
    'nik'                 => $nik,
    'no_hp'               => $hp_pembeli,
    'sudah_beli_hari_ini' => $sudah_beli_sak,
    'sudah_beli_total'    => $sudah_beli_sak,
    'sudah_beli_kg'       => $sudah_kg,
    'sisa_kuota'          => $sisa_kuota_sak,
    'sisa_kuota_kg'       => $sisa_kg,
    'maks_harian_sak'     => $maks_harian_sak,
    'maks_harian_kg'      => $maks_harian_kg,
    'nama_pembeli'        => $nama_pembeli
]);
