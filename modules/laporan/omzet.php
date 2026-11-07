<?php
// modules/laporan/omzet.php - Laporan Omzet & Laba Berdasarkan Kategori Produk
$page_title = 'Laporan Omzet Berdasarkan Kategori Beras';
$active_menu = 'lap_omzet';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']); // Khusus pemilik toko

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// Query agregasi omzet dan laba per kategori produk
$sql = "SELECT kb.id_kategori, kb.kode_kategori, kb.nama_kategori,
               COUNT(DISTINCT pd.id_penjualan) as jml_transaksi,
               SUM(CASE WHEN pr.is_eceran = 0 THEN pd.jumlah ELSE 0 END) as total_sak,
               SUM(CASE WHEN pr.is_eceran = 1 THEN pd.jumlah ELSE 0 END) as total_liter,
               COALESCE(SUM(pd.subtotal), 0) as total_omzet,
               COALESCE(SUM(pd.jumlah * pd.harga_beli), 0) as total_hpp,
               COALESCE(SUM(pd.laba), 0) as total_laba
        FROM kategori_beras kb
        LEFT JOIN produk pr ON kb.id_kategori = pr.id_kategori
        LEFT JOIN penjualan_detail pd ON pr.id_produk = pd.id_produk
        LEFT JOIN penjualan p ON pd.id_penjualan = p.id_penjualan AND DATE(p.tanggal_penjualan) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
        GROUP BY kb.id_kategori, kb.kode_kategori, kb.nama_kategori
        ORDER BY kb.id_kategori ASC";

$omzet_kategori = query($sql);

$grand_omzet = 0;
$grand_hpp = 0;
$grand_laba = 0;
$grand_sak = 0;
$grand_liter = 0;

foreach ($omzet_kategori as $ok) {
    $grand_omzet += (float)$ok['total_omzet'];
    $grand_hpp += (float)$ok['total_hpp'];
    $grand_laba += (float)$ok['total_laba'];
    $grand_sak += (float)$ok['total_sak'];
    $grand_liter += (float)$ok['total_liter'];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
  <div>
    <h5 class="fw-bold mb-1">Laporan Omzet & Laba Berdasarkan Kategori Beras</h5>
    <p class="text-muted small mb-0">Analisis perolehan pendapatan dan modal (HPP) beras SPHP, Medium, dan Premium.</p>
  </div>
  <button type="button" class="btn btn-outline-secondary shadow-sm" onclick="window.print()">
    <i class="bi bi-printer me-1"></i> Cetak Laporan Omzet
  </button>
</div>

<!-- Kop Surat Cetak Print -->
<div class="d-none d-print-block print-header">
  <h4><?= strtoupper(htmlspecialchars($toko_info['nama_toko'])) ?></h4>
  <div class="print-sub"><?= htmlspecialchars($toko_info['mitra']) ?> | <?= htmlspecialchars($toko_info['alamat']) ?> | Telp: <?= htmlspecialchars($toko_info['telepon']) ?></div>
  <div class="print-doc-title">LAPORAN OMZET PER KATEGORI PRODUK BERAS</div>
  <div class="print-period">Periode: <?= tgl_indo($tgl_mulai) ?> s/d <?= tgl_indo($tgl_selesai) ?></div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Omzet Penjualan (Kotor)</div>
      <h3 class="fw-bold text-primary mt-1 mb-0"><?= rupiah($grand_omzet) ?></h3>
      <small class="text-dark">Total penerimaan kasir</small>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Modal / HPP Pengadaan</div>
      <h3 class="fw-bold text-dark mt-1 mb-0"><?= rupiah($grand_hpp) ?></h3>
      <small class="text-dark">Biaya tebus BULOG & HPP gabah</small>
    </div>
  </div>
</div>

<!-- Filter Periode -->
<div class="card border-0 shadow-sm rounded-3 mb-4 no-print">
  <div class="card-body p-3">
    <form method="GET" action="" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small mb-1 fw-semibold">Dari Tanggal</label>
        <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small mb-1 fw-semibold">Sampai Tanggal</label>
        <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai) ?>">
      </div>
      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-filter"></i> Analisis Omzet</button>
        <a href="<?= BASE_URL ?>/modules/laporan/omzet.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Omzet Berdasarkan Kategori -->
<div class="card border shadow-sm rounded-3 mb-4">
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th>Kategori Beras</th>
          <th class="text-center">Volume Terjual (Kemasan)</th>
          <th class="text-center">Volume Terjual (Eceran Liter)</th>
          <th class="text-end">Total Omzet Penjualan</th>
          <th class="text-end">Total HPP (Modal)</th>
          <th class="text-center" style="width: 180px;">Kontribusi Omzet (%)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($omzet_kategori as $row): ?>
          <?php
            $persen_kontribusi = $grand_omzet > 0 ? round(($row['total_omzet'] / $grand_omzet) * 100, 1) : 0;
            $badge_style = 'badge-medium';
            if ($row['kode_kategori'] === 'SPHP') $badge_style = 'badge-sphp';
            elseif ($row['kode_kategori'] === 'PREMIUM') $badge_style = 'badge-premium';
          ?>
          <tr>
            <td>
              <span class="badge <?= $badge_style ?> fs-6 px-2 py-1 mb-1"><?= htmlspecialchars($row['nama_kategori']) ?></span>
              <small class="d-block text-muted"><?= $row['jml_transaksi'] ?>x transaksi terjadi</small>
            </td>
            <td class="text-center fw-bold">
              <?= number_format($row['total_sak']) ?> Sak
            </td>
            <td class="text-center fw-semibold">
              <?= $row['total_liter'] > 0 ? number_format($row['total_liter'], 1) . ' Liter' : '<span class="text-muted">-</span>' ?>
            </td>
            <td class="text-end fw-bold text-dark fs-6">
              <?= rupiah($row['total_omzet']) ?>
            </td>
            <td class="text-end text-muted">
              <?= rupiah($row['total_hpp']) ?>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-fill" style="height: 10px;">
                  <div class="progress-bar <?= $row['kode_kategori'] === 'SPHP' ? 'bg-primary' : ($row['kode_kategori'] === 'PREMIUM' ? 'bg-purple' : 'bg-success') ?>" 
                       style="width: <?= $persen_kontribusi ?>%; <?= $row['kode_kategori'] === 'PREMIUM' ? 'background-color: #7c3aed;' : '' ?>"></div>
                </div>
                <span class="small fw-bold text-dark" style="min-width: 40px;"><?= $persen_kontribusi ?>%</span>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="fw-bold bg-light fs-6">
          <td>TOTAL KESELURUHAN:</td>
          <td class="text-center"><?= number_format($grand_sak) ?> Sak</td>
          <td class="text-center"><?= number_format($grand_liter, 1) ?> Liter</td>
          <td class="text-end text-primary"><?= rupiah($grand_omzet) ?></td>
          <td class="text-end text-secondary"><?= rupiah($grand_hpp) ?></td>
          <td class="text-center">100%</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- Tanda Tangan Cetak Laporan -->
<div class="d-none d-print-block print-signature">
  <div class="sign-box">
    <div>Dicetak pada: <?= date('d/m/Y H:i') ?> WIB</div>
    <div style="font-size: 8.5pt; color: #555;">Petugas / Kasir:</div>
    <div class="sign-space"></div>
    <div class="sign-name">( <?= htmlspecialchars($user['nama_lengkap'] ?? 'Petugas') ?> )</div>
  </div>
  <div class="sign-box">
    <div>Mengetahui,</div>
    <div style="font-size: 8.5pt; color: #555;">Pemilik RPK Humaira</div>
    <div class="sign-space"></div>
    <div class="sign-name">( <?= htmlspecialchars($toko_info['nama_pemilik']) ?> )</div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
