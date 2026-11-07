<?php
// modules/kasir/hapus.php - Hapus Transaksi Penjualan & Pengembalian Stok
require_once __DIR__ . '/../../config/auth.php';
check_login();

// Hanya admin / pemilik yang berhak menghapus transaksi
if (!is_admin()) {
    set_flash('danger', 'Akses ditolak! Hanya Admin / Pemilik yang berhak menghapus transaksi penjualan.');
    header('Location: ' . BASE_URL . '/modules/kasir/riwayat.php');
    exit;
}

$id_penjualan = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$return_url = $_POST['return_url'] ?? $_GET['return_url'] ?? (BASE_URL . '/modules/kasir/riwayat.php');

// Validasi URL redirect
if (empty($return_url) || strpos($return_url, 'javascript:') !== false) {
    $return_url = BASE_URL . '/modules/kasir/riwayat.php';
}

$penjualan = query_single("SELECT * FROM penjualan WHERE id_penjualan = $id_penjualan");
if (!$penjualan) {
    set_flash('danger', 'Data transaksi penjualan tidak ditemukan atau sudah dihapus sebelumnya.');
    header('Location: ' . $return_url);
    exit;
}

// Ambil rincian produk yang terjual untuk dikembalikan stoknya
$details = query("SELECT pd.*, p.nama_produk, p.satuan 
                  FROM penjualan_detail pd 
                  JOIN produk p ON pd.id_produk = p.id_produk 
                  WHERE pd.id_penjualan = $id_penjualan");

$conn->begin_transaction();
try {
    $user_id = (int)$_SESSION['user_id'];
    $no_faktur = $penjualan['no_faktur'];

    // 1. Kembalikan stok setiap produk ke tabel produk dan catat di riwayat_stok
    foreach ($details as $item) {
        $id_produk = (int)$item['id_produk'];
        $qty = (float)$item['jumlah'];

        // Kunci baris produk untuk konsistensi stok
        $p_db = query_single("SELECT stok, nama_produk, satuan FROM produk WHERE id_produk = $id_produk FOR UPDATE");
        if ($p_db) {
            $stok_sebelum = (float)$p_db['stok'];
            $stok_sesudah = $stok_sebelum + $qty;

            // Update stok produk
            $conn->query("UPDATE produk SET stok = $stok_sesudah WHERE id_produk = $id_produk");

            // Catat mutasi masuk pada riwayat_stok (audit trail)
            $ket_stok = "Pembatalan/hapus faktur $no_faktur (stok dikembalikan +$qty {$p_db['satuan']})";
            $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                          VALUES ($id_produk, 'MASUK', 'PENJUALAN', '$no_faktur', $qty, $stok_sebelum, $stok_sesudah, '$ket_stok', $user_id)");
        }
    }

    // 2. Hapus data penjualan_detail dan faktur penjualan
    $conn->query("DELETE FROM penjualan_detail WHERE id_penjualan = $id_penjualan");
    $conn->query("DELETE FROM penjualan WHERE id_penjualan = $id_penjualan");

    $conn->commit();
    set_flash('success', "Transaksi faktur <strong>$no_faktur</strong> berhasil dihapus dan seluruh stok produk telah dikembalikan ke inventori.");
} catch (Exception $e) {
    $conn->rollback();
    set_flash('danger', 'Gagal memproses penghapusan transaksi: ' . $e->getMessage());
}

header('Location: ' . $return_url);
exit;
