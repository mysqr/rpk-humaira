<?php
// modules/laporan/kuota_sphp.php - Laporan Realisasi Penggunaan Kuota SPHP Mingguan vs Batas 2 Ton
$page_title = 'Laporan Realisasi Kuota SPHP Mingguan';
$active_menu = 'lap_kuota';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

$tahun_pilih = (int)($_GET['tahun'] ?? date('Y'));
$batas_mingguan_kg = (float)($toko_info['kuota_mingguan_sphp_kg'] ?? 2000.00);

// Query semua pengadaan SPHP yang tidak dibatalkan pada tahun yang dipilih
$sql = "SELECT p.*, 
        YEARWEEK(p.tanggal_order, 1) as minggu_ke,
        YEAR(p.tanggal_order) as thn
        FROM pengadaan_sphp p
        WHERE YEAR(p.tanggal_order) = $tahun_pilih AND p.status != 'batal'
        ORDER BY p.tanggal_order DESC";

$semua_pengadaan = query($sql);

// Kelompokkan per minggu (Senin - Minggu)
$rekap_mingguan = [];
foreach ($semua_pengadaan as $row) {
    $w_key = $row['minggu_ke'];
    if (!isset($rekap_mingguan[$w_key])) {
        // Hitung tanggal Senin dan Minggu untuk minggu_ke ini
        $thn = substr($w_key, 0, 4);
        $mgg = substr($w_key, 4, 2);
        $dto = new DateTime();
        $dto->setISODate($thn, $mgg);
        $senin = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $minggu = $dto->format('Y-m-d');

        $rekap_mingguan[$w_key] = [
            'minggu_ke' => $mgg,
            'tahun' => $thn,
            'periode_senin' => $senin,
            'periode_minggu' => $minggu,
            'jml_order' => 0,
            'total_sak' => 0,
            'total_kg' => 0,
            'total_biaya' => 0,
            'orders' => []
        ];
    }

    $rekap_mingguan[$w_key]['jml_order']++;
    $rekap_mingguan[$w_key]['total_sak'] += (int)$row['jumlah_sak'];
    $rekap_mingguan[$w_key]['total_kg'] += (float)$row['total_berat_kg'];
    $rekap_mingguan[$w_key]['total_biaya'] += (float)$row['total_biaya'];
    $rekap_mingguan[$w_key]['orders'][] = $row;
}

// Current week quota
$kuota_current = get_kuota_sphp_minggu_ini();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
  <div>
    <h5 class="fw-bold mb-1">Laporan Realisasi Kuota SPHP Mingguan (Evaluasi Batas 2 Ton)</h5>
    <p class="text-muted small mb-0">Pemantauan kepatuhan terhadap regulasi alokasi maksimal <strong>2.000 Kg (2 Ton / 400 Sak @ 5Kg)</strong> per pekan dari Perum BULOG.</p>
  </div>
  <button type="button" class="btn btn-outline-secondary shadow-sm" onclick="window.print()">
    <i class="bi bi-printer me-1"></i> Cetak Laporan Kuota
  </button>
</div>

<!-- Kop Surat Cetak Print -->
<div class="d-none d-print-block print-header">
  <h4><?= strtoupper(htmlspecialchars($toko_info['nama_toko'])) ?></h4>
  <div class="print-sub"><?= htmlspecialchars($toko_info['mitra']) ?> | <?= htmlspecialchars($toko_info['alamat']) ?> | Telp: <?= htmlspecialchars($toko_info['telepon']) ?></div>
  <div class="print-doc-title">LAPORAN REALISASI KUOTA PENGADAAN BERAS SPHP BULOG MINGGUAN</div>
  <div class="print-period">Batas Alokasi Resmi: 2.000 Kg (2,0 Ton / Pekan) | Tahun Evaluasi: <?= $tahun_pilih ?></div>
</div>

<!-- Banner Status Minggu Berjalan Ini -->
<div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-primary">
  <div class="card-body p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
      <div>
        <span class="badge bg-primary mb-1">MINGGU BERJALAN INI</span>
        <h5 class="fw-bold text-dark mb-0">
          Periode: <?= date('d M', strtotime($kuota_current['senin'])) ?> - <?= date('d M Y', strtotime($kuota_current['minggu'])) ?>
        </h5>
      </div>
      <div class="d-flex gap-4">
        <div>
          <span class="text-muted small d-block">Realisasi:</span>
          <span class="fw-bold fs-5 text-dark"><?= number_format($kuota_current['terpakai_kg'], 0) ?> Kg</span>
          <small class="text-muted">(<?= number_format($kuota_current['terpakai_kg']/5, 0) ?> Sak)</small>
        </div>
        <div>
          <span class="text-muted small d-block">Sisa Kuota:</span>
          <span class="fw-bold fs-5 <?= $kuota_current['sisa_kg'] < 200 ? 'text-danger' : 'text-success' ?>"><?= number_format($kuota_current['sisa_kg'], 0) ?> Kg</span>
          <small class="text-muted">(<?= number_format($kuota_current['sisa_kg']/5, 0) ?> Sak)</small>
        </div>
        <div>
          <span class="text-muted small d-block">Batas Alokasi:</span>
          <span class="fw-bold fs-5 text-muted"><?= number_format($batas_mingguan_kg, 0) ?> Kg</span>
          <small class="text-muted">(2.0 Ton)</small>
        </div>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress quota-progress" style="height: 18px;">
      <?php 
        $b_class = 'bg-primary';
        if ($kuota_current['persen'] >= 100) $b_class = 'bg-danger';
        elseif ($kuota_current['persen'] >= 75) $b_class = 'bg-warning text-dark';
      ?>
      <div class="progress-bar <?= $b_class ?> progress-bar-striped progress-bar-animated fw-bold" 
           style="width: <?= $kuota_current['persen'] ?>%;">
        <?= $kuota_current['persen'] ?>% Terpakai
      </div>
    </div>
  </div>
</div>

<!-- Filter Tahun -->
<div class="card border-0 shadow-sm rounded-3 mb-4 no-print">
  <div class="card-body p-3">
    <form method="GET" action="" class="row g-2 align-items-center">
      <div class="col-md-3">
        <label class="form-label small mb-1 fw-semibold">Pilih Tahun Anggaran / Operasional</label>
        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
            <option value="<?= $y ?>" <?= $tahun_pilih === $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-9 text-end">
        <span class="badge bg-light text-muted border p-2">
          <i class="bi bi-info-circle text-primary me-1"></i> Rumus: <em>Realisasi (%) = (Total Kg Dipesan / 2.000 Kg) &times; 100%</em>
        </span>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Laporan Realisasi Mingguan -->
<div class="card border shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 110px;">Minggu Ke-</th>
          <th>Rentang Tanggal (Senin - Minggu)</th>
          <th class="text-center">Jml Order</th>
          <th class="text-center">Volume (Sak)</th>
          <th class="text-center">Tonase Realisasi</th>
          <th class="text-center">Batas Kuota</th>
          <th style="width: 200px;">Tingkat Realisasi (%)</th>
          <th class="text-center">Sisa Kuota</th>
          <th>Status Kepatuhan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rekap_mingguan)): ?>
          <tr><td colspan="9" class="text-center py-5 text-muted">Belum ada catatan pengadaan SPHP pada tahun <?= $tahun_pilih ?>.</td></tr>
        <?php else: ?>
          <?php foreach ($rekap_mingguan as $m_data): ?>
            <?php
              $persen = round(($m_data['total_kg'] / $batas_mingguan_kg) * 100, 1);
              $sisa_kg = max(0, $batas_mingguan_kg - $m_data['total_kg']);
            ?>
            <tr>
              <td>
                <span class="badge bg-dark fw-bold px-2 py-1">Pekan <?= $m_data['minggu_ke'] ?></span>
              </td>
              <td>
                <div class="fw-semibold text-dark">
                  <?= tgl_indo($m_data['periode_senin']) ?> &mdash; <?= tgl_indo($m_data['periode_minggu']) ?>
                </div>
                <small class="text-muted"><?= count($m_data['orders']) ?> pengajuan</small>
              </td>
              <td class="text-center fw-bold"><?= $m_data['jml_order'] ?>x</td>
              <td class="text-center">
                <span class="badge badge-sphp"><?= number_format($m_data['total_sak']) ?> Sak</span>
              </td>
              <td class="text-center fw-bold text-dark">
                <?= number_format($m_data['total_kg'], 0) ?> Kg
                <small class="d-block text-muted" style="font-size: 0.72rem;">(<?= number_format($m_data['total_kg']/1000, 2) ?> Ton)</small>
              </td>
              <td class="text-center text-muted">
                <?= number_format($batas_mingguan_kg, 0) ?> Kg
                <small class="d-block text-muted" style="font-size: 0.72rem;">(2.0 Ton)</small>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-fill" style="height: 10px;">
                    <div class="progress-bar <?= $persen >= 100 ? 'bg-danger' : ($persen >= 75 ? 'bg-warning' : 'bg-primary') ?>" 
                         style="width: <?= min(100, $persen) ?>%;"></div>
                  </div>
                  <span class="small fw-bold <?= $persen > 100 ? 'text-danger' : 'text-dark' ?>" style="min-width: 40px;">
                    <?= $persen ?>%
                  </span>
                </div>
              </td>
              <td class="text-center fw-semibold <?= $sisa_kg < 200 ? 'text-danger' : 'text-success' ?>">
                <?= number_format($sisa_kg, 0) ?> Kg
              </td>
              <td>
                <?php if ($m_data['total_kg'] > $batas_mingguan_kg): ?>
                  <span class="badge bg-danger"><i class="bi bi-x-octagon me-1"></i>Over Kuota (>2T)</span>
                <?php elseif ($persen >= 90): ?>
                  <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Maksimal</span>
                <?php else: ?>
                  <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Patuh (Aman)</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
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
