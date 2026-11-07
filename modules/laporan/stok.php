<?php
// modules/laporan/stok.php - Laporan Pergerakan Stok (Masuk & Keluar)
$page_title = 'Laporan Pergerakan Stok Beras';
$active_menu = 'lap_stok';
require_once __DIR__ . '/../../config/auth.php';
check_login();

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$kategori_filter = $_GET['kategori'] ?? '';
$jenis_filter = $_GET['jenis'] ?? '';

$where = "WHERE DATE(r.created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
if (!empty($kategori_filter)) {
    $where .= " AND k.kode_kategori = '" . escape($kategori_filter) . "'";
}
if (!empty($jenis_filter)) {
    $where .= " AND r.jenis_transaksi = '" . escape($jenis_filter) . "'";
}

$sql = "SELECT r.*, p.nama_produk, p.satuan, p.is_eceran, k.kode_kategori, k.nama_kategori, u.nama_lengkap as nama_petugas
        FROM riwayat_stok r
        JOIN produk p ON r.id_produk = p.id_produk
        JOIN kategori_beras k ON p.id_kategori = k.id_kategori
        LEFT JOIN users u ON r.id_user = u.id_user
        $where
        ORDER BY r.created_at DESC, r.id_riwayat DESC";

$pergerakan_list = query($sql);

$total_masuk_sak = 0;
$total_masuk_liter = 0;
$total_keluar_sak = 0;
$total_keluar_liter = 0;

foreach ($pergerakan_list as $row) {
    if ($row['jenis_transaksi'] === 'MASUK') {
        if ($row['is_eceran'] == 1) $total_masuk_liter += (float)$row['jumlah'];
        else $total_masuk_sak += (float)$row['jumlah'];
    } elseif ($row['jenis_transaksi'] === 'KELUAR') {
        if ($row['is_eceran'] == 1) $total_keluar_liter += (float)$row['jumlah'];
        else $total_keluar_sak += (float)$row['jumlah'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
  <div>
    <h5 class="fw-bold mb-1">Laporan Pergerakan Stok Beras (Masuk & Keluar)</h5>
    <p class="text-muted small mb-0">Audit riwayat mutasi stok: pengadaan SPHP BULOG, giling mandiri medium/premium, dan transaksi penjualan.</p>
  </div>
  <button type="button" class="btn btn-outline-secondary shadow-sm" onclick="window.print()">
    <i class="bi bi-printer me-1"></i> Cetak Laporan
  </button>
</div>

<!-- Kop Surat Cetak Print (Hanya Muncul Saat Cetak) -->
<div class="d-none d-print-block print-header text-center mb-3">
  <h4><?= strtoupper(htmlspecialchars($toko_info['nama_toko'])) ?></h4>
  <div class="print-sub"><?= htmlspecialchars($toko_info['mitra']) ?> | <?= htmlspecialchars($toko_info['alamat']) ?> | Telp: <?= htmlspecialchars($toko_info['telepon']) ?></div>
  <div class="print-doc-title">LAPORAN MUTASI & PERGERAKAN STOK BERAS</div>
  <div class="print-period">
    Periode: <?= tgl_indo($tgl_mulai) ?> s/d <?= tgl_indo($tgl_selesai) ?>
    <?= !empty($kategori_filter) ? " | Kategori: $kategori_filter" : ' | Semua Kategori' ?>
    <?= !empty($jenis_filter) ? " | Arah: " . ($jenis_filter === 'MASUK' ? 'Stok Masuk (+)' : 'Stok Keluar (-)') : '' ?>
  </div>
</div>

<!-- Ringkasan Mutasi Khusus Cetak (Hanya Muncul Saat Print) -->
<div class="d-none d-print-block mb-3">
  <table style="width: 100%; border: 1.5px solid #000; border-collapse: collapse; font-size: 8.5pt;">
    <tr>
      <td style="width: 50%; padding: 5px 8px; border-right: 1.5px solid #000; background: #fafafa;">
        <strong>TOTAL STOK MASUK (+):</strong>&nbsp;
        <span style="font-weight: 800; font-size: 9.5pt; color: #000;">+<?= number_format($total_masuk_sak) ?> Sak</span> &nbsp;|&nbsp;
        <span style="font-weight: 800; font-size: 9.5pt; color: #000;">+<?= number_format($total_masuk_liter, 1) ?> Liter</span>
        <div style="font-size: 7.5pt; color: #333;">Sumber: Pengadaan SPHP BULOG & Produksi Giling Sendiri</div>
      </td>
      <td style="width: 50%; padding: 5px 8px; background: #fafafa;">
        <strong>TOTAL STOK KELUAR (-):</strong>&nbsp;
        <span style="font-weight: 800; font-size: 9.5pt; color: #000;">-<?= number_format($total_keluar_sak) ?> Sak</span> &nbsp;|&nbsp;
        <span style="font-weight: 800; font-size: 9.5pt; color: #000;">-<?= number_format($total_keluar_liter, 1) ?> Liter</span>
        <div style="font-size: 7.5pt; color: #333;">Sumber: Transaksi Penjualan Kasir</div>
      </td>
    </tr>
  </table>
</div>

<!-- Summary Cards Tampilan Layar (Sembunyi Saat Cetak) -->
<div class="row g-3 mb-4 no-print">
  <div class="col-md-6">
    <div class="stat-card border-start border-4 border-success">
      <div class="text-dark small fw-semibold">Total Stok Masuk (+)</div>
      <div class="d-flex justify-content-between align-items-baseline mt-1">
        <h4 class="fw-bold text-success mb-0">+<?= number_format($total_masuk_sak) ?> <span class="fs-6 text-dark fw-semibold">Sak</span></h4>
        <span class="fw-bold text-success">+<?= number_format($total_masuk_liter, 1) ?> <span class="text-dark">Liter</span></span>
      </div>
      <small class="text-dark" style="font-size: 0.75rem;">Dari Pengadaan BULOG & Produksi Giling</small>
    </div>
  </div>

  <div class="col-md-6">
    <div class="stat-card border-start border-4 border-danger">
      <div class="text-dark small fw-semibold">Total Stok Keluar (-)</div>
      <div class="d-flex justify-content-between align-items-baseline mt-1">
        <h4 class="fw-bold text-danger mb-0">-<?= number_format($total_keluar_sak) ?> <span class="fs-6 text-dark fw-semibold">Sak</span></h4>
        <span class="fw-bold text-danger">-<?= number_format($total_keluar_liter, 1) ?> <span class="text-dark">Liter</span></span>
      </div>
      <small class="text-dark" style="font-size: 0.75rem;">Dari Transaksi Penjualan Kasir</small>
    </div>
  </div>
</div>

<!-- Filter Box -->
<div class="card border-0 shadow-sm rounded-3 mb-4 no-print">
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
        <label class="form-label small mb-1 fw-semibold">Kategori Beras</label>
        <select name="kategori" class="form-select form-select-sm">
          <option value="">-- Semua Kategori --</option>
          <option value="SPHP" <?= $kategori_filter === 'SPHP' ? 'selected' : '' ?>>SPHP (BULOG)</option>
          <option value="MEDIUM" <?= $kategori_filter === 'MEDIUM' ? 'selected' : '' ?>>Medium</option>
          <option value="PREMIUM" <?= $kategori_filter === 'PREMIUM' ? 'selected' : '' ?>>Premium</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1 fw-semibold">Arah Pergerakan</label>
        <select name="jenis" class="form-select form-select-sm">
          <option value="">-- Semua Arah --</option>
          <option value="MASUK" <?= $jenis_filter === 'MASUK' ? 'selected' : '' ?>>Masuk (+)</option>
          <option value="KELUAR" <?= $jenis_filter === 'KELUAR' ? 'selected' : '' ?>>Keluar (-)</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-secondary btn-sm flex-fill"><i class="bi bi-filter"></i> Filter</button>
        <a href="<?= BASE_URL ?>/modules/laporan/stok.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Laporan Pergerakan -->
<div class="card border shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-custom mb-0 align-middle">
      <thead class="table-light">
        <tr class="text-center align-middle">
          <th style="width: 11%;" class="text-nowrap">Tanggal & Jam</th>
          <th style="width: 14%;" class="text-nowrap">Kode Referensi</th>
          <th style="width: 9%;" class="text-nowrap">Kategori</th>
          <th style="width: 18%;" class="text-start">Produk Beras</th>
          <th style="width: 12%;" class="text-nowrap">Arah Pergerakan</th>
          <th style="width: 9%;" class="text-end text-nowrap">Jumlah</th>
          <th style="width: 11%;" class="text-nowrap">Sebelum &rarr; Sesudah</th>
          <th style="width: 16%;" class="text-start">Keterangan Transaksi</th>
          <?php if (is_pemilik()): ?>
          <th style="width: 70px;" class="text-center text-nowrap no-print">Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pergerakan_list)): ?>
          <tr><td colspan="<?= is_pemilik() ? 9 : 8 ?>" class="text-center py-5 text-dark fw-semibold">Tidak ada pergerakan stok pada periode ini.</td></tr>
        <?php else: ?>
          <?php foreach ($pergerakan_list as $row): ?>
            <tr>
              <td class="text-center text-nowrap small py-2">
                <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($row['created_at'])) ?></div>
                <div class="text-dark fw-medium" style="font-size: 0.75rem;"><?= date('H:i', strtotime($row['created_at'])) ?> WIB</div>
              </td>
              <td class="text-nowrap py-2">
                <span class="font-monospace fw-bold small text-dark px-2 py-1 bg-light border rounded d-inline-block"><?= htmlspecialchars($row['referensi_kode']) ?></span>
              </td>
              <td class="text-center text-nowrap py-2">
                <span class="badge <?= $row['kode_kategori'] === 'SPHP' ? 'badge-sphp' : ($row['kode_kategori'] === 'PREMIUM' ? 'badge-premium' : 'badge-medium') ?> px-2 py-1">
                  <?= $row['kode_kategori'] ?>
                </span>
              </td>
              <td class="py-2">
                <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($row['nama_produk']) ?></div>
                <div class="text-dark small fw-medium" style="font-size: 0.75rem;">Kemasan: <?= htmlspecialchars($row['satuan']) ?></div>
              </td>
              <td class="text-center text-nowrap py-2">
                <?php if ($row['jenis_transaksi'] === 'MASUK'): ?>
                  <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-1">MASUK (+)</span>
                  <div class="text-dark fw-bold mt-1" style="font-size: 0.7rem; letter-spacing: 0.3px;"><?= strtoupper(str_replace('_', ' ', $row['sumber'])) ?></div>
                <?php elseif ($row['jenis_transaksi'] === 'KELUAR'): ?>
                  <span class="badge bg-danger-subtle text-danger border border-danger fw-bold px-2 py-1">KELUAR (-)</span>
                  <div class="text-dark fw-bold mt-1" style="font-size: 0.7rem; letter-spacing: 0.3px;">PENJUALAN</div>
                <?php else: ?>
                  <span class="badge bg-warning-subtle text-warning border border-warning fw-bold px-2 py-1">KOREKSI</span>
                <?php endif; ?>
              </td>
              <td class="text-end text-nowrap py-2">
                <div class="fw-bold fs-6 <?= $row['jenis_transaksi'] === 'MASUK' ? 'text-success' : 'text-danger' ?>">
                  <?= $row['jenis_transaksi'] === 'MASUK' ? '+' : '-' ?><?= number_format($row['jumlah'], ($row['is_eceran'] ? 1 : 0)) ?>
                </div>
                <div class="text-dark fw-semibold" style="font-size: 0.72rem;"><?= htmlspecialchars($row['satuan']) ?></div>
              </td>
              <td class="text-center text-nowrap py-2">
                <span class="fw-semibold text-dark"><?= number_format($row['stok_sebelum'], ($row['is_eceran'] ? 1 : 0)) ?></span>
                <span class="text-dark fw-bold mx-1">&rarr;</span>
                <span class="fw-bold text-dark"><?= number_format($row['stok_sesudah'], ($row['is_eceran'] ? 1 : 0)) ?></span>
              </td>
              <td class="small text-dark py-2" style="line-height: 1.35; min-width: 170px;">
                <?= nl2br(htmlspecialchars($row['keterangan'] ?? '-')) ?>
              </td>
              <?php if (is_pemilik()): ?>
              <td class="text-center py-2 no-print">
                <button type="button" 
                        class="btn btn-sm btn-outline-danger px-2 py-1" 
                        title="Hapus riwayat & kembalikan saldo stok"
                        onclick="bukaModalHapusRiwayat(
                          <?= (int)$row['id_riwayat'] ?>,
                          '<?= htmlspecialchars(addslashes($row['referensi_kode'])) ?>',
                          '<?= htmlspecialchars(addslashes($row['nama_produk'])) ?>',
                          '<?= $row['jenis_transaksi'] ?>',
                          '<?= number_format($row['jumlah'], ($row['is_eceran'] ? 1 : 0)) ?>',
                          '<?= htmlspecialchars(addslashes($row['satuan'])) ?>',
                          '<?= number_format($row['stok_sebelum'], ($row['is_eceran'] ? 1 : 0)) ?>',
                          '<?= number_format($row['stok_sesudah'], ($row['is_eceran'] ? 1 : 0)) ?>'
                        )">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($pergerakan_list)): ?>
        <tfoot class="table-light align-middle fw-bold">
          <tr>
            <td colspan="5" class="text-end py-2 text-dark">TOTAL PERGERAKAN:</td>
            <td class="text-end text-nowrap py-2">
              <div class="text-success">+<?= number_format($total_masuk_sak) ?> Sak</div>
              <div class="text-danger">-<?= number_format($total_keluar_sak) ?> Sak</div>
            </td>
            <td colspan="2" class="py-2 text-dark small">
              <?php if ($total_masuk_liter > 0 || $total_keluar_liter > 0): ?>
                <span class="badge bg-light text-dark border">
                  Eceran: <span class="text-success">+<?= number_format($total_masuk_liter, 1) ?> L</span> | <span class="text-danger">-<?= number_format($total_keluar_liter, 1) ?> L</span>
                </span>
              <?php endif; ?>
            </td>
            <?php if (is_pemilik()): ?>
            <td class="no-print"></td>
            <?php endif; ?>
          </tr>
        </tfoot>
      <?php endif; ?>
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
