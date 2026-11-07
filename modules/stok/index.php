<?php
// modules/stok/index.php - Monitoring Persediaan Stok Beras Terpisah Per Kategori
$page_title = 'Monitoring Persediaan Stok Beras';
$active_menu = 'stok';
require_once __DIR__ . '/../../config/auth.php';
check_login();

// Ambil stok per kategori
$kategori_list = query("SELECT * FROM kategori_beras ORDER BY id_kategori ASC");

$produk_by_kategori = [];
foreach ($kategori_list as $kat) {
    $id_kat = $kat['id_kategori'];
    $produk_by_kategori[$kat['kode_kategori']] = query("SELECT * FROM produk WHERE id_kategori = $id_kat ORDER BY is_eceran ASC, berat_kg ASC");
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Monitoring Persediaan Stok Beras Terpisah</h5>
    <p class="text-muted small mb-0">Pencatatan persediaan beras dipisah per kategori (SPHP BULOG, Medium Giling Sendiri, dan Premium Giling Sendiri).</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/modules/stok/mutasi.php" class="btn btn-outline-primary shadow-sm d-flex align-items-center gap-2">
      <i class="bi bi-arrow-left-right"></i>
      <span>Kartu Mutasi Stok</span>
    </a>
    <?php if (is_pemilik()): ?>
    <a href="<?= BASE_URL ?>/modules/stok/penyesuaian.php" class="btn btn-outline-secondary shadow-sm d-flex align-items-center gap-2">
      <i class="bi bi-sliders"></i>
      <span>Stock Opname / Koreksi</span>
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- 1. KATEGORI BERAS SPHP BULOG -->
<div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-primary">
  <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <span class="badge badge-sphp px-2 py-1">SPHP</span>
      <h6 class="mb-0 fw-bold text-dark">Stok Beras SPHP (Stabilisasi Pasokan & Harga Pangan BULOG)</h6>
    </div>
    <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/tambah.php" class="btn btn-sm btn-primary">
      <i class="bi bi-truck me-1"></i> Pengadaan ke BULOG
    </a>
  </div>
  <div class="card-body p-3">
    <div class="table-responsive">
      <table class="table table-hover table-custom mb-0 align-middle">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Produk</th>
            <th>Kemasan</th>
            <th>Harga Jual (HET)</th>
            <th class="text-center">Stok Tersedia</th>
            <th class="text-center">Batas Minimum</th>
            <th>Status Pasokan</th>
            <th>Pemicu Alur</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($produk_by_kategori['SPHP'] as $p): ?>
            <tr>
              <td><span class="fw-bold"><?= htmlspecialchars($p['kode_produk']) ?></span></td>
              <td class="fw-bold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['satuan']) ?></span></td>
              <td class="fw-semibold text-primary"><?= rupiah($p['harga_jual']) ?></td>
              <td class="text-center">
                <span class="fs-5 fw-bold <?= $p['stok'] <= $p['stok_minimum'] ? 'text-danger' : 'text-success' ?>">
                  <?= number_format($p['stok'], ($p['is_eceran'] ? 1 : 0)) ?>
                </span> <?= $p['is_eceran'] ? 'Kg' : 'Sak (' . number_format($p['stok'] * 5, 0) . ' kg)' ?>
              </td>
              <td class="text-center text-muted"><?= number_format($p['stok_minimum'], ($p['is_eceran'] ? 1 : 0)) ?> <?= $p['is_eceran'] ? 'Kg' : 'Sak' ?></td>
              <td>
                <?php if ($p['stok'] <= 0): ?>
                  <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Habis</span>
                <?php elseif ($p['stok'] <= $p['stok_minimum']): ?>
                  <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Menipis (Perlu Restock)</span>
                <?php else: ?>
                  <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aman</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($p['stok'] <= $p['stok_minimum']): ?>
                  <?php if (!$p['is_eceran']): ?>
                    <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/tambah.php" class="btn btn-sm btn-danger py-1 px-2">
                      <i class="bi bi-cart-plus me-1"></i> Order BULOG
                    </a>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>/modules/stok/penyesuaian.php" class="btn btn-sm btn-warning py-1 px-2 text-dark">
                      <i class="bi bi-sliders me-1"></i> Sesuaikan Stok
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted small">Terkendali</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- 2. KATEGORI BERAS MEDIUM (HASIL GILING SENDIRI) -->
<div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-success">
  <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <span class="badge badge-medium px-2 py-1">MEDIUM</span>
      <h6 class="mb-0 fw-bold text-dark">Stok Beras Medium (Hasil Penggilingan Sendiri)</h6>
    </div>
    <a href="<?= BASE_URL ?>/modules/produksi/tambah.php" class="btn btn-sm btn-success">
      <i class="bi bi-gear-wide-connected me-1"></i> Catat Giling Medium
    </a>
  </div>
  <div class="card-body p-3">
    <div class="table-responsive">
      <table class="table table-hover table-custom mb-0 align-middle">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Varian</th>
            <th>Satuan / Jenis</th>
            <th class="text-end">Harga Jual</th>
            <th class="text-center">Stok Saat Ini</th>
            <th class="text-center">Batas Minimum</th>
            <th>Status Stok</th>
            <th class="text-center">Sumber Penambahan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($produk_by_kategori['MEDIUM'] as $p): ?>
            <tr>
              <td><span class="fw-bold"><?= htmlspecialchars($p['kode_produk']) ?></span></td>
              <td class="fw-semibold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></td>
              <td>
                <?php if ($p['is_eceran']): ?>
                  <span class="badge badge-eceran me-1">Eceran</span> Liter
                <?php else: ?>
                  <span class="badge bg-light text-dark border me-1">Kemasan</span> <?= htmlspecialchars($p['satuan']) ?>
                <?php endif; ?>
              </td>
              <td class="text-end fw-semibold text-primary"><?= rupiah($p['harga_jual']) ?></td>
              <td class="text-center">
                <span class="fw-bold fs-6 <?= $p['stok'] <= $p['stok_minimum'] ? 'text-danger' : 'text-dark' ?>">
                  <?= number_format($p['stok'], ($p['is_eceran'] ? 1 : 0)) ?>
                </span> <?= $p['satuan'] ?>
              </td>
              <td class="text-center text-muted"><?= number_format($p['stok_minimum'], 0) ?></td>
              <td>
                <?php if ($p['stok'] <= $p['stok_minimum']): ?>
                  <span class="badge bg-warning text-dark">Menipis</span>
                <?php else: ?>
                  <span class="badge bg-success">Aman</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge bg-success-subtle text-success small">Produksi Giling Gabah</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- 3. KATEGORI BERAS PREMIUM (HASIL GILING SENDIRI) -->
<div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-purple" style="border-left-color: #7c3aed !important;">
  <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <span class="badge badge-premium px-2 py-1">PREMIUM</span>
      <h6 class="mb-0 fw-bold text-dark">Stok Beras Premium (Hasil Penggilingan Sendiri)</h6>
    </div>
    <a href="<?= BASE_URL ?>/modules/produksi/tambah.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-gear-wide-connected me-1"></i> Catat Giling Premium
    </a>
  </div>
  <div class="card-body p-3">
    <div class="table-responsive">
      <table class="table table-hover table-custom mb-0 align-middle">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Varian</th>
            <th>Satuan / Jenis</th>
            <th class="text-end">Harga Jual</th>
            <th class="text-center">Stok Saat Ini</th>
            <th class="text-center">Batas Minimum</th>
            <th>Status Stok</th>
            <th class="text-center">Sumber Penambahan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($produk_by_kategori['PREMIUM'] as $p): ?>
            <tr>
              <td><span class="fw-bold"><?= htmlspecialchars($p['kode_produk']) ?></span></td>
              <td class="fw-semibold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></td>
              <td>
                <?php if ($p['is_eceran']): ?>
                  <span class="badge badge-eceran me-1">Eceran</span> Liter
                <?php else: ?>
                  <span class="badge bg-light text-dark border me-1">Kemasan</span> <?= htmlspecialchars($p['satuan']) ?>
                <?php endif; ?>
              </td>
              <td class="text-end fw-semibold text-primary"><?= rupiah($p['harga_jual']) ?></td>
              <td class="text-center">
                <span class="fw-bold fs-6 <?= $p['stok'] <= $p['stok_minimum'] ? 'text-danger' : 'text-dark' ?>">
                  <?= number_format($p['stok'], ($p['is_eceran'] ? 1 : 0)) ?>
                </span> <?= $p['satuan'] ?>
              </td>
              <td class="text-center text-muted"><?= number_format($p['stok_minimum'], 0) ?></td>
              <td>
                <?php if ($p['stok'] <= $p['stok_minimum']): ?>
                  <span class="badge bg-warning text-dark">Menipis</span>
                <?php else: ?>
                  <span class="badge bg-success">Aman</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge bg-info-subtle text-primary small">Produksi Giling Gabah</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
