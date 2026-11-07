<?php
// modules/pengadaan_sphp/tambah.php - Form Permintaan Pengadaan SPHP ke BULOG
$page_title = 'Buat Permintaan Pengadaan SPHP';
$active_menu = 'pengadaan_sphp';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

// Ambil data produk SPHP (utamakan varian kemasan sak untuk pengadaan resmi BULOG)
$produk_sphp = query_single("SELECT p.*, k.kode_kategori 
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    WHERE k.kode_kategori = 'SPHP' AND p.is_eceran = 0 
    ORDER BY p.id_produk ASC LIMIT 1");
if (!$produk_sphp) {
    $produk_sphp = query_single("SELECT p.*, k.kode_kategori 
        FROM produk p 
        JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
        WHERE k.kode_kategori = 'SPHP' LIMIT 1");
}

if (!$produk_sphp) {
    set_flash('danger', 'Produk Beras SPHP belum terdaftar di master data!');
    header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/index.php');
    exit;
}

// --- Ambil stok saat ini & hitung ambang batas 10% ---
// Kuota total = 400 sak (2.000 kg). Batas restock = stok <= 10% dari 400 sak = 40 sak
$stok_saat_ini_sak = (float)$produk_sphp['stok']; // stok dalam satuan SAK
$kuota_total_sak    = 400;   // 400 sak @ 5kg = 2.000 kg
$persen_ambang      = 10;    // 10%
$batas_restock_sak  = $kuota_total_sak * ($persen_ambang / 100); // 40 sak
$persen_stok_sisa   = ($kuota_total_sak > 0) ? round(($stok_saat_ini_sak / $kuota_total_sak) * 100, 1) : 0;
$boleh_restock      = ($stok_saat_ini_sak <= $batas_restock_sak);

// Kuota mingguan BULOG
$kuota = get_kuota_sphp_minggu_ini();

// Generate Auto No. SO Internal
$today_str = date('Ymd');
$bulan_romawi = ['','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
$no_so_auto = 'SO/8052/' . date('m') . '/' . date('Y') . '/10020';
$cek_last = query_single("SELECT no_pengadaan FROM pengadaan_sphp WHERE no_pengadaan LIKE 'SPHP-ORD-$today_str%' ORDER BY id_pengadaan DESC LIMIT 1");
if ($cek_last) {
    $last_num = (int)substr($cek_last['no_pengadaan'], -2);
    $new_num = str_pad($last_num + 1, 2, '0', STR_PAD_LEFT);
} else {
    $new_num = '01';
}
$auto_no = "SPHP-ORD-$today_str-$new_num";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_pengadaan   = escape($_POST['no_pengadaan'] ?? $auto_no);
    $no_ref_bulog   = escape($_POST['no_ref_bulog'] ?? $no_so_auto);
    $tanggal_order  = escape($_POST['tanggal_order'] ?? date('Y-m-d'));
    $cabang_bulog   = '10020 - KANCAB CIANJUR';
    $gudang_bulog   = 'KOMPLEKS PERGUDANGAN BOJONGHERANG';
    $jumlah_sak     = (int)($_POST['jumlah_sak'] ?? 0);
    $total_berat_kg = $jumlah_sak * 5.00;
    $raw_harga      = preg_replace('/[^0-9]/', '', (string)($_POST['harga_satuan'] ?? '55000'));
    $harga_satuan   = (float)$raw_harga;
    $total_biaya    = $jumlah_sak * $harga_satuan;
    $catatan        = escape($_POST['catatan'] ?? '');
    $user_id        = $_SESSION['user_id'];

    // --- Validasi Utama: Cek stok sisa <= 10% sebelum boleh restock ---
    $stok_cek = (float)query_single("SELECT stok FROM produk WHERE id_produk = " . (int)$produk_sphp['id_produk'])['stok'];
    $override_restock = isset($_POST['override_restock']) && $_POST['override_restock'] === '1';

    if ($stok_cek > $batas_restock_sak && !$override_restock) {
        $persen_skrg = round(($stok_cek / $kuota_total_sak) * 100, 1);
        set_flash('warning', "Belum waktunya restock SPHP! Stok saat ini masih <strong>" . number_format($stok_cek, 0) . " Sak (" . number_format($stok_cek * 5, 0) . " Kg)</strong> = <strong>{$persen_skrg}%</strong> dari total kuota. Restock BULOG hanya diperbolehkan jika sisa stok ≤ <strong>{$persen_ambang}%</strong> ({$batas_restock_sak} Sak / " . ($batas_restock_sak * 5) . " Kg).");
    } elseif ($jumlah_sak <= 0) {
        set_flash('danger', 'Jumlah sak harus lebih dari 0!');
    } elseif ($total_berat_kg > $kuota['sisa_kg']) {
        set_flash('danger', "Pesanan melebihi sisa kuota mingguan BULOG! Sisa: " . number_format($kuota['sisa_kg'], 0) . " Kg (" . floor($kuota['sisa_kg'] / 5) . " Sak).");
    } else {
        $id_produk = (int)$produk_sphp['id_produk'];
        $sql = "INSERT INTO pengadaan_sphp (no_pengadaan, no_ref_bulog, tanggal_order, cabang_bulog, id_produk, jumlah_sak, total_berat_kg, harga_satuan, total_biaya, status, catatan, id_user_created)
                VALUES ('$no_pengadaan', '$no_ref_bulog', '$tanggal_order', '$cabang_bulog', $id_produk, $jumlah_sak, $total_berat_kg, $harga_satuan, $total_biaya, 'diajukan', '$catatan', $user_id)";

        if ($conn->query($sql)) {
            $id_baru = $conn->insert_id;
            // Langsung redirect ke halaman dokumen Order Penjualan BULOG
            header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/detail.php?id=' . $id_baru);
            exit;
        } else {
            set_flash('danger', 'Gagal membuat pesanan: ' . $conn->error);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <?php if (!$boleh_restock): ?>
    <!-- === PERINGATAN: Stok Masih Cukup === -->
    <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 d-flex gap-3 align-items-start">
      <i class="bi bi-exclamation-triangle-fill fs-4 text-warning mt-1 flex-shrink-0"></i>
      <div class="w-100">
        <div class="fw-bold mb-1">⚠️ Stok SPHP Masih Mencukupi — Restock Belum Diperbolehkan</div>
        <p class="mb-2 small">
          Sesuai kebijakan BULOG, pengajuan restock hanya diperbolehkan jika sisa stok sudah
          <strong>≤ <?= $persen_ambang ?>%</strong> (≤ <?= number_format($batas_restock_sak, 0) ?> Sak / <?= number_format($batas_restock_sak * 5, 0) ?> Kg) dari kuota total <?= number_format($kuota_total_sak, 0) ?> Sak.
        </p>
        <div class="mb-2">
          <div class="d-flex justify-content-between small fw-semibold mb-1">
            <span>Stok Saat Ini: <strong class="text-danger"><?= number_format($stok_saat_ini_sak, 0) ?> Sak (<?= number_format($stok_saat_ini_sak * 5, 0) ?> Kg)</strong></span>
            <span class="text-muted"><?= $persen_stok_sisa ?>% dari <?= number_format($kuota_total_sak, 0) ?> Sak</span>
          </div>
          <div class="progress" style="height: 12px;">
            <?php $bar_cls = $persen_stok_sisa <= $persen_ambang ? 'bg-danger' : ($persen_stok_sisa <= 30 ? 'bg-warning' : 'bg-success'); ?>
            <div class="progress-bar <?= $bar_cls ?>" style="width: <?= min(100, $persen_stok_sisa) ?>%"></div>
          </div>
          <div class="d-flex justify-content-between small text-muted mt-1">
            <span>0</span>
            <span>Ambang Restock (≤<?= $persen_ambang ?>%)</span>
            <span><?= number_format($kuota_total_sak, 0) ?> Sak</span>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/index.php" class="btn btn-sm btn-outline-warning fw-semibold">
          <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
      </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-file-earmark-text text-primary fs-5"></i>
          <h6 class="mb-0 fw-bold">Form Permintaan Pengadaan SPHP ke BULOG</h6>
        </div>
        <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/index.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>

      <div class="card-body p-4">

        <!-- Info Stok Ringkas -->
        <?php if ($boleh_restock): ?>
        <div class="alert alert-success border-0 d-flex gap-2 align-items-center py-2 px-3 mb-4 rounded-3 small">
          <i class="bi bi-check-circle-fill text-success"></i>
          <span>Stok SPHP saat ini <strong><?= number_format($stok_saat_ini_sak, 0) ?> Sak (<?= $persen_stok_sisa ?>%)</strong> — Sudah memenuhi syarat restock ≤<?= $persen_ambang ?>%.</span>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="formPengadaan">

          <?php if (!$boleh_restock): ?>
          <!-- Override darurat jika stok masih tinggi -->
          <div class="alert alert-danger border-0 rounded-3 p-3 mb-4">
            <div class="fw-bold mb-2"><i class="bi bi-shield-exclamation me-1"></i> Pengajuan Darurat / Tetap Lanjutkan</div>
            <p class="small mb-2">Stok masih <strong><?= number_format($stok_saat_ini_sak, 0) ?> Sak (<?= $persen_stok_sisa ?>%)</strong>, namun pemilik dapat tetap mengajukan jika ada kebutuhan mendesak.</p>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="override_restock" value="1" id="cbOverride" required>
              <label class="form-check-label fw-semibold small text-danger" for="cbOverride">
                Saya mengerti risiko & tetap mengajukan restock meskipun stok belum habis 10%
              </label>
            </div>
          </div>
          <?php endif; ?>

          <div class="row g-3">

            <!-- No. SO BULOG -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small">No. Order Penjualan (SO) BULOG</label>
              <input type="text" name="no_ref_bulog" class="form-control font-monospace" value="<?= htmlspecialchars($no_so_auto) ?>"
                placeholder="Contoh: SO/8052/10/2026/10020">
              <small class="text-muted" style="font-size: 0.73rem;">Nomor dari aplikasi resmi SPHP BULOG (opsional).</small>
            </div>

            <!-- Tanggal Order -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Tanggal Order <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_order" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <!-- Komoditas (readonly) -->
            <div class="col-12">
              <label class="form-label fw-semibold small">Komoditas</label>
              <input type="text" class="form-control bg-light text-primary fw-bold" 
                value="[B0010201Z] BERAS MEDIUM HASIL GILING 25% LOGO SPHP 5 KG PSO DN" readonly>
            </div>

            <!-- Gudang & Cabang (readonly - sudah fix) -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Cabang BULOG</label>
              <input type="text" class="form-control bg-light" value="10020 - KANCAB CIANJUR" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Gudang</label>
              <input type="text" class="form-control bg-light" value="KOMPLEKS PERGUDANGAN BOJONGHERANG" readonly>
            </div>

            <!-- Jumlah Sak & Harga -->
            <div class="col-md-5">
              <label class="form-label fw-semibold small">Jumlah Sak (@ 5 Kg) <span class="text-danger">*</span></label>
              <input type="number" name="jumlah_sak" id="jumlah_sak" class="form-control form-control-lg fw-bold text-center"
                placeholder="0" min="1" max="<?= (int)floor($kuota['sisa_kg'] / 5) ?>" required oninput="hitungTotal()">
              <small class="text-muted">Total Berat: <strong id="totalKgLabel">0</strong> Kg</small>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Harga Tebus / Sak (Rp)</label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">Rp</span>
                <input type="text" inputmode="numeric" name="harga_satuan" id="harga_satuan" class="form-control fw-bold"
                  value="<?= number_format((float)$produk_sphp['harga_beli'] ?: 55000, 0, ',', '.') ?>"
                  required autocomplete="off" oninput="formatRibuanInput(this); hitungTotal()">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold small">Total Biaya</label>
              <div class="input-group">
                <span class="input-group-text text-muted small">Rp</span>
                <input type="text" id="total_biaya_label" class="form-control fw-bold text-dark" value="0" readonly>
              </div>
            </div>

            <!-- Catatan -->
            <div class="col-12">
              <label class="form-label fw-semibold small">Catatan (Opsional)</label>
              <input type="text" name="catatan" class="form-control" placeholder="Contoh: Pengadaan rutin mingguan">
            </div>

            <!-- Rekening & Syarat info (tampil saja, tidak perlu input) -->
            <div class="col-12">
              <div class="p-3 bg-light rounded-3 border small text-muted d-flex flex-wrap gap-3">
                <span><i class="bi bi-bank me-1"></i> <strong>Rekening:</strong> BRI-010501002479306</span>
                <span><i class="bi bi-credit-card me-1"></i> <strong>Syarat Bayar:</strong> Tunai</span>
                <span><i class="bi bi-arrow-left-right me-1"></i> <strong>Metode:</strong> Transfer</span>
                <span><i class="bi bi-shield-check me-1"></i> <strong>PPN:</strong> 12% (Dibebaskan)</span>
              </div>
            </div>

            <!-- Tombol Submit -->
            <div class="col-12 mt-3">
              <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" id="btnSubmit">
                <i class="bi bi-file-earmark-arrow-right me-2"></i> Buat Order Penjualan BULOG
              </button>
              <p class="text-muted small text-center mt-2 mb-0">
                <i class="bi bi-info-circle me-1"></i> Setelah disimpan, dokumen Order Penjualan akan langsung ditampilkan dan siap untuk di-Approve.
              </p>
            </div>

          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
const maxSisaKg = <?= (float)$kuota['sisa_kg'] ?>;

function hitungTotal() {
  const sak = parseInt(document.getElementById('jumlah_sak').value) || 0;
  const hrgStr = document.getElementById('harga_satuan').value.replace(/[^0-9]/g, '');
  const hrg = parseFloat(hrgStr) || 0;
  const kg = sak * 5;
  const total = sak * hrg;

  document.getElementById('totalKgLabel').textContent = kg.toLocaleString('id-ID');
  document.getElementById('total_biaya_label').value = total.toLocaleString('id-ID');

  if (kg > maxSisaKg && maxSisaKg > 0) {
    document.getElementById('jumlah_sak').setCustomValidity('Melebihi sisa kuota mingguan BULOG (' + maxSisaKg + ' Kg)!');
  } else {
    document.getElementById('jumlah_sak').setCustomValidity('');
  }
}

function formatRibuanInput(el) {
  let val = el.value.replace(/[^0-9]/g, '');
  if (!val) { el.value = ''; return; }
  el.value = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
