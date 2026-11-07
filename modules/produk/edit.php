<?php
// modules/produk/edit.php - Edit Master Produk Beras
$page_title = 'Ubah Data Produk Beras';
$active_menu = 'produk';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

$id_produk = (int)($_GET['id'] ?? 0);
$produk = query_single("SELECT p.*, k.kode_kategori, k.nama_kategori 
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    WHERE p.id_produk = $id_produk");

if (!$produk) {
    set_flash('danger', 'Data produk tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/produk/index.php');
    exit;
}

$kategori_list = query("SELECT * FROM kategori_beras ORDER BY id_kategori ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = escape($_POST['nama_produk'] ?? '');
    $raw_harga_beli = preg_replace('/[^0-9]/', '', (string)($_POST['harga_beli'] ?? '0'));
    $harga_beli = (float)$raw_harga_beli;

    $raw_harga_jual = preg_replace('/[^0-9]/', '', (string)($_POST['harga_jual'] ?? '0'));
    $harga_jual = (float)$raw_harga_jual;

    $stok_minimum = (float)($_POST['stok_minimum'] ?? 10);
    $keterangan = escape($_POST['keterangan'] ?? '');

    // Satuan bisa diedit untuk semua kategori (termasuk SPHP antara Kemasan 5kg dan Per Kg)
    $satuan_update = "";
    if (isset($_POST['satuan_pilih'])) {
        $satuan_pilih = $_POST['satuan_pilih'];
        if ($produk['kode_kategori'] === 'SPHP') {
            if ($satuan_pilih === 'kg') {
                $satuan = 'Kg';
                $berat_kg = 1.00;
                $is_eceran = 1;
            } else {
                $satuan = 'Kemasan 5 Kg';
                $berat_kg = 5.00;
                $is_eceran = 0;
            }
        } else {
            if ($satuan_pilih === 'liter') {
                $satuan = 'Liter';
                $berat_kg = 0.753; // massa jenis beras: 1 liter = 0.753 kg (1 kg ≈ 1.328 liter)
                $is_eceran = 1;
            } elseif ($satuan_pilih === 'kg') {
                $satuan = 'Kg';
                $berat_kg = 1.00;
                $is_eceran = 1;
            } elseif ($satuan_pilih === 'sak_1kg') {
                $satuan = 'Kemasan 1 Kg';
                $berat_kg = 1.00;
                $is_eceran = 0;
            } elseif ($satuan_pilih === 'sak_10kg') {
                $satuan = 'Kemasan 10 Kg';
                $berat_kg = 10.00;
                $is_eceran = 0;
            } elseif ($satuan_pilih === 'sak_25kg') {
                $satuan = 'Kemasan 25 Kg';
                $berat_kg = 25.00;
                $is_eceran = 0;
            } else {
                $satuan = 'Kemasan 5 Kg';
                $berat_kg = 5.00;
                $is_eceran = 0;
            }
        }
        $satuan_update = ", satuan = '$satuan', berat_kg = $berat_kg, is_eceran = $is_eceran";
    }

    $sql = "UPDATE produk SET 
            nama_produk = '$nama_produk',
            harga_beli = $harga_beli,
            harga_jual = $harga_jual,
            stok_minimum = $stok_minimum,
            keterangan = '$keterangan'
            $satuan_update
            WHERE id_produk = $id_produk";

    if ($conn->query($sql)) {
        set_flash('success', "Data produk beras <strong>$nama_produk</strong> berhasil diperbarui!");
        header('Location: ' . BASE_URL . '/modules/produk/index.php');
        exit;
    } else {
        set_flash('danger', 'Gagal memperbarui produk: ' . $conn->error);
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Produk Beras: <?= htmlspecialchars($produk['nama_produk']) ?></h6>
        <a href="<?= BASE_URL ?>/modules/produk/index.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Kategori Beras</label>
              <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($produk['nama_kategori']) ?>" readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Kode Produk</label>
              <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($produk['kode_produk']) ?>" readonly>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Nama Produk Beras <span class="text-danger">*</span></label>
              <input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($produk['nama_produk']) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Satuan / Kemasan Penjualan <span class="text-danger">*</span></label>
              <?php if ($produk['kode_kategori'] === 'SPHP'): ?>
                <select name="satuan_pilih" id="satuan_pilih" class="form-select" onchange="handleSatuanSphpEdit(this)">
                  <option value="sak_5kg" <?= ($produk['is_eceran'] == 0) ? 'selected' : '' ?>>Kemasan Sak 5 Kg (HET Rp 62.500)</option>
                  <option value="kg" <?= ($produk['is_eceran'] == 1) ? 'selected' : '' ?>>Per Kg - Eceran Curah (HET Rp 12.500)</option>
                </select>
                <div class="text-primary small mt-1 p-2 bg-primary-subtle rounded border border-primary-subtle">
                  <i class="bi bi-info-circle-fill me-1"></i> Beras SPHP BULOG resmi dibedakan menjadi:
                  <ul class="mb-0 ps-3 mt-1">
                    <li><strong>Kemasan Sak 5 Kg</strong>: HET Rp 62.500 (HPP Rp 55.000)</li>
                    <li><strong>Per Kg (Eceran)</strong>: HET Rp 12.500 (HPP Rp 11.000)</li>
                  </ul>
                </div>
              <?php else: ?>
                <select name="satuan_pilih" class="form-select">
                  <option value="sak_1kg" <?= ($produk['is_eceran'] == 0 && $produk['berat_kg'] == 1) ? 'selected' : '' ?>>Kemasan 1 Kg</option>
                  <option value="sak_5kg" <?= ($produk['is_eceran'] == 0 && $produk['berat_kg'] == 5) ? 'selected' : '' ?>>Kemasan Sak 5 Kg</option>
                  <option value="sak_10kg" <?= ($produk['is_eceran'] == 0 && $produk['berat_kg'] == 10) ? 'selected' : '' ?>>Kemasan Sak 10 Kg</option>
                  <option value="sak_25kg" <?= ($produk['is_eceran'] == 0 && $produk['berat_kg'] == 25) ? 'selected' : '' ?>>Kemasan Sak 25 Kg</option>
                  <option value="kg" <?= ($produk['is_eceran'] == 1 && $produk['satuan'] === 'Kg') ? 'selected' : '' ?>>Eceran Curah (Kg)</option>
                  <option value="liter" <?= ($produk['is_eceran'] == 1 && $produk['satuan'] !== 'Kg') ? 'selected' : '' ?>>Eceran Curah (Liter - 1 L = 0,753 Kg)</option>
                </select>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Stok Saat Ini</label>
              <div class="input-group">
                <input type="text" class="form-control bg-light fw-bold" value="<?= number_format($produk['stok'], ($produk['is_eceran'] ? 1 : 0)) ?>" readonly>
                <span class="input-group-text"><?= $produk['satuan'] ?></span>
              </div>
              <small class="text-muted">Penyesuaian stok dilakukan via menu Monitoring Stok atau Giling Sendiri.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Harga Beli / HPP (Rp) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">Rp</span>
                <input type="text" inputmode="numeric" name="harga_beli" class="form-control fw-bold" value="<?= number_format($produk['harga_beli'], 0, ',', '.') ?>" autocomplete="off" required oninput="formatRibuanInput(this)">
              </div>
              <?php if ($produk['kode_kategori'] === 'MEDIUM' && $produk['is_eceran'] == 1): ?>
                <div class="text-success small mt-1 p-2 bg-success-subtle rounded border border-success-subtle">
                  <i class="bi bi-info-circle-fill me-1"></i> <strong>HPP Beras Medium Eceran: Rp 10.500</strong> per Liter / Kg.
                </div>
              <?php elseif ($produk['kode_kategori'] === 'PREMIUM' && $produk['is_eceran'] == 1): ?>
                <div class="text-warning small mt-1 p-2 bg-warning-subtle rounded border border-warning-subtle">
                  <i class="bi bi-info-circle-fill me-1"></i> <strong>HPP Beras Premium Eceran: Rp 11.300</strong> per Liter / Kg.
                </div>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Harga Jual Konsumen (Rp) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">Rp</span>
                <input type="text" inputmode="numeric" name="harga_jual" class="form-control fw-bold text-primary" value="<?= number_format($produk['harga_jual'], 0, ',', '.') ?>" autocomplete="off" required oninput="formatRibuanInput(this)">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Batas Stok Minimum</label>
              <input type="number" step="any" name="stok_minimum" class="form-control" value="<?= (float)$produk['stok_minimum'] ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Keterangan / Catatan</label>
              <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($produk['keterangan'] ?? '') ?>">
            </div>

            <div class="col-12 text-end mt-4">
              <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                <i class="bi bi-save me-1"></i> Simpan Perubahan
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function handleSatuanSphpEdit(el) {
  const hargaBeli = document.querySelector('input[name="harga_beli"]');
  const hargaJual = document.querySelector('input[name="harga_jual"]');
  if (el.value === 'kg') {
    if (confirm('Ubah harga default SPHP ke varian Per Kg (Harga Beli: Rp 11.000, Harga Jual: Rp 12.500)?')) {
      hargaBeli.value = '11.000';
      hargaJual.value = '12.500';
    }
  } else if (el.value === 'sak_5kg') {
    if (confirm('Ubah harga default SPHP ke varian Kemasan Sak 5 Kg (Harga Beli: Rp 55.000, Harga Jual: Rp 62.500)?')) {
      hargaBeli.value = '55.000';
      hargaJual.value = '62.500';
    }
  }
}

function formatRibuanInput(el) {
  let val = el.value.replace(/[^0-9]/g, '');
  if (!val) {
    el.value = '';
    return;
  }
  el.value = val.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
