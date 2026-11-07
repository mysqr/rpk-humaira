<?php
// modules/stok/hapus_riwayat.php - Hapus Kesalahan Riwayat Mutasi Stok & Kembalikan Saldo Stok ke Sebelum Transaksi
require_once __DIR__ . '/../../config/auth.php';
check_login();

// Hanya Pemilik / Admin yang berhak menghapus riwayat stok
if (!is_pemilik()) {
    set_flash('danger', 'Akses ditolak! Fitur hapus riwayat mutasi stok hanya dapat dilakukan oleh Admin / Pemilik.');
    $fallback_url = BASE_URL . '/modules/laporan/stok.php';
    header('Location: ' . $fallback_url);
    exit;
}

$id_riwayat = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$return_url = $_POST['return_url'] ?? $_GET['return_url'] ?? (BASE_URL . '/modules/laporan/stok.php');

// Validasi URL redirect agar aman
if (empty($return_url) || strpos($return_url, 'javascript:') !== false) {
    $return_url = BASE_URL . '/modules/laporan/stok.php';
}

$riwayat = query_single("SELECT r.*, p.nama_produk, p.satuan, p.stok as stok_sekarang 
                         FROM riwayat_stok r 
                         JOIN produk p ON r.id_produk = p.id_produk 
                         WHERE r.id_riwayat = $id_riwayat");

if (!$riwayat) {
    set_flash('danger', 'Data riwayat mutasi stok tidak ditemukan atau telah dihapus sebelumnya.');
    header('Location: ' . $return_url);
    exit;
}

$id_produk = (int)$riwayat['id_produk'];
$referensi_kode = $riwayat['referensi_kode'];
$jenis_transaksi = $riwayat['jenis_transaksi'];
$sumber = $riwayat['sumber'];
$jumlah = (float)$riwayat['jumlah'];
$stok_sebelum_trx = (float)$riwayat['stok_sebelum'];
$stok_sesudah_trx = (float)$riwayat['stok_sesudah'];
$nama_produk = $riwayat['nama_produk'];
$satuan = $riwayat['satuan'];

$conn->begin_transaction();
try {
    // 1. Kunci data produk untuk konsistensi perhitungan saldo
    $p_db = query_single("SELECT id_produk, stok, nama_produk, satuan FROM produk WHERE id_produk = $id_produk FOR UPDATE");
    if (!$p_db) {
        throw new Exception("Data produk beras terkait (ID $id_produk) tidak ditemukan di database.");
    }

    $stok_sekarang = (float)$p_db['stok'];

    // 2. Hitung pengembalian saldo stok ke kondisi sebelum transaksi:
    // - Jika pergerakan MASUK (+): dulu stok bertambah, saat dihapus stok dikurangi kembali
    // - Jika pergerakan KELUAR (-): dulu stok berkurang, saat dihapus stok dikembalikan/ditambah
    // - Jika PENYESUAIAN: kembalikan selisihnya
    if ($jenis_transaksi === 'MASUK') {
        $stok_kembali = max(0, $stok_sekarang - $jumlah);
        $arah_info = "dikurangi -$jumlah $satuan";
    } elseif ($jenis_transaksi === 'KELUAR') {
        $stok_kembali = $stok_sekarang + $jumlah;
        $arah_info = "ditambah kembali +$jumlah $satuan";
    } else {
        $selisih = $stok_sesudah_trx - $stok_sebelum_trx;
        $stok_kembali = max(0, $stok_sekarang - $selisih);
        $arah_info = "disesuaikan selisih (" . ($selisih >= 0 ? "-$selisih" : "+" . abs($selisih)) . " $satuan)";
    }

    // Perbarui stok produk kembali ke saldo sebelum transaksi
    $conn->query("UPDATE produk SET stok = $stok_kembali WHERE id_produk = $id_produk");

    // 3. Sinkronkan dengan data induk transaksi jika ada referensi terkait
    // a. Jika terkait Produksi Giling
    if ($sumber === 'PRODUKSI' && !empty($referensi_kode)) {
        $ref_esc = escape($referensi_kode);
        $conn->query("DELETE FROM produksi_giling WHERE kode_produksi = '$ref_esc'");
    }
    // b. Jika terkait Penjualan Kasir (transaksi keluar)
    if ($sumber === 'PENJUALAN' && $jenis_transaksi === 'KELUAR' && !empty($referensi_kode)) {
        $ref_esc = escape($referensi_kode);
        $pj = query_single("SELECT id_penjualan FROM penjualan WHERE no_faktur = '$ref_esc'");
        if ($pj) {
            $id_penjualan = (int)$pj['id_penjualan'];
            $conn->query("DELETE FROM penjualan_detail WHERE id_penjualan = $id_penjualan");
            $conn->query("DELETE FROM penjualan WHERE id_penjualan = $id_penjualan");
        }
    }
    // c. Jika terkait Pengadaan SPHP BULOG
    if ($sumber === 'PENGADAAN_BULOG' && !empty($referensi_kode)) {
        $ref_esc = escape($referensi_kode);
        $conn->query("UPDATE pengadaan_sphp SET status = 'batal' WHERE no_pengadaan = '$ref_esc'");
    }

    // 4. Hapus baris riwayat mutasi dari tabel riwayat_stok
    $conn->query("DELETE FROM riwayat_stok WHERE id_riwayat = $id_riwayat");

    $conn->commit();
    set_flash('success', "Riwayat mutasi <strong>$referensi_kode</strong> berhasil dihapus. Saldo stok <strong>$nama_produk</strong> telah dikembalikan ke kondisi sebelum transaksi ($arah_info): dari <strong>$stok_sekarang $satuan</strong> menjadi <strong>$stok_kembali $satuan</strong>.");
} catch (Exception $e) {
    $conn->rollback();
    set_flash('danger', 'Gagal memproses penghapusan riwayat mutasi: ' . $e->getMessage());
}

header('Location: ' . $return_url);
exit;
