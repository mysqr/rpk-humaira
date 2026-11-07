<?php
// modules/stok/mutasi.php - Kartu Mutasi Stok Masuk & Keluar Beras
$page_title = 'Kartu Mutasi Stok Beras';
$active_menu = 'stok';
require_once __DIR__ . '/../../config/auth.php';
check_login();

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$id_produk_filter = (int)($_GET['id_produk'] ?? 0);
$jenis_filter = $_GET['jenis'] ?? '';
$sumber_filter = $_GET['sumber'] ?? '';

$where = "WHERE DATE(r.created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
if ($id_produk_filter > 0) {
    $where .= " AND r.id_produk = $id_produk_filter";
}
if (!empty($jenis_filter)) {
    $where .= " AND r.jenis_transaksi = '" . escape($jenis_filter) . "'";
}
if (!empty($sumber_filter)) {
    $where .= " AND r.sumber = '" . escape($sumber_filter) . "'";
}

$sql = "SELECT r.*, p.nama_produk, p.satuan, p.is_eceran, k.kode_kategori, k.nama_kategori, u.nama_lengkap as nama_user
        FROM riwayat_stok r
        JOIN produk p ON r.id_produk = p.id_produk
        JOIN kategori_beras k ON p.id_kategori = k.id_kategori
        LEFT JOIN users u ON r.id_user = u.id_user
        $where
        ORDER BY r.id_riwayat DESC";

$mutasi_list = query($sql);
$produk_options = query("SELECT id_produk, nama_produk, satuan, kode_produk FROM produk ORDER BY id_kategori, nama_produk");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Kartu Mutasi Stok Beras (Audit Log)</h5>
    <p class="text-muted small mb-0">Lacak setiap pergerakan stok: penerimaan pengadaan BULOG, hasil giling sendiri, penjualan kasir, dan penyesuaian.</p>
  </div>
  <a href="<?= BASE_URL ?>/modules/stok/index.php" class="btn btn-outline-secondary px-3">
    <i class="bi bi-arrow-left me-1"></i> Kembali ke Stok
  </a>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label small mb-1 fw-semibold">Dari Tanggal</label>
        <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1 fw-semibold">Sampai Tanggal</label>
        <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1 fw-semibold">Filter Produk Beras</label>
        <select name="id_produk" class="form-select form-select-sm">
          <option value="">-- Semua Produk Beras --</option>
          <?php foreach ($produk_options as $po): ?>
            <option value="<?= $po['id_produk'] ?>" <?= $id_produk_filter == $po['id_produk'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($po['nama_produk']) ?> (<?= $po['satuan'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1 fw-semibold">Arah Mutasi</label>
        <select name="jenis" class="form-select form-select-sm">
          <option value="">-- Semua --</option>
          <option value="MASUK" <?= $jenis_filter === 'MASUK' ? 'selected' : '' ?>>MASUK (+)</option>
          <option value="KELUAR" <?= $jenis_filter === 'KELUAR' ? 'selected' : '' ?>>KELUAR (-)</option>
          <option value="PENYESUAIAN" <?= $jenis_filter === 'PENYESUAIAN' ? 'selected' : '' ?>>PENYESUAIAN</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-secondary btn-sm flex-fill"><i class="bi bi-filter"></i> Terapkan</button>
        <a href="<?= BASE_URL ?>/modules/stok/mutasi.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Mutasi Stok -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th>Waktu Mutasi</th>
          <th>Kode Ref</th>
          <th>Produk Beras</th>
          <th class="text-center">Arah & Sumber</th>
          <th class="text-end">Jumlah</th>
          <th class="text-center">Sebelum &rarr; Sesudah</th>
          <th>Keterangan</th>
          <th>Petugas</th>
          <?php if (is_pemilik()): ?>
          <th class="text-center text-nowrap no-print" style="width: 70px;">Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($mutasi_list)): ?>
          <tr><td colspan="<?= is_pemilik() ? 9 : 8 ?>" class="text-center py-5 text-muted">Belum ada riwayat mutasi stok pada filter ini.</td></tr>
        <?php else: ?>
          <?php foreach ($mutasi_list as $m): ?>
            <tr>
              <td class="small">
                <?= date('d/m/Y', strtotime($m['created_at'])) ?><br>
                <span class="text-muted"><?= date('H:i', strtotime($m['created_at'])) ?> WIB</span>
              </td>
              <td>
                <span class="fw-bold small text-dark"><?= htmlspecialchars($m['referensi_kode']) ?></span>
              </td>
              <td>
                <div class="fw-semibold text-dark small"><?= htmlspecialchars($m['nama_produk']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($m['satuan']) ?></small>
              </td>
              <td class="text-center">
                <?php if ($m['jenis_transaksi'] === 'MASUK'): ?>
                  <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">MASUK</span>
                <?php elseif ($m['jenis_transaksi'] === 'KELUAR'): ?>
                  <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1">KELUAR</span>
                <?php else: ?>
                  <span class="badge bg-warning-subtle text-warning fw-bold px-2 py-1">KOREKSI</span>
                <?php endif; ?>
                <small class="d-block text-muted" style="font-size: 0.7rem;"><?= str_replace('_', ' ', $m['sumber']) ?></small>
              </td>
              <td class="text-end fw-bold fs-6 <?= $m['jenis_transaksi'] === 'MASUK' ? 'text-success' : 'text-danger' ?>">
                <?= $m['jenis_transaksi'] === 'MASUK' ? '+' : '-' ?><?= number_format($m['jumlah'], ($m['is_eceran'] ? 1 : 0)) ?>
              </td>
              <td class="text-center small">
                <span class="text-muted"><?= number_format($m['stok_sebelum'], ($m['is_eceran'] ? 1 : 0)) ?></span>
                <i class="bi bi-arrow-right text-muted mx-1"></i>
                <span class="fw-bold text-dark"><?= number_format($m['stok_sesudah'], ($m['is_eceran'] ? 1 : 0)) ?></span>
              </td>
              <td class="small text-muted"><?= htmlspecialchars($m['keterangan'] ?? '-') ?></td>
              <td class="small text-muted"><?= htmlspecialchars($m['nama_user'] ?? 'Sistem') ?></td>
              <?php if (is_pemilik()): ?>
              <td class="text-center py-2 no-print">
                <button type="button" 
                        class="btn btn-sm btn-outline-danger px-2 py-1" 
                        title="Hapus riwayat & kembalikan saldo stok"
                        onclick="bukaModalHapusRiwayat(
                          <?= (int)$m['id_riwayat'] ?>,
                          '<?= htmlspecialchars(addslashes($m['referensi_kode'])) ?>',
                          '<?= htmlspecialchars(addslashes($m['nama_produk'])) ?>',
                          '<?= $m['jenis_transaksi'] ?>',
                          '<?= number_format($m['jumlah'], ($m['is_eceran'] ? 1 : 0)) ?>',
                          '<?= htmlspecialchars(addslashes($m['satuan'])) ?>',
                          '<?= number_format($m['stok_sebelum'], ($m['is_eceran'] ? 1 : 0)) ?>',
                          '<?= number_format($m['stok_sesudah'], ($m['is_eceran'] ? 1 : 0)) ?>'
                        )">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (is_pemilik()): ?>
<!-- Modal Konfirmasi Hapus Riwayat Mutasi Stok -->
<div class="modal fade" id="modalHapusRiwayat" tabindex="-1" aria-labelledby="modalHapusRiwayatLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-danger text-white border-0 py-3">
        <h6 class="modal-title fw-bold" id="modalHapusRiwayatLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Riwayat Mutasi Stok
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/modules/stok/hapus_riwayat.php" id="formHapusRiwayat">
        <input type="hidden" name="id" id="hapusIdRiwayat" value="">
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        <div class="modal-body p-4">
          <p class="mb-3 text-secondary">Apakah Anda yakin ingin membatalkan/menghapus riwayat transaksi pergerakan stok ini?</p>
          <div class="card bg-light border-0 rounded-3 p-3 mb-3">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small">Kode Referensi:</span>
              <span class="fw-bold font-monospace text-dark" id="modalHapusRef">-</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small">Produk Beras:</span>
              <span class="fw-semibold text-dark" id="modalHapusProduk">-</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small">Arah & Jumlah:</span>
              <span class="fw-bold text-dark" id="modalHapusJumlah">-</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted small">Saldo Sebelum &rarr; Sesudah:</span>
              <span class="fw-bold text-secondary" id="modalHapusSaldo">-</span>
            </div>
          </div>
          <div class="alert alert-warning py-2 px-3 small d-flex align-items-start gap-2 mb-0">
            <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0 mt-n1"></i>
            <div>
              <strong>Pengembalian Saldo Otomatis:</strong><br>
              Saldo stok produk di gudang/toko akan <strong>otomatis dikembalikan ke kondisi sebelum transaksi tersebut dilakukan</strong>.
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light py-2 px-4">
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold shadow-sm">
            <i class="bi bi-trash me-1"></i> Ya, Hapus & Kembalikan Saldo
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function bukaModalHapusRiwayat(id, ref, produk, jenis, jml, satuan, sebelum, sesudah) {
  document.getElementById('hapusIdRiwayat').value = id;
  document.getElementById('modalHapusRef').textContent = ref;
  document.getElementById('modalHapusProduk').textContent = produk;
  
  const tanda = jenis === 'MASUK' ? '+' : (jenis === 'KELUAR' ? '-' : '');
  const badgeColor = jenis === 'MASUK' ? 'text-success' : 'text-danger';
  const jmlEl = document.getElementById('modalHapusJumlah');
  jmlEl.className = 'fw-bold ' + badgeColor;
  jmlEl.textContent = jenis + ' (' + tanda + jml + ' ' + satuan + ')';
  
  document.getElementById('modalHapusSaldo').textContent = sebelum + ' ' + satuan + ' → ' + sesudah + ' ' + satuan;

  const modalEl = document.getElementById('modalHapusRiwayat');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
