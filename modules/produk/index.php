<?php
// modules/produk/index.php - Data Master Produk Beras
$page_title = 'Data Master Produk Beras';
$active_menu = 'produk';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

// Filter kategori
$filter_kat = $_GET['kategori'] ?? '';
$search = escape($_GET['search'] ?? '');

$where = "WHERE 1=1";
if (!empty($filter_kat)) {
    $where .= " AND k.kode_kategori = '" . escape($filter_kat) . "'";
}
if (!empty($search)) {
    $where .= " AND (p.nama_produk LIKE '%$search%' OR p.kode_produk LIKE '%$search%')";
}

$produk_list = query("SELECT p.*, k.nama_kategori, k.kode_kategori 
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    $where 
    ORDER BY p.id_kategori ASC, p.is_eceran ASC, p.berat_kg ASC");

$kategori_list = query("SELECT * FROM kategori_beras ORDER BY id_kategori ASC");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Daftar Produk & Varian Kemasan Beras</h5>
    <p class="text-muted small mb-0">Kelola master beras SPHP (kemasan 5kg & eceran per kg), serta Beras Medium & Premium (kemasan & eceran).</p>
  </div>
  <?php if (is_pemilik()): ?>
  <a href="<?= BASE_URL ?>/modules/produk/tambah.php" class="btn btn-primary px-3 shadow-sm d-flex align-items-center gap-2">
    <i class="bi bi-plus-circle"></i>
    <span class="fw-semibold">Tambah Produk Beras</span>
  </a>
  <?php endif; ?>
</div>

<!-- Filter & Pencarian -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="" class="row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama atau kode beras..." value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>
      <div class="col-md-3">
        <select name="kategori" class="form-select" onchange="this.form.submit()">
          <option value="">-- Semua Kategori Beras --</option>
          <?php foreach ($kategori_list as $kat): ?>
            <option value="<?= $kat['kode_kategori'] ?>" <?= $filter_kat === $kat['kode_kategori'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($kat['nama_kategori']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i> Filter</button>
      </div>
      <?php if (!empty($filter_kat) || !empty($search)): ?>
      <div class="col-md-2">
        <a href="<?= BASE_URL ?>/modules/produk/index.php" class="btn btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i> Reset</a>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Tabel Master Produk -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th>Kode & Nama Produk</th>
          <th>Kategori</th>
          <th>Satuan / Jenis</th>
          <th class="text-end">Harga Beli (HPP)</th>
          <th class="text-end">Harga Jual</th>
          <th class="text-center">Stok</th>
          <th>Status Stok</th>
          <?php if (is_pemilik()): ?>
          <th class="text-center" style="width: 130px;">Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($produk_list)): ?>
          <tr><td colspan="9" class="text-center py-5 text-muted">Tidak ada data produk ditemukan.</td></tr>
        <?php else: ?>
          <?php $no = 1; foreach ($produk_list as $p): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td>
                <div class="fw-bold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></div>
                <small class="text-muted"><i class="bi bi-upc-scan me-1"></i><?= htmlspecialchars($p['kode_produk']) ?></small>
              </td>
              <td>
                <?php if ($p['kode_kategori'] === 'SPHP'): ?>
                  <span class="badge badge-sphp">SPHP (BULOG)</span>
                <?php elseif ($p['kode_kategori'] === 'MEDIUM'): ?>
                  <span class="badge badge-medium">Medium</span>
                <?php else: ?>
                  <span class="badge badge-premium">Premium</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($p['is_eceran'] == 1): ?>
                  <span class="badge badge-eceran me-1">Eceran</span>
                  <span class="fw-semibold text-dark"><?= htmlspecialchars($p['satuan']) ?></span>
                <?php else: ?>
                  <span class="badge bg-light text-dark border me-1">Kemasan</span>
                  <span class="fw-semibold text-dark"><?= htmlspecialchars($p['satuan']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-end text-muted">
                <?= rupiah($p['harga_beli']) ?>
              </td>
              <td class="text-end fw-bold text-primary">
                <?= rupiah($p['harga_jual']) ?>
              </td>
              <td class="text-center">
                <span class="fw-bold fs-6 <?= $p['stok'] <= $p['stok_minimum'] ? 'text-danger' : 'text-dark' ?>">
                  <?= number_format($p['stok'], ($p['is_eceran'] ? 1 : 0)) ?>
                </span>
                <small class="text-muted d-block" style="font-size: 0.75rem;"><?= $p['satuan'] ?></small>
              </td>
              <td>
                <?php if ($p['stok'] <= 0): ?>
                  <span class="badge bg-danger">Habis</span>
                <?php elseif ($p['stok'] <= $p['stok_minimum']): ?>
                  <span class="badge bg-warning text-dark">Menipis (Min: <?= number_format($p['stok_minimum'], 0) ?>)</span>
                <?php else: ?>
                  <span class="badge bg-success">Aman</span>
                <?php endif; ?>
              </td>
              <?php if (is_pemilik()): ?>
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                  <a href="<?= BASE_URL ?>/modules/produk/edit.php?id=<?= $p['id_produk'] ?>" class="btn btn-outline-primary" title="Edit Produk">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                  <a href="<?= BASE_URL ?>/modules/produk/hapus.php?id=<?= $p['id_produk'] ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin ingin menghapus produk <?= addslashes($p['nama_produk']) ?>?')" title="Hapus">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Catatan Informasi Ketentuan Tugas Akhir -->
<div class="alert alert-info border-0 shadow-sm rounded-3 mt-4 small">
  <div class="d-flex gap-2">
    <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
    <div>
      <h6 class="fw-bold mb-1">Ketentuan Master Produk:</h6>
      <ul class="mb-0 ps-3">
        <li><strong>Beras SPHP:</strong> Hanya tersedia dalam <em>Kemasan Sak 5 Kg utuh</em> dan <u>TIDAK BOLEH</u> dijual dalam bentuk eceran sesuai petunjuk teknis stabilisasi pangan BULOG.</li>
        <li><strong>Beras Medium & Beras Premium:</strong> Merupakan komoditas <em>hasil giling sendiri</em>. Tersedia dalam kemasan sak (5kg, 10kg, 25kg) maupun eceran curah dalam satuan <strong>Liter</strong>. Penambahan stok didapat melalui modul <strong>Giling Sendiri</strong>.</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
