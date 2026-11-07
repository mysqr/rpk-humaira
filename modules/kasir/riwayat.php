<?php
// modules/kasir/riwayat.php - Riwayat Seluruh Transaksi Penjualan
$page_title = 'Riwayat Transaksi Penjualan';
$active_menu = 'riwayat_penjualan';
require_once __DIR__ . '/../../config/auth.php';
check_login();

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$filter_sphp = $_GET['sphp'] ?? '';
$search = escape($_GET['search'] ?? '');

$where = "WHERE DATE(p.tanggal_penjualan) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
if ($filter_sphp === '1') {
    $where .= " AND p.ada_sphp = 1";
} elseif ($filter_sphp === '0') {
    $where .= " AND p.ada_sphp = 0";
}

if (!empty($search)) {
    $where .= " AND (p.no_faktur LIKE '%$search%' OR p.nama_pembeli LIKE '%$search%' OR p.nik_pembeli LIKE '%$search%' OR p.no_hp_pembeli LIKE '%$search%')";
}

$sql = "SELECT p.*, u.nama_lengkap as nama_kasir 
        FROM penjualan p 
        LEFT JOIN users u ON p.id_user = u.id_user 
        $where 
        ORDER BY p.tanggal_penjualan DESC, p.id_penjualan DESC";

$transaksi_list = query($sql);

$total_omzet = 0;
$total_transaksi = count($transaksi_list);
foreach ($transaksi_list as $t) {
    $total_omzet += (float)$t['total_harga'];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Riwayat Transaksi Penjualan Kasir</h5>
    <p class="text-muted small mb-0">Daftar semua faktur penjualan, identitas pembeli SPHP, dan pencetakan ulang struk.</p>
  </div>
  <a href="<?= BASE_URL ?>/modules/kasir/index.php" class="btn btn-primary px-3 shadow-sm d-flex align-items-center gap-2">
    <i class="bi bi-cart4"></i>
    <span class="fw-semibold">Kasir POS Baru</span>
  </a>
</div>

<!-- Kartu Ringkasan -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="stat-card">
      <div class="text-muted small fw-semibold">Total Nilai Penjualan (Hasil Filter)</div>
      <h4 class="fw-bold text-primary mt-1 mb-0"><?= rupiah($total_omzet) ?></h4>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card">
      <div class="text-muted small fw-semibold">Jumlah Transaksi Faktur</div>
      <h4 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_transaksi) ?> <span class="fs-6 text-muted fw-normal">Faktur</span></h4>
    </div>
  </div>
</div>

<!-- Filter Box -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1 fw-semibold">Dari Tanggal</label>
        <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1 fw-semibold">Sampai Tanggal</label>
        <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1 fw-semibold">Kandungan SPHP</label>
        <select name="sphp" class="form-select form-select-sm">
          <option value="">-- Semua --</option>
          <option value="1" <?= $filter_sphp === '1' ? 'selected' : '' ?>>Mengandung SPHP</option>
          <option value="0" <?= $filter_sphp === '0' ? 'selected' : '' ?>>Hanya Med/Pre</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1 fw-semibold">Cari Faktur / NIK / Nama</label>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="No Faktur / NIK / HP" value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-secondary btn-sm flex-fill"><i class="bi bi-filter"></i> Filter</button>
        <a href="<?= BASE_URL ?>/modules/kasir/riwayat.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Riwayat Penjualan -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th>No. Faktur</th>
          <th>Tanggal & Jam</th>
          <th>Identitas Pembeli</th>
          <th>Status Pembeli</th>
          <th class="text-center">Jml Item</th>
          <th class="text-end">Total Bayar</th>
          <th>Kasir</th>
          <th class="text-center" style="width: <?= is_admin() ? '110px' : '75px' ?>;"><?= is_admin() ? 'Aksi' : 'Struk' ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transaksi_list)): ?>
          <tr><td colspan="8" class="text-center py-5 text-muted">Tidak ada transaksi ditemukan pada periode ini.</td></tr>
        <?php else: ?>
          <?php foreach ($transaksi_list as $t): ?>
            <tr>
              <td>
                <span class="fw-bold text-dark"><?= htmlspecialchars($t['no_faktur']) ?></span>
                <?php if ($t['ada_sphp']): ?>
                  <span class="badge badge-sphp d-block mt-1" style="font-size: 0.65rem;">BERAS SPHP</span>
                <?php endif; ?>
              </td>
              <td class="small">
                <?= date('d/m/Y', strtotime($t['tanggal_penjualan'])) ?><br>
                <span class="text-muted"><?= date('H:i:s', strtotime($t['tanggal_penjualan'])) ?> WIB</span>
              </td>
              <td>
                <div class="fw-semibold text-dark small"><?= htmlspecialchars($t['nama_pembeli'] ?: 'Pembeli Umum') ?></div>
                <?php if (!empty($t['nik_pembeli'])): ?>
                  <div class="badge bg-light text-secondary border font-monospace mt-1" style="font-size: 0.72rem;">
                    <i class="bi bi-card-text me-1"></i>NIK: <?= htmlspecialchars($t['nik_pembeli']) ?>
                  </div>
                <?php endif; ?>
                <?php if ($t['no_hp_pembeli']): ?>
                  <div class="small text-muted mt-1"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($t['no_hp_pembeli']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($t['ada_sphp']): ?>
                  <span class="badge bg-success-subtle text-success small">
                    <i class="bi bi-check-circle me-1"></i>Konsumen Akhir
                  </span>
                <?php else: ?>
                  <span class="text-muted small">Umum</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge bg-light text-dark border">
                  <?= (int)$t['total_item'] ?> jenis (<?= (float)$t['total_kuantiti'] ?> unit)
                </span>
              </td>
              <td class="text-end fw-bold text-primary">
                <?= rupiah($t['total_harga']) ?>
              </td>
              <td class="small text-muted">
                <?= htmlspecialchars($t['nama_kasir'] ?? 'Kasir') ?>
              </td>
              <td class="text-center">
                <div class="d-inline-flex align-items-center gap-1">
                  <a href="<?= BASE_URL ?>/modules/kasir/struk.php?id=<?= $t['id_penjualan'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak Nota">
                    <i class="bi bi-printer"></i>
                  </a>
                  <?php if (is_admin()): ?>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger" 
                            title="Hapus Transaksi"
                            onclick="bukaModalHapus(<?= (int)$t['id_penjualan'] ?>, '<?= htmlspecialchars(addslashes($t['no_faktur'])) ?>', '<?= rupiah($t['total_harga']) ?>', '<?= htmlspecialchars(addslashes($t['nama_pembeli'] ?: 'Pembeli Umum')) ?>')">
                      <i class="bi bi-trash"></i>
                    </button>
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

<?php if (is_admin()): ?>
<!-- Modal Konfirmasi Hapus Transaksi -->
<div class="modal fade" id="modalHapusTrx" tabindex="-1" aria-labelledby="modalHapusTrxLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-danger text-white border-0 py-3">
        <h6 class="modal-title fw-bold" id="modalHapusTrxLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Transaksi
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/modules/kasir/hapus.php" id="formHapusTrx">
        <input type="hidden" name="id" id="hapusIdPenjualan" value="">
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        <div class="modal-body p-4">
          <p class="mb-3 text-secondary">Apakah Anda yakin ingin menghapus data transaksi penjualan kasir ini?</p>
          <div class="card bg-light border-0 rounded-3 p-3 mb-3">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small">No. Faktur:</span>
              <span class="fw-bold font-monospace text-dark" id="hapusNoFaktur">-</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small">Pembeli:</span>
              <span class="fw-semibold text-dark" id="hapusPembeli">-</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted small">Total Pembayaran:</span>
              <span class="fw-bold text-danger fs-6" id="hapusTotalHarga">-</span>
            </div>
          </div>
          <div class="alert alert-warning py-2 px-3 small d-flex align-items-start gap-2 mb-0">
            <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0 mt-n1"></i>
            <div>
              <strong>Perhatian:</strong> Seluruh stok beras pada faktur ini akan <strong>otomatis dikembalikan ke gudang/toko</strong> dan tercatat rapi di kartu mutasi stok.
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light py-2 px-4">
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold shadow-sm">
            <i class="bi bi-trash me-1"></i> Ya, Hapus Transaksi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function bukaModalHapus(id, noFaktur, totalHarga, pembeli) {
  document.getElementById('hapusIdPenjualan').value = id;
  document.getElementById('hapusNoFaktur').textContent = noFaktur;
  document.getElementById('hapusPembeli').textContent = pembeli;
  document.getElementById('hapusTotalHarga').textContent = totalHarga;
  const modalEl = document.getElementById('modalHapusTrx');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
