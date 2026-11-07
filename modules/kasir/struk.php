<?php
// modules/kasir/struk.php - Cetak Nota Struk Transaksi Kasir
require_once __DIR__ . '/../../config/auth.php';
check_login();

$id_penjualan = (int)($_GET['id'] ?? 0);
$trx = query_single("SELECT p.*, u.nama_lengkap as nama_kasir 
    FROM penjualan p 
    LEFT JOIN users u ON p.id_user = u.id_user 
    WHERE p.id_penjualan = $id_penjualan");

if (!$trx) {
    set_flash('danger', 'Transaksi tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/kasir/index.php');
    exit;
}

$items = query("SELECT d.*, pr.nama_produk, pr.satuan, pr.is_eceran, k.kode_kategori, k.nama_kategori 
    FROM penjualan_detail d 
    JOIN produk pr ON d.id_produk = pr.id_produk 
    JOIN kategori_beras k ON pr.id_kategori = k.id_kategori 
    WHERE d.id_penjualan = $id_penjualan");

$toko = query_single("SELECT * FROM pengaturan WHERE id = 1");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Struk Pembayaran - <?= htmlspecialchars($trx['no_faktur']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: #fff !important; }
      .thermal-receipt {
        border: none !important;
        box-shadow: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
      }
    }
  </style>
</head>
<body class="bg-light py-4">

<!-- Action Buttons -->
<div class="container no-print text-center mb-3">
  <button type="button" class="btn btn-primary px-4 shadow-sm me-2" onclick="window.print()">
    <i class="bi bi-printer me-1"></i> Cetak Struk
  </button>
  <a href="<?= BASE_URL ?>/modules/kasir/index.php" class="btn btn-success px-4 shadow-sm me-2">
    <i class="bi bi-cart-plus me-1"></i> Transaksi Baru
  </a>
  <a href="<?= BASE_URL ?>/modules/kasir/riwayat.php" class="btn btn-outline-secondary px-3">
    <i class="bi bi-receipt me-1"></i> Riwayat
  </a>
</div>

<!-- Receipt Content -->
<div class="thermal-receipt" id="printableReceipt">
  <!-- Header Toko -->
  <div class="text-center mb-2">
    <h5 class="fw-bold mb-0 text-dark"><?= strtoupper(htmlspecialchars($toko['nama_toko'])) ?></h5>
    <div class="fw-bold small text-primary" style="font-size: 0.8rem;"><?= htmlspecialchars($toko['mitra']) ?></div>
    <div class="small text-muted" style="font-size: 0.72rem; line-height: 1.2;">
      <?= htmlspecialchars($toko['alamat']) ?><br>
      Telp: <?= htmlspecialchars($toko['telepon']) ?>
    </div>
  </div>

  <hr class="my-2">

  <!-- Info Transaksi -->
  <table style="width: 100%; font-size: 0.75rem;">
    <tr>
      <td>No. Faktur</td>
      <td class="text-end fw-bold"><?= htmlspecialchars($trx['no_faktur']) ?></td>
    </tr>
    <tr>
      <td>Waktu</td>
      <td class="text-end"><?= date('d/m/Y H:i:s', strtotime($trx['tanggal_penjualan'])) ?></td>
    </tr>
    <tr>
      <td>Kasir</td>
      <td class="text-end"><?= htmlspecialchars($trx['nama_kasir'] ?? 'Kasir') ?></td>
    </tr>
    <tr>
      <td>Pembayaran</td>
      <td class="text-end fw-bold"><?= htmlspecialchars($trx['metode_pembayaran']) ?></td>
    </tr>
  </table>

  <!-- Khusus Jika Pembelian Ada Beras SPHP -->
  <?php if ($trx['ada_sphp']): ?>
    <hr class="my-2">
    <div class="bg-light p-2 border rounded" style="font-size: 0.72rem;">
      <div class="fw-bold text-primary mb-1"><i class="bi bi-check-circle-fill"></i> TERVERIFIKASI SPHP BULOG</div>
      <div><strong>NIK / KTP:</strong> <?= htmlspecialchars($trx['nik_pembeli'] ?? '-') ?></div>
      <div><strong>Pembeli:</strong> <?= htmlspecialchars($trx['nama_pembeli']) ?></div>
      <?php if (!empty($trx['no_hp_pembeli'])): ?>
        <div><strong>No. HP:</strong> <?= htmlspecialchars($trx['no_hp_pembeli']) ?></div>
      <?php endif; ?>
      <div><strong>Status:</strong> Konsumen Akhir (Rumah Tangga)</div>
    </div>
  <?php elseif ($trx['nama_pembeli'] && $trx['nama_pembeli'] !== 'Pembeli Umum'): ?>
    <div style="font-size: 0.75rem;" class="mt-1">
      <span>Pembeli: <strong><?= htmlspecialchars($trx['nama_pembeli']) ?></strong></span>
      <?= $trx['no_hp_pembeli'] ? ' (' . htmlspecialchars($trx['no_hp_pembeli']) . ')' : '' ?>
    </div>
  <?php endif; ?>

  <hr class="my-2">

  <!-- Daftar Produk -->
  <table style="width: 100%; font-size: 0.75rem;" class="mb-2">
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td colspan="2" class="fw-bold">
            <?= htmlspecialchars($item['nama_produk']) ?>
            <?php if ($item['kode_kategori'] === 'SPHP'): ?>
              <span class="badge bg-primary text-white" style="font-size: 0.6rem;">SPHP</span>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td class="text-muted ps-2" style="font-size: 0.72rem;">
            <?php if ($item['is_eceran']): ?>
              <?php if ($item['satuan'] === 'Liter'): ?>
                <?= number_format($item['jumlah'], 1) ?> Liter (≈ <?= number_format($item['jumlah'] * 0.753, 2) ?> Kg) x <?= number_format($item['harga_satuan'], 0, ',', '.') ?>
              <?php else: ?>
                <?= number_format($item['jumlah'], ($item['jumlah'] == (int)$item['jumlah'] ? 0 : 2)) ?> <?= htmlspecialchars($item['satuan']) ?> x <?= number_format($item['harga_satuan'], 0, ',', '.') ?>
              <?php endif; ?>
            <?php else: ?>
              <?= number_format($item['jumlah'], 0) ?> <?= htmlspecialchars($item['satuan']) ?> x <?= number_format($item['harga_satuan'], 0, ',', '.') ?>
            <?php endif; ?>
          </td>
          <td class="text-end fw-semibold">
            <?= number_format($item['subtotal'], 0, ',', '.') ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php 
    $ada_eceran = false;
    foreach ($items as $it) {
      if ($it['is_eceran']) { $ada_eceran = true; break; }
    }
    if ($ada_eceran):
  ?>
    <div class="text-center text-muted my-1" style="font-size: 0.68rem; font-style: italic;">
      * Konversi Beras Eceran: 1 Liter = 0,753 Kg (1 Kg ≈ 1,328 Liter)
    </div>
  <?php endif; ?>

  <hr class="my-2">

  <!-- Ringkasan Total & Pembayaran Tunai -->
  <table style="width: 100%; font-size: 0.8rem;">
    <tr>
      <td>Total Item</td>
      <td class="text-end"><?= count($items) ?> jenis (<?= (float)$trx['total_kuantiti'] ?> unit)</td>
    </tr>
    <tr class="fw-bold fs-6">
      <td>TOTAL BELANJA</td>
      <td class="text-end text-dark">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></td>
    </tr>
    <tr>
      <td>Bayar Tunai</td>
      <td class="text-end">Rp <?= number_format($trx['uang_bayar'], 0, ',', '.') ?></td>
    </tr>
    <tr class="fw-bold">
      <td>KEMBALIAN</td>
      <td class="text-end text-success">Rp <?= number_format($trx['uang_kembali'], 0, ',', '.') ?></td>
    </tr>
  </table>

  <hr class="my-2">

  <!-- Footer Catatan Resmi BULOG & Toko -->
  <div class="text-center text-muted" style="font-size: 0.7rem; line-height: 1.3;">
    <p class="mb-1 fw-semibold text-dark"><?= htmlspecialchars($toko['pesan_struk']) ?></p>
    <p class="mb-0">Barang yang sudah dibeli tidak dapat ditukar.<br>Terima kasih atas kunjungan Anda!</p>
  </div>
</div>

</body>
</html>
