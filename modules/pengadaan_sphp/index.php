<?php
// modules/pengadaan_sphp/index.php - Daftar Pengadaan Beras SPHP dari Perum BULOG
$page_title = 'Pengadaan Beras SPHP BULOG';
$active_menu = 'pengadaan_sphp';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

// Ambil data produk SPHP untuk stok saat ini
$produk_sphp = query_single("SELECT p.stok, p.nama_produk
    FROM produk p
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori
    WHERE k.kode_kategori = 'SPHP' LIMIT 1");

$stok_saat_ini_sak = $produk_sphp ? (float)$produk_sphp['stok'] : 0;
$kuota_total_sak   = 400;
$batas_restock_sak = 40; // 10% dari 400
$persen_stok_sisa  = ($kuota_total_sak > 0) ? round(($stok_saat_ini_sak / $kuota_total_sak) * 100, 1) : 0;
$boleh_restock     = ($stok_saat_ini_sak <= $batas_restock_sak);

// Kuota Mingguan BULOG
$kuota = get_kuota_sphp_minggu_ini();

// Filter Status
$status_filter = $_GET['status'] ?? '';
$where = "WHERE 1=1";
if (!empty($status_filter)) {
    $where .= " AND p.status = '" . escape($status_filter) . "'";
}

$sql = "SELECT p.*, pr.nama_produk, pr.satuan, u.nama_lengkap as pembuat, ua.nama_lengkap as penyetuju
        FROM pengadaan_sphp p
        JOIN produk pr ON p.id_produk = pr.id_produk
        LEFT JOIN users u ON p.id_user_created = u.id_user
        LEFT JOIN users ua ON p.id_user_approved = ua.id_user
        $where
        ORDER BY p.tanggal_order DESC, p.id_pengadaan DESC";

$pengadaan_list = query($sql);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Pengadaan Beras SPHP dari Perum BULOG</h5>
    <p class="text-muted small mb-0">Isi form → Dokumen Order Penjualan BULOG → Approve → Stok Bertambah Otomatis.</p>
  </div>
  <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/tambah.php" 
     class="btn <?= $boleh_restock ? 'btn-primary' : 'btn-outline-secondary' ?> px-3 shadow-sm d-flex align-items-center gap-2">
    <i class="bi bi-plus-circle"></i>
    <span class="fw-semibold">Buat Permintaan Pengadaan SPHP</span>
  </a>
</div>

<!-- ===== KARTU MONITORING STATUS STOK SPHP (Validasi 10%) ===== -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-4">
    <div class="row g-3 align-items-center">

      <!-- Kiri: Status Stok & Progress -->
      <div class="col-lg-7">
        <div class="d-flex align-items-center gap-2 mb-2">
          <?php if ($boleh_restock): ?>
            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> SIAP RESTOCK</span>
          <?php else: ?>
            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> BELUM WAKTUNYA RESTOCK</span>
          <?php endif; ?>
          <h6 class="fw-bold mb-0 text-dark">Monitoring Stok Beras SPHP Toko</h6>
        </div>
        <p class="text-muted small mb-2">
          Restock ke BULOG diperbolehkan jika sisa stok sudah <strong>≤ 10%</strong>
          dari kuota total <strong><?= number_format($kuota_total_sak, 0) ?> Sak (2.000 Kg)</strong>.
          Ambang batas: <strong class="text-danger">≤ <?= $batas_restock_sak ?> Sak (<?= $batas_restock_sak * 5 ?> Kg)</strong>.
        </p>
        <!-- Progress Bar Stok -->
        <div class="progress mb-1" style="height: 20px; border-radius: 10px;">
          <?php
            if ($persen_stok_sisa <= 10) $bar_cls = 'bg-danger';
            elseif ($persen_stok_sisa <= 30) $bar_cls = 'bg-warning';
            else $bar_cls = 'bg-success';
          ?>
          <div class="progress-bar <?= $bar_cls ?> progress-bar-striped fw-bold" 
               style="width: <?= min(100, $persen_stok_sisa) ?>%; transition: width 0.6s ease; font-size: 0.75rem;"
               title="Stok: <?= number_format($stok_saat_ini_sak, 0) ?> Sak">
            <?= $persen_stok_sisa ?>%
          </div>
        </div>
        <div class="d-flex justify-content-between small text-muted mt-1">
          <span>0 Sak</span>
          <span style="position: relative;">
            <span style="position: absolute; left: 0; transform: translateX(-50%);">
              ⬆ Batas Restock (10% = <?= $batas_restock_sak ?> Sak)
            </span>
          </span>
          <span><?= number_format($kuota_total_sak, 0) ?> Sak</span>
        </div>
      </div>

      <!-- Kanan: Ringkasan Angka Stok -->
      <div class="col-lg-5">
        <div class="row g-2 text-center">
          <div class="col-4">
            <div class="p-2 bg-light rounded-3 border">
              <div class="small text-muted fw-semibold">Stok Saat Ini</div>
              <div class="fw-bold <?= $boleh_restock ? 'text-danger' : 'text-success' ?> fs-5"><?= number_format($stok_saat_ini_sak, 0) ?></div>
              <div class="small text-muted">Sak</div>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-light rounded-3 border">
              <div class="small text-muted fw-semibold">Persentase</div>
              <div class="fw-bold <?= $persen_stok_sisa <= 10 ? 'text-danger' : 'text-dark' ?> fs-5"><?= $persen_stok_sisa ?>%</div>
              <div class="small text-muted">dari Kuota</div>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 <?= $boleh_restock ? 'bg-success' : 'bg-light' ?> rounded-3 border <?= $boleh_restock ? 'border-success text-white' : '' ?>">
              <div class="small fw-semibold <?= $boleh_restock ? 'text-white-50' : 'text-muted' ?>">Status</div>
              <div class="fw-bold fs-6 mt-1"><?= $boleh_restock ? '✅ Boleh' : '⏳ Tunggu' ?></div>
              <div class="small <?= $boleh_restock ? 'text-white-50' : 'text-muted' ?>">Restock</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ===== KARTU KUOTA MINGGUAN BULOG (2 Ton) ===== -->
<div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-info">
  <div class="card-body py-3 px-4">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-info text-dark">KUOTA BULOG</span>
          <h6 class="fw-bold mb-0 text-dark">Alokasi Pengambilan SPHP: Maks. 2 Ton / Minggu</h6>
        </div>
        <p class="text-muted small mb-2">
          Periode: <strong><?= date('d M', strtotime($kuota['senin'])) ?> - <?= date('d M Y', strtotime($kuota['minggu'])) ?></strong>
        </p>
        <div class="progress" style="height: 12px;">
          <?php
            $bar_kuota = 'bg-primary';
            if ($kuota['persen'] >= 100) $bar_kuota = 'bg-danger';
            elseif ($kuota['persen'] >= 75) $bar_kuota = 'bg-warning';
          ?>
          <div class="progress-bar <?= $bar_kuota ?> progress-bar-striped" style="width: <?= $kuota['persen'] ?>%;"></div>
        </div>
      </div>
      <div class="col-lg-3 col-6 text-center border-start py-2">
        <div class="small text-muted fw-semibold">Terpakai Minggu Ini</div>
        <h5 class="fw-bold text-dark mb-0"><?= number_format($kuota['terpakai_kg'], 0) ?> <small class="fs-6">Kg</small></h5>
        <small class="text-muted">(<?= number_format($kuota['terpakai_kg'] / 5, 0) ?> Sak)</small>
      </div>
      <div class="col-lg-3 col-6 text-center py-2">
        <div class="small text-muted fw-semibold">Sisa Kuota</div>
        <h5 class="fw-bold <?= $kuota['sisa_kg'] < 200 ? 'text-danger' : 'text-success' ?> mb-0">
          <?= number_format($kuota['sisa_kg'], 0) ?> <small class="fs-6">Kg</small>
        </h5>
        <small class="text-muted">(<?= number_format($kuota['sisa_kg'] / 5, 0) ?> Sak)</small>
      </div>
    </div>
  </div>
</div>

<!-- Filter Status -->
<div class="card border-0 shadow-sm rounded-3 mb-3">
  <div class="card-body p-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="small text-muted fw-semibold me-1">Filter:</span>
      <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/index.php" class="btn btn-sm <?= empty($status_filter) ? 'btn-primary' : 'btn-light border' ?>">Semua</a>
      <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/index.php?status=diajukan" class="btn btn-sm <?= $status_filter === 'diajukan' ? 'btn-secondary text-white' : 'btn-light border' ?>">Menunggu Approve</a>
      <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/index.php?status=selesai" class="btn btn-sm <?= $status_filter === 'selesai' ? 'btn-success text-white' : 'btn-light border' ?>">✅ Selesai (Stok Masuk)</a>
      <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/index.php?status=batal" class="btn btn-sm <?= $status_filter === 'batal' ? 'btn-danger text-white' : 'btn-light border' ?>">Batal</a>
    </div>
  </div>
</div>

<!-- Tabel Daftar Pengadaan SPHP -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th>No. SO / Referensi</th>
          <th>Tgl Order</th>
          <th class="text-center">Volume</th>
          <th class="text-end">Total Biaya</th>
          <th class="text-center">Status</th>
          <th class="text-center" style="width: 180px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pengadaan_list)): ?>
          <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data pengadaan SPHP.</td></tr>
        <?php else: ?>
          <?php foreach ($pengadaan_list as $pg): ?>
            <tr>
              <td>
                <div class="fw-bold text-dark small"><?= htmlspecialchars($pg['no_ref_bulog'] ?: $pg['no_pengadaan']) ?></div>
                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($pg['no_pengadaan']) ?></div>
              </td>
              <td class="small"><?= tgl_indo($pg['tanggal_order']) ?></td>
              <td class="text-center">
                <span class="badge badge-sphp fs-6 px-2 py-1"><?= number_format($pg['jumlah_sak'], 0) ?> Sak</span>
                <small class="d-block text-muted" style="font-size: 0.73rem;"><?= number_format($pg['total_berat_kg'], 0) ?> Kg</small>
              </td>
              <td class="text-end fw-bold text-dark small"><?= rupiah($pg['total_biaya']) ?></td>
              <td class="text-center">
                <?php
                  switch ($pg['status']) {
                    case 'diajukan':
                      echo '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Menunggu Approve</span>';
                      break;
                    case 'selesai':
                      echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Selesai (Stok Masuk)</span>';
                      break;
                    case 'batal':
                      echo '<span class="badge bg-danger">Batal</span>';
                      break;
                    default:
                      echo '<span class="badge bg-secondary">' . htmlspecialchars($pg['status']) . '</span>';
                  }
                ?>
              </td>
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                  <!-- Tombol Lihat Dokumen Order Penjualan -->
                  <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/detail.php?id=<?= $pg['id_pengadaan'] ?>"
                     class="btn btn-outline-primary btn-sm" title="Lihat Dokumen Order Penjualan BULOG">
                    <i class="bi bi-file-earmark-text me-1"></i> Dokumen
                  </a>

                  <?php if ($pg['status'] !== 'selesai' && $pg['status'] !== 'batal' && is_pemilik()): ?>
                  <!-- Tombol Approve Cepat langsung dari daftar -->
                  <form method="POST" action="<?= BASE_URL ?>/modules/pengadaan_sphp/detail.php?id=<?= $pg['id_pengadaan'] ?>"
                        onsubmit="return confirm('APPROVE ORDER PENJUALAN SPHP\n\nSetujui penerimaan <?= number_format($pg['jumlah_sak']) ?> Sak (<?= number_format($pg['total_berat_kg'], 0) ?> Kg)?\n\nStok toko akan OTOMATIS BERTAMBAH.');">
                    <input type="hidden" name="action" value="approve_pengadaan">
                    <button type="submit" class="btn btn-success btn-sm" title="Approve & Tambah Stok">
                      <i class="bi bi-check-circle-fill me-1"></i> Approve
                    </button>
                  </form>
                  <?php endif; ?>
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
