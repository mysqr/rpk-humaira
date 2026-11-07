<?php
// modules/laporan/penjualan.php - Laporan Penjualan Harian, Bulanan, dan Filter Jenis Beras
$page_title = 'Laporan Penjualan Beras';
$active_menu = 'lap_penjualan';
require_once __DIR__ . '/../../config/auth.php';
check_login();

$mode = $_GET['mode'] ?? 'harian'; // harian atau bulanan
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');
$kategori_filter = $_GET['kategori'] ?? '';

// Kondisi query
$where = "WHERE 1=1";
if ($mode === 'harian') {
    $where .= " AND DATE(p.tanggal_penjualan) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
} else {
    $where .= " AND MONTH(p.tanggal_penjualan) = '$bulan' AND YEAR(p.tanggal_penjualan) = '$tahun'";
}

if (!empty($kategori_filter)) {
    $where .= " AND kb.kode_kategori = '" . escape($kategori_filter) . "'";
}

$sql = "SELECT pd.*, p.no_faktur, p.tanggal_penjualan, p.nama_pembeli, p.nik_pembeli, p.no_hp_pembeli, p.ada_sphp,
               pr.nama_produk, pr.satuan, pr.is_eceran, kb.kode_kategori, kb.nama_kategori, u.nama_lengkap as kasir
        FROM penjualan_detail pd
        JOIN penjualan p ON pd.id_penjualan = p.id_penjualan
        JOIN produk pr ON pd.id_produk = pr.id_produk
        JOIN kategori_beras kb ON pr.id_kategori = kb.id_kategori
        LEFT JOIN users u ON p.id_user = u.id_user
        $where
        ORDER BY p.tanggal_penjualan DESC, pd.id_detail DESC";

$laporan_data = query($sql);

$total_pendapatan = 0;
$total_qty_sak = 0;
$total_qty_liter = 0;
$total_laba = 0;
$total_laba_eceran = 0;
$total_laba_kemasan = 0;
$ada_laba_minus = false;

foreach ($laporan_data as $row) {
    $total_pendapatan += (float)$row['subtotal'];
    $laba_row = (float)$row['laba'];
    $total_laba += $laba_row;
    if ((float)$row['laba'] < 0) $ada_laba_minus = true;
    if ($row['is_eceran'] == 1) {
        $total_qty_liter += (float)$row['jumlah'];
        $total_laba_eceran += $laba_row;
    } else {
        $total_qty_sak += (float)$row['jumlah'];
        $total_laba_kemasan += $laba_row;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
  <div>
    <h5 class="fw-bold mb-1">Laporan Penjualan Beras</h5>
    <p class="text-muted small mb-0">Rekapitulasi penjualan harian & bulanan yang dapat dipisah per kategori beras (SPHP, Medium, Premium).</p>
  </div>
  <button type="button" class="btn btn-outline-secondary shadow-sm" onclick="window.print()">
    <i class="bi bi-printer me-1"></i> Cetak Laporan
  </button>
</div>

<!-- Filter Mode & Parameter -->
<div class="card border-0 shadow-sm rounded-3 mb-4 no-print">
  <div class="card-body p-3">
    <form method="GET" action="" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label small mb-1 fw-semibold">Jenis Periode</label>
        <select name="mode" class="form-select form-select-sm" onchange="toggleMode(this.value)">
          <option value="harian" <?= $mode === 'harian' ? 'selected' : '' ?>>Rentang Tanggal</option>
          <option value="bulanan" <?= $mode === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
        </select>
      </div>

      <div class="col-md-2 mode-harian <?= $mode === 'bulanan' ? 'd-none' : '' ?>">
        <label class="form-label small mb-1 fw-semibold">Dari Tanggal</label>
        <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai) ?>">
      </div>
      <div class="col-md-2 mode-harian <?= $mode === 'bulanan' ? 'd-none' : '' ?>">
        <label class="form-label small mb-1 fw-semibold">Sampai Tanggal</label>
        <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_selesai) ?>">
      </div>

      <div class="col-md-2 mode-bulanan <?= $mode === 'harian' ? 'd-none' : '' ?>">
        <label class="form-label small mb-1 fw-semibold">Pilih Bulan</label>
        <select name="bulan" class="form-select form-select-sm">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= (int)$bulan === $m ? 'selected' : '' ?>>
              <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2 mode-bulanan <?= $mode === 'harian' ? 'd-none' : '' ?>">
        <label class="form-label small mb-1 fw-semibold">Tahun</label>
        <input type="number" name="tahun" class="form-control form-control-sm" value="<?= htmlspecialchars($tahun) ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label small mb-1 fw-semibold">Kategori Beras</label>
        <select name="kategori" class="form-select form-select-sm">
          <option value="">-- Semua Kategori Beras --</option>
          <option value="SPHP" <?= $kategori_filter === 'SPHP' ? 'selected' : '' ?>>Beras SPHP (BULOG)</option>
          <option value="MEDIUM" <?= $kategori_filter === 'MEDIUM' ? 'selected' : '' ?>>Beras Medium (Giling Sendiri)</option>
          <option value="PREMIUM" <?= $kategori_filter === 'PREMIUM' ? 'selected' : '' ?>>Beras Premium (Giling Sendiri)</option>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-filter"></i> Tampilkan</button>
        <a href="<?= BASE_URL ?>/modules/laporan/penjualan.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Kop Surat Cetak Print (Hanya Muncul Saat Print) -->
<div class="d-none d-print-block print-header">
  <h4><?= strtoupper(htmlspecialchars($toko_info['nama_toko'])) ?></h4>
  <div class="print-sub"><?= htmlspecialchars($toko_info['mitra']) ?> | <?= htmlspecialchars($toko_info['alamat']) ?> | Telp: <?= htmlspecialchars($toko_info['telepon']) ?></div>
  <div class="print-doc-title">LAPORAN PENJUALAN BERAS</div>
  <div class="print-period">
    Periode: <?= $mode === 'harian' ? tgl_indo($tgl_mulai) . ' s/d ' . tgl_indo($tgl_selesai) : "Bulan " . date('F', mktime(0, 0, 0, (int)$bulan, 10)) . " Tahun $tahun" ?>
    <?= $kategori_filter ? " | Kategori: $kategori_filter" : ' | Semua Kategori' ?>
  </div>
</div>

<!-- Alert Peringatan Laba Minus (hanya tampil jika ada) -->
<?php if ($ada_laba_minus && is_pemilik()): ?>
<div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 no-print d-flex gap-3 align-items-center">
  <i class="bi bi-exclamation-octagon-fill fs-4 text-danger"></i>
  <div>
    <strong>Perhatian:</strong> Terdapat transaksi dengan laba minus pada periode yang dipilih. Pastikan harga beli (HPP) pada master produk sudah sesuai dengan harga modal.
  </div>
</div>
<?php endif; ?>

<!-- Kartu Ringkasan Omzet & Volume -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Nilai Penjualan</div>
      <h4 class="fw-bold text-primary mt-1 mb-0"><?= rupiah($total_pendapatan) ?></h4>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Beras Kemasan Terjual</div>
      <h4 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_qty_sak, 0) ?> <span class="fs-6 text-dark fw-semibold">Sak Kemasan</span></h4>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Beras Eceran Terjual</div>
      <h4 class="fw-bold text-warning mt-1 mb-0"><?= number_format($total_qty_liter, 1) ?> <span class="fs-6 text-dark fw-semibold">Liter</span></h4>
    </div>
  </div>
  <?php if (is_pemilik()): ?>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="text-dark small fw-semibold">Total Laba Bersih</div>
      <h4 class="fw-bold mt-1 mb-0 <?= $total_laba < 0 ? 'text-danger' : 'text-success' ?>">
        <?= rupiah($total_laba) ?>
      </h4>
      <?php if (!empty($laporan_data)): ?>
      <small class="text-muted d-block mt-1">
        Kemasan: <span class="<?= $total_laba_kemasan < 0 ? 'text-danger' : 'text-success' ?> fw-semibold"><?= rupiah($total_laba_kemasan) ?></span>
        &bull;
        Eceran: <span class="<?= $total_laba_eceran < 0 ? 'text-danger fw-bold' : 'text-success' ?> fw-semibold"><?= rupiah($total_laba_eceran) ?></span>
      </small>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Tabel Laporan -->
<div class="card border shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th>No</th>
          <th>Tanggal & Faktur</th>
          <th>Kategori</th>
          <th>Nama Produk Beras</th>
          <th>Pembeli / NIK / HP</th>
          <th class="text-center">Kuantiti</th>
          <th class="text-end">Harga Satuan</th>
          <th class="text-end">Subtotal</th>
          <?php if (is_pemilik()): ?>
          <th class="text-end">Laba</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($laporan_data)): ?>
          <tr><td colspan="9" class="text-center py-5 text-muted">Tidak ada data penjualan pada periode dan kategori yang dipilih.</td></tr>
        <?php else: ?>
          <?php $no = 1; foreach ($laporan_data as $row): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td>
                <span class="fw-bold text-dark"><?= htmlspecialchars($row['no_faktur']) ?></span><br>
                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($row['tanggal_penjualan'])) ?></small>
              </td>
              <td>
                <?php if ($row['kode_kategori'] === 'SPHP'): ?>
                  <span class="badge badge-sphp">SPHP</span>
                <?php elseif ($row['kode_kategori'] === 'MEDIUM'): ?>
                  <span class="badge badge-medium">Medium</span>
                <?php else: ?>
                  <span class="badge badge-premium">Premium</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_produk']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($row['satuan']) ?></small>
              </td>
              <td>
                <div class="small fw-semibold"><?= htmlspecialchars($row['nama_pembeli'] ?: 'Umum') ?></div>
                <?php if (!empty($row['nik_pembeli'])): ?>
                  <div class="badge bg-light text-secondary border font-monospace mt-1" style="font-size: 0.7rem;">
                    <i class="bi bi-card-text me-1"></i><?= htmlspecialchars($row['nik_pembeli']) ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($row['no_hp_pembeli'])): ?>
                  <div><small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($row['no_hp_pembeli']) ?></small></div>
                <?php endif; ?>
              </td>
              <td class="text-center fw-bold">
                <?= number_format($row['jumlah'], ($row['is_eceran'] ? 1 : 0)) ?>
                <?php if ($row['is_eceran']): ?>
                  <small class="d-block text-muted" style="font-size: 0.7rem;">≈ <?= number_format($row['jumlah'] * 0.753, 2) ?> Kg</small>
                <?php endif; ?>
              </td>
              <td class="text-end text-muted">
                <?= rupiah($row['harga_satuan']) ?>
              </td>
              <td class="text-end fw-bold text-dark">
                <?= rupiah($row['subtotal']) ?>
              </td>
              <?php if (is_pemilik()): ?>
              <td class="text-end fw-semibold <?= (float)$row['laba'] < 0 ? 'text-danger' : 'text-success' ?>">
                <?= rupiah($row['laba']) ?>
                <?php if ((float)$row['laba'] < 0): ?>
                  <i class="bi bi-exclamation-triangle-fill ms-1 text-danger" title="Laba minus: HPP lebih tinggi dari harga jual"></i>
                <?php endif; ?>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="fw-bold bg-light">
          <td colspan="5" class="text-end">TOTAL KESELURUHAN:</td>
          <td class="text-center"><?= number_format($total_qty_sak, 0) ?> Sak + <?= number_format($total_qty_liter, 1) ?> Ltr <small class="text-muted d-block" style="font-size: 0.7rem;">(≈ <?= number_format($total_qty_liter * 0.753, 2) ?> Kg)</small></td>
          <td></td>
          <td class="text-end text-primary fs-6"><?= rupiah($total_pendapatan) ?></td>
          <?php if (is_pemilik()): ?>
          <td class="text-end <?= $total_laba < 0 ? 'text-danger' : 'text-success' ?> fs-6">
            <?= rupiah($total_laba) ?>
            <?php if ($total_laba < 0): ?>
              <div class="small fw-normal text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Total Laba Minus</div>
            <?php endif; ?>
          </td>
          <?php endif; ?>
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

<script>
function toggleMode(val) {
  if (val === 'harian') {
    document.querySelectorAll('.mode-harian').forEach(el => el.classList.remove('d-none'));
    document.querySelectorAll('.mode-bulanan').forEach(el => el.classList.add('d-none'));
  } else {
    document.querySelectorAll('.mode-harian').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.mode-bulanan').forEach(el => el.classList.remove('d-none'));
  }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
