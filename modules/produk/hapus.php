<?php
// modules/produk/hapus.php - Hapus Produk
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

$id_produk = (int)($_GET['id'] ?? 0);
$produk = query_single("SELECT p.*, k.kode_kategori FROM produk p JOIN kategori_beras k ON p.id_kategori = k.id_kategori WHERE p.id_produk = $id_produk");

if (!$produk) {
    set_flash('danger', 'Produk tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/produk/index.php');
    exit;
}

// Cek apakah ada relasi ke penjualan_detail
$cek_trx = query_single("SELECT id_detail FROM penjualan_detail WHERE id_produk = $id_produk LIMIT 1");
if ($cek_trx) {
    set_flash('danger', "Produk <strong>{$produk['nama_produk']}</strong> sudah memiliki riwayat transaksi penjualan dan tidak boleh dihapus demi integritas laporan!");
    header('Location: ' . BASE_URL . '/modules/produk/index.php');
    exit;
}

// Cek apakah ada relasi ke pengadaan_sphp
$cek_pengadaan = query_single("SELECT id_pengadaan FROM pengadaan_sphp WHERE id_produk = $id_produk LIMIT 1");
if ($cek_pengadaan) {
    set_flash('danger', "Produk <strong>{$produk['nama_produk']}</strong> sudah memiliki riwayat data pengadaan SPHP BULOG dan tidak boleh dihapus demi integritas data pengadaan!");
    header('Location: ' . BASE_URL . '/modules/produk/index.php');
    exit;
}

// Cek apakah ada relasi ke produksi_giling
$cek_produksi = query_single("SELECT id_produksi FROM produksi_giling WHERE id_produk = $id_produk LIMIT 1");
if ($cek_produksi) {
    set_flash('danger', "Produk <strong>{$produk['nama_produk']}</strong> memiliki riwayat produksi giling dan tidak boleh dihapus!");
    header('Location: ' . BASE_URL . '/modules/produk/index.php');
    exit;
}

// Hapus riwayat kartu stok terkait produk ini jika tidak ada relasi transaksi
$conn->query("DELETE FROM riwayat_stok WHERE id_produk = $id_produk");

if ($conn->query("DELETE FROM produk WHERE id_produk = $id_produk")) {
    set_flash('success', "Produk beras <strong>{$produk['nama_produk']}</strong> berhasil dihapus.");
} else {
    set_flash('danger', 'Gagal menghapus produk: ' . $conn->error);
}

header('Location: ' . BASE_URL . '/modules/produk/index.php');
exit;
