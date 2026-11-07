<?php
// modules/dashboard/index.php - Halaman Utama Dashboard
$page_title = 'Dashboard Utama';
$active_menu = 'dashboard';
require_once __DIR__ . '/../../config/auth.php';
check_login();

// 1. Data Penjualan Hari Ini
$today = date('Y-m-d');
$first_day_month = date('Y-m-01');

$penjualan_hari_ini = query_single("SELECT 
    COALESCE(SUM(total_harga), 0) as omzet_hari_ini,
    COUNT(id_penjualan) as jml_trx_hari_ini
    FROM penjualan WHERE DATE(tanggal_penjualan) = '$today'");

// 2. Data Penjualan Bulan Ini
$penjualan_bulan_ini = query_single("SELECT 
    COALESCE(SUM(total_harga), 0) as omzet_bulan_ini,
    COUNT(id_penjualan) as jml_trx_bulan_ini
    FROM penjualan WHERE DATE(tanggal_penjualan) >= '$first_day_month'");

// 3. Stok Produk Beras Per Kategori
$stok_sphp = query_single("SELECT p.*, k.nama_kategori 
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    WHERE k.kode_kategori = 'SPHP' LIMIT 1");

$stok_medium = query_single("SELECT 
    SUM(CASE WHEN is_eceran = 0 THEN stok ELSE 0 END) as stok_sak,
    SUM(CASE WHEN is_eceran = 1 THEN stok ELSE 0 END) as stok_liter
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    WHERE k.kode_kategori = 'MEDIUM'");

$stok_premium = query_single("SELECT 
    SUM(CASE WHEN is_eceran = 0 THEN stok ELSE 0 END) as stok_sak,
    SUM(CASE WHEN is_eceran = 1 THEN stok ELSE 0 END) as stok_liter
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    WHERE k.kode_kategori = 'PREMIUM'");

// 4. Kuota Mingguan SPHP (Batas 2 Ton)
$kuota = get_kuota_sphp_minggu_ini();

// 5. Riwayat 5 Transaksi Penjualan Terakhir
$transaksi_terakhir = query("SELECT p.*, u.nama_lengkap as kasir 
    FROM penjualan p 
    LEFT JOIN users u ON p.id_user = u.id_user 
    ORDER BY p.id_penjualan DESC LIMIT 5");

// 6. Riwayat 4 Produksi Giling Terakhir
$produksi_terakhir = query("SELECT pr.*, p.nama_produk, p.satuan, u.nama_lengkap as petugas 
    FROM produksi_giling pr 
    JOIN produk p ON pr.id_produk = p.id_produk 
    LEFT JOIN users u ON pr.id_user = u.id_user 
    ORDER BY pr.id_produksi DESC LIMIT 4");

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Alert Peringatan Stok SPHP Menipis / Kritis -->
<?php if ($stok_sphp): ?>
  <?php if ($stok_sphp['stok'] <= 0): ?>
    <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-exclamation-octagon-fill fs-4"></i>
        </div>
        <div>
          <h6 class="mb-0 fw-bold text-danger">PERHATIAN KRITIS: Stok Beras SPHP Telah HABIS (0 Sak)!</h6>
          <p class="mb-0 text-dark small">Segera lakukan pemesanan ulang beras SPHP ke BULOG agar pasokan untuk masyarakat tetap stabil.</p>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/tambah.php" class="btn btn-danger btn-sm px-3 fw-semibold">
        <i class="bi bi-cart-plus me-1"></i> Buat Pesanan ke BULOG
      </a>
    </div>
  <?php elseif ($stok_sphp['stok'] <= $stok_sphp['stok_minimum']): ?>
    <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-exclamation-triangle-fill fs-4"></i>
        </div>
        <div>
          <h6 class="mb-0 fw-bold text-warning-emphasis">Peringatan: Stok Beras SPHP Mulai Menipis!</h6>
          <p class="mb-0 text-dark small">Stok saat ini: <strong><?= number_format($stok_sphp['stok'], 0) ?> Sak</strong> (Batas minimum: <?= number_format($stok_sphp['stok_minimum'], 0) ?> Sak). Pertimbangkan pengadaan ke BULOG.</p>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/tambah.php" class="btn btn-warning btn-sm px-3 fw-semibold text-dark">
        <i class="bi bi-cart-plus me-1"></i> Pesan ke BULOG
      </a>
    </div>
  <?php endif; ?>
<?php endif; ?>

<!-- 4 Kotak Metrik Statistik -->
<div class="row g-3 mb-4">
  <!-- Penjualan Hari Ini -->
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card h-100">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted fw-semibold small">Omzet Hari Ini</span>
        <div class="stat-icon sphp">
          <i class="bi bi-cash-stack"></i>
        </div>
      </div>
      <h4 class="fw-bold mb-1 text-dark"><?= rupiah($penjualan_hari_ini['omzet_hari_ini']) ?></h4>
      <div class="text-muted small">
        <i class="bi bi-receipt me-1"></i> <strong><?= $penjualan_hari_ini['jml_trx_hari_ini'] ?></strong> transaksi hari ini
      </div>
    </div>
  </div>

  <!-- Penjualan Bulan Ini -->
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card h-100">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted fw-semibold small">Omzet Bulan Ini</span>
        <div class="stat-icon premium">
          <i class="bi bi-calendar-check"></i>
        </div>
      </div>
      <h4 class="fw-bold mb-1 text-dark"><?= rupiah($penjualan_bulan_ini['omzet_bulan_ini']) ?></h4>
      <div class="text-muted small">
        <i class="bi bi-bar-chart me-1"></i> <strong><?= $penjualan_bulan_ini['jml_trx_bulan_ini'] ?></strong> total transaksi
      </div>
    </div>
  </div>

  <!-- Stok Beras SPHP (BULOG) -->
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card h-100 border-start border-4 border-primary">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted fw-semibold small">Stok Beras SPHP</span>
        <span class="badge bg-primary">BULOG</span>
      </div>
      <h4 class="fw-bold mb-1 text-primary">
        <?= number_format($stok_sphp['stok'] ?? 0, 0) ?> <span class="fs-6 text-dark fw-semibold">Sak (5kg)</span>
      </h4>
      <div class="small">
        <?php if (($stok_sphp['stok'] ?? 0) <= ($stok_sphp['stok_minimum'] ?? 10)): ?>
          <span class="text-danger fw-semibold"><i class="bi bi-arrow-down-circle"></i> Stok Menipis</span>
        <?php else: ?>
          <span class="text-success fw-semibold"><i class="bi bi-check-circle"></i> Stok Aman</span>
        <?php endif; ?>
        <span class="text-muted">(<?= number_format(($stok_sphp['stok'] ?? 0) * 5, 0) ?> kg)</span>
      </div>
    </div>
  </div>

  <!-- Stok Beras Giling Sendiri (Medium & Premium) -->
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card h-100 border-start border-4 border-success">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted fw-semibold small">Beras Giling Sendiri</span>
        <span class="badge bg-success">Mandiri</span>
      </div>
      <div class="d-flex justify-content-between align-items-baseline mb-1">
        <div>
          <span class="text-muted small">Medium:</span> <strong><?= number_format($stok_medium['stok_sak'] ?? 0, 0) ?> Sak</strong> | <strong><?= number_format($stok_medium['stok_liter'] ?? 0, 0) ?> Ltr</strong>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-baseline small">
        <div>
          <span class="text-muted">Premium:</span> <strong><?= number_format($stok_premium['stok_sak'] ?? 0, 0) ?> Sak</strong> | <strong><?= number_format($stok_premium['stok_liter'] ?? 0, 0) ?> Ltr</strong>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Section Monitoring Kuota SPHP Mingguan BULOG (2 TON) & Shortcut Cepat -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-speedometer2 text-primary fs-5"></i>
          <h6 class="mb-0 fw-bold">Monitoring Kuota SPHP Mingguan (Batas 2 Ton dari BULOG)</h6>
        </div>
        <span class="badge bg-light text-dark border">
          Periode: <?= date('d M', strtotime($kuota['senin'])) ?> - <?= date('d M Y', strtotime($kuota['minggu'])) ?>
        </span>
      </div>
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <span class="text-muted small">Realisasi Pengadaan Minggu Ini:</span>
            <h5 class="fw-bold mb-0 text-dark">
              <?= number_format($kuota['terpakai_kg'], 0) ?> Kg <span class="text-muted fs-6 fw-normal">/ <?= number_format($kuota['batas_kg'], 0) ?> Kg (2 Ton)</span>
            </h5>
          </div>
          <div class="text-end">
            <span class="text-muted small">Sisa Alokasi Kuota:</span>
            <h5 class="fw-bold mb-0 <?= $kuota['sisa_kg'] < 200 ? 'text-danger' : 'text-success' ?>">
              <?= number_format($kuota['sisa_kg'], 0) ?> Kg <span class="text-muted fs-6 fw-normal">(<?= number_format($kuota['sisa_kg'] / 5, 0) ?> Sak)</span>
            </h5>
          </div>
        </div>

        <!-- Progress Bar Meter -->
        <div class="progress quota-progress mb-3" style="height: 18px;">
          <?php 
            $bar_class = 'bg-primary';
            if ($kuota['persen'] >= 100) $bar_class = 'bg-danger';
            elseif ($kuota['persen'] >= 75) $bar_class = 'bg-warning text-dark';
          ?>
          <div class="progress-bar <?= $bar_class ?> progress-bar-striped progress-bar-animated fw-bold" 
               role="progressbar" 
               style="width: <?= $kuota['persen'] ?>%;" 
               aria-valuenow="<?= $kuota['persen'] ?>" 
               aria-valuemin="0" 
               aria-valuemax="100">
            <?= $kuota['persen'] ?>%
          </div>
        </div>

        <div class="row g-2 small text-muted pt-2 border-top">
          <div class="col-md-6">
            <i class="bi bi-info-circle text-primary me-1"></i> Batas pengambilan beras SPHP ke BULOG maksimal <strong>2.000 kg (400 sak @ 5kg)</strong> setiap pekan.
          </div>
          <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/modules/laporan/kuota_sphp.php" class="text-decoration-none fw-semibold">
              Lihat Detail Laporan Kuota Mingguan <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Aksi Cepat / Shortcut -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge text-warning me-2"></i>Aksi Cepat</h6>
      </div>
      <div class="card-body p-3 d-flex flex-column gap-2 justify-content-center">
        <a href="<?= BASE_URL ?>/modules/kasir/index.php" class="btn btn-primary d-flex align-items-center justify-content-between p-2 px-3 rounded-3 shadow-sm">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cart4 fs-5"></i>
            <div class="text-start">
              <div class="fw-bold">Kasir Penjualan</div>
              <small class="opacity-75">Buka halaman POS</small>
            </div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>

        <a href="<?= BASE_URL ?>/modules/produksi/tambah.php" class="btn btn-outline-success d-flex align-items-center justify-content-between p-2 px-3 rounded-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-gear-wide-connected fs-5"></i>
            <div class="text-start">
              <div class="fw-bold">Input Giling Sendiri</div>
              <small class="text-muted">Beras Medium & Premium</small>
            </div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>

        <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/tambah.php" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-2 px-3 rounded-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-truck fs-5"></i>
            <div class="text-start">
              <div class="fw-bold">Pengadaan SPHP BULOG</div>
              <small class="text-muted">Catat order ke BULOG</small>
            </div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Tabel Transaksi Terakhir & Produksi Terakhir -->
<div class="row g-4">
  <!-- 5 Transaksi Terakhir -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-receipt text-primary me-2"></i>Transaksi Penjualan Terbaru</h6>
        <a href="<?= BASE_URL ?>/modules/kasir/riwayat.php" class="btn btn-sm btn-light border small">Semua Transaksi</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-custom mb-0 align-middle">
          <thead>
            <tr>
              <th>No. Faktur</th>
              <th>Waktu</th>
              <th>Pembeli</th>
              <th>Jenis Beras</th>
              <th class="text-end">Total</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($transaksi_terakhir)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi penjualan.</td></tr>
            <?php else: ?>
              <?php foreach ($transaksi_terakhir as $trx): ?>
                <tr>
                  <td>
                    <span class="fw-bold text-dark"><?= htmlspecialchars($trx['no_faktur']) ?></span>
                  </td>
                  <td class="small text-muted">
                    <?= date('d/m/Y H:i', strtotime($trx['tanggal_penjualan'])) ?>
                  </td>
                  <td>
                    <?php if ($trx['nama_pembeli']): ?>
                      <div class="fw-semibold small"><?= htmlspecialchars($trx['nama_pembeli']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars($trx['no_hp_pembeli'] ?? '-') ?></small>
                    <?php else: ?>
                      <span class="text-muted small">Pembeli Umum</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($trx['ada_sphp']): ?>
                      <span class="badge badge-sphp">SPHP</span>
                    <?php endif; ?>
                    <span class="badge bg-secondary small"><?= (int)$trx['total_kuantiti'] ?> Item</span>
                  </td>
                  <td class="text-end fw-bold text-dark">
                    <?= rupiah($trx['total_harga']) ?>
                  </td>
                  <td class="text-center">
                    <a href="<?= BASE_URL ?>/modules/kasir/struk.php?id=<?= $trx['id_penjualan'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak Struk">
                      <i class="bi bi-printer"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Produksi Giling Sendiri Terakhir -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-gear text-success me-2"></i>Hasil Giling Padi Terbaru</h6>
        <a href="<?= BASE_URL ?>/modules/produksi/index.php" class="btn btn-sm btn-light border small">Semua Riwayat</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-custom mb-0 align-middle">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Produk Beras</th>
              <th>Hasil Giling</th>
              <th>Tanggal</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($produksi_terakhir)): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">Belum ada catatan giling.</td></tr>
            <?php else: ?>
              <?php foreach ($produksi_terakhir as $prd): ?>
                <tr>
                  <td class="small fw-semibold text-muted"><?= htmlspecialchars($prd['kode_produksi']) ?></td>
                  <td>
                    <div class="fw-semibold small text-dark"><?= htmlspecialchars($prd['nama_produk']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($prd['satuan']) ?></small>
                  </td>
                  <td>
                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">
                      +<?= number_format($prd['jumlah_hasil'], 0) ?> <?= $prd['satuan'] ?>
                    </span>
                  </td>
                  <td class="small text-muted"><?= date('d/m/Y', strtotime($prd['tanggal_giling'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
