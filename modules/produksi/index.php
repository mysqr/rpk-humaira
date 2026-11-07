<?php
// modules/produksi/index.php - Riwayat Produksi Giling Sendiri (Medium & Premium)
$page_title = 'Produksi Giling Sendiri (Medium & Premium)';
$active_menu = 'produksi';
require_once __DIR__ . '/../../config/auth.php';
check_login();

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$kategori_filter = $_GET['kategori'] ?? '';

$where = "WHERE pg.tanggal_giling BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
if (!empty($kategori_filter)) {
    $where .= " AND k.kode_kategori = '" . escape($kategori_filter) . "'";
}

$sql = "SELECT pg.*, p.nama_produk, p.satuan, p.is_eceran, k.nama_kategori, k.kode_kategori, u.nama_lengkap as nama_petugas 
        FROM produksi_giling pg
        JOIN produk p ON pg.id_produk = p.id_produk
        JOIN kategori_beras k ON p.id_kategori = k.id_kategori
        LEFT JOIN users u ON pg.id_user = u.id_user
        $where
        ORDER BY pg.tanggal_giling DESC, pg.id_produksi DESC";

$riwayat_produksi = query($sql);

// Total ringkasan
$total_hasil_sak = 0;
$total_hasil_liter = 0;
$total_gabah = 0;
foreach ($riwayat_produksi as $r) {
    $total_gabah += (float)$r['jumlah_gabah_kg'];
    if ($r['is_eceran'] == 1) {
        $total_hasil_liter += (float)$r['jumlah_hasil'];
    } else {
        $total_hasil_sak += (float)$r['jumlah_hasil'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Catatan Produksi Giling Beras Sendiri</h5>
    <p class="text-muted small mb-0">Pencatatan hasil penggilingan gabah padi menjadi Beras Medium dan Premium (kemasan sak & eceran liter).</p>
  </div>
  <a href="<?= BASE_URL ?>/modules/produksi/tambah.php" class="btn btn-success px-3 shadow-sm d-flex align-items-center gap-2">
    <i class="bi bi-plus-circle"></i>
    <span class="fw-semibold">Catat Hasil Giling Baru</span>
  </a>
</div>

<!-- Info Cards Summary Giling -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Gabah Digiling (Periode Ini)</div>
      <h4 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_gabah, 1) ?> <span class="fs-6 text-dark fw-semibold">Kg Gabah</span></h4>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Beras Kemasan Dihasilkan</div>
      <h4 class="fw-bold text-success mt-1 mb-0"><?= number_format($total_hasil_sak, 0) ?> <span class="fs-6 text-dark fw-semibold">Sak Kemasan</span></h4>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Beras Curah/Liter Dihasilkan</div>
      <h4 class="fw-bold text-warning mt-1 mb-0"><?= number_format($total_hasil_liter, 1) ?> <span class="fs-6 text-dark fw-semibold">Liter</span></h4>
    </div>
  </div>
</div>

<!-- Filter Periode Tanggal -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="" class="row g-2 align-items-center">
      <div class="col-md-3">
        <label class="form-label small mb-1 fw-semibold">Dari Tanggal</label>
        <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1 fw-semibold">Sampai Tanggal</label>
        <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1 fw-semibold">Kategori Beras</label>
        <select name="kategori" class="form-select form-select-sm">
          <option value="">-- Semua Giling (Med & Pre) --</option>
          <option value="MEDIUM" <?= $kategori_filter === 'MEDIUM' ? 'selected' : '' ?>>Beras Medium</option>
          <option value="PREMIUM" <?= $kategori_filter === 'PREMIUM' ? 'selected' : '' ?>>Beras Premium</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2 align-items-end">
        <button type="submit" class="btn btn-secondary btn-sm flex-fill"><i class="bi bi-filter"></i> Filter</button>
        <a href="<?= BASE_URL ?>/modules/produksi/index.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Riwayat Produksi -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th>No. Produksi</th>
          <th>Tanggal Giling</th>
          <th>Produk Beras Dihasilkan</th>
          <th>Kategori</th>
          <th class="text-end">Bahan Gabah</th>
          <th class="text-center">Jumlah Hasil</th>
          <th class="text-end">Biaya Giling</th>
          <th>Petugas Input</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($riwayat_produksi)): ?>
          <tr><td colspan="9" class="text-center py-5 text-muted">Belum ada catatan produksi giling pada periode ini.</td></tr>
        <?php else: ?>
          <?php foreach ($riwayat_produksi as $rp): ?>
            <tr>
              <td>
                <span class="fw-bold text-dark"><?= htmlspecialchars($rp['kode_produksi']) ?></span>
              </td>
              <td><?= tgl_indo($rp['tanggal_giling']) ?></td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($rp['nama_produk']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($rp['satuan']) ?></small>
              </td>
              <td>
                <span class="badge <?= $rp['kode_kategori'] === 'PREMIUM' ? 'badge-premium' : 'badge-medium' ?>">
                  <?= htmlspecialchars($rp['nama_kategori']) ?>
                </span>
              </td>
              <td class="text-end">
                <?= $rp['jumlah_gabah_kg'] > 0 ? number_format($rp['jumlah_gabah_kg'], 1) . ' Kg' : '-' ?>
              </td>
              <td class="text-center">
                <span class="badge bg-success-subtle text-success fs-6 fw-bold px-2 py-1">
                  +<?= number_format($rp['jumlah_hasil'], ($rp['is_eceran'] ? 1 : 0)) ?> <?= $rp['satuan'] ?>
                </span>
              </td>
              <td class="text-end text-muted">
                <?= $rp['biaya_giling'] > 0 ? rupiah($rp['biaya_giling']) : '-' ?>
              </td>
              <td class="small text-muted">
                <?= htmlspecialchars($rp['nama_petugas'] ?? 'Petugas') ?>
              </td>
              <td class="text-center text-nowrap">
                <div class="btn-group btn-group-sm" role="group">
                  <a href="<?= BASE_URL ?>/modules/produksi/detail.php?id=<?= $rp['id_produksi'] ?>" class="btn btn-outline-info" title="Detail Produksi">
                    <i class="bi bi-eye"></i> Detail
                  </a>
                  <a href="<?= BASE_URL ?>/modules/produksi/edit.php?id=<?= $rp['id_produksi'] ?>" class="btn btn-outline-primary" title="Edit Hasil Giling">
                    <i class="bi bi-pencil-square"></i> Edit
                  </a>
                  <a href="<?= BASE_URL ?>/modules/produksi/hapus.php?id=<?= $rp['id_produksi'] ?>" class="btn btn-outline-danger" title="Hapus Catatan" onclick="return confirm('Apakah Anda yakin ingin menghapus catatan produksi <?= htmlspecialchars($rp['kode_produksi']) ?>? Stok beras (+<?= $rp['jumlah_hasil'] ?> <?= $rp['satuan'] ?>) akan otomatis dikurangi kembali.')">
                    <i class="bi bi-trash"></i> Hapus
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
