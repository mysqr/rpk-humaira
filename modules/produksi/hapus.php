<?php
// modules/produksi/hapus.php - Hapus / Batalkan Catatan Hasil Giling Beras
require_once __DIR__ . '/../../config/auth.php';
check_login();

$id_produksi = (int)($_GET['id'] ?? 0);
$prd = query_single("SELECT pg.*, p.nama_produk, p.satuan, p.stok 
                     FROM produksi_giling pg 
                     JOIN produk p ON pg.id_produk = p.id_produk 
                     WHERE pg.id_produksi = $id_produksi");

if (!$prd) {
    set_flash('danger', 'Data catatan produksi giling tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/produksi/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id_produk = (int)$prd['id_produk'];
$jumlah_hasil = (float)$prd['jumlah_hasil'];
$kode_produksi = $prd['kode_produksi'];

$conn->begin_transaction();
try {
    // 1. Kurangi stok produk sejumlah hasil giling yang dihapus
    $stok_sebelum = (float)$prd['stok'];
    $stok_sesudah = max(0, $stok_sebelum - $jumlah_hasil);
    $conn->query("UPDATE produk SET stok = $stok_sesudah WHERE id_produk = $id_produk");

    // 2. Catat mutasi di riwayat stok
    $ket_stok = "Pembatalan/hapus catatan produksi giling $kode_produksi (stok berkurang -$jumlah_hasil {$prd['satuan']})";
    $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                  VALUES ($id_produk, 'KELUAR', 'PRODUKSI', '$kode_produksi', $jumlah_hasil, $stok_sebelum, $stok_sesudah, '$ket_stok', $user_id)");

    // 3. Hapus data produksi_giling
    $conn->query("DELETE FROM produksi_giling WHERE id_produksi = $id_produksi");

    $conn->commit();
    set_flash('success', "Catatan produksi <strong>$kode_produksi</strong> berhasil dihapus. Stok <strong>{$prd['nama_produk']}</strong> otomatis dikurangi <strong>-$jumlah_hasil {$prd['satuan']}</strong>.");
} catch (Exception $e) {
    $conn->rollback();
    set_flash('danger', 'Gagal menghapus catatan produksi: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/modules/produksi/index.php');
exit;
