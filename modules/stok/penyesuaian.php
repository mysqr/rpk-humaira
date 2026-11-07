<?php
// modules/stok/penyesuaian.php - Stock Opname & Penyesuaian Selisih Fisik
$page_title = 'Stock Opname / Koreksi Stok';
$active_menu = 'stok';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

$produk_list = query("SELECT p.*, k.kode_kategori, k.nama_kategori 
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    ORDER BY p.id_kategori ASC, p.is_eceran ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $stok_fisik_baru = (float)($_POST['stok_fisik_baru'] ?? 0);
    $alasan = escape($_POST['alasan'] ?? 'Stock opname fisik');
    $user_id = $_SESSION['user_id'];

    $prod = query_single("SELECT * FROM produk WHERE id_produk = $id_produk");
    if (!$prod) {
        set_flash('danger', 'Produk tidak ditemukan!');
    } else {
        $stok_sebelum = (float)$prod['stok'];
        $selisih = $stok_fisik_baru - $stok_sebelum;

        if ($selisih == 0) {
            set_flash('info', 'Jumlah stok fisik sama dengan stok sistem, tidak ada perubahan.');
        } else {
            $jenis_transaksi = $selisih > 0 ? 'MASUK' : 'KELUAR';
            $jumlah_mutasi = abs($selisih);

            $conn->begin_transaction();
            try {
                // Update stok produk
                $conn->query("UPDATE produk SET stok = $stok_fisik_baru WHERE id_produk = $id_produk");

                // Catat riwayat
                $ref = "OPNAME-" . date('Ymd-His');
                $ket = "Koreksi Stok Opname: " . ($selisih > 0 ? "+$selisih" : "$selisih") . " {$prod['satuan']}. Alasan: $alasan";
                $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                              VALUES ($id_produk, '$jenis_transaksi', 'PENYESUAIAN', '$ref', $jumlah_mutasi, $stok_sebelum, $stok_fisik_baru, '$ket', $user_id)");

                $conn->commit();
                set_flash('success', "Stok <strong>{$prod['nama_produk']}</strong> berhasil disesuaikan menjadi <strong>$stok_fisik_baru {$prod['satuan']}</strong>.");
                header('Location: ' . BASE_URL . '/modules/stok/index.php');
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                set_flash('danger', 'Gagal memproses penyesuaian: ' . $e->getMessage());
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-sliders text-secondary me-2"></i>Stock Opname / Koreksi Stok Fisik</h6>
        <a href="<?= BASE_URL ?>/modules/stok/index.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label fw-semibold small">Pilih Produk Beras <span class="text-danger">*</span></label>
            <select name="id_produk" id="selectProduk" class="form-select" required onchange="updateInfoStok()">
              <option value="">-- Pilih Produk --</option>
              <?php foreach ($produk_list as $pl): ?>
                <option value="<?= $pl['id_produk'] ?>" data-stok="<?= $pl['stok'] ?>" data-satuan="<?= htmlspecialchars($pl['satuan']) ?>">
                  [<?= $pl['kode_kategori'] ?>] <?= htmlspecialchars($pl['nama_produk']) ?> (<?= $pl['satuan'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Stok di Sistem Saat Ini</label>
              <div class="input-group">
                <input type="text" id="stokSistem" class="form-control bg-light fw-bold" readonly value="0">
                <span class="input-group-text" id="satuanSistem">-</span>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small text-primary">Hasil Hitung Fisik Sebenarnya <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" step="any" name="stok_fisik_baru" id="stokFisik" class="form-control fw-bold text-primary" required min="0" oninput="hitungSelisih()">
                <span class="input-group-text" id="satuanFisik">-</span>
              </div>
            </div>
          </div>

          <!-- Alert Selisih -->
          <div id="selisihAlert" class="alert alert-secondary py-2 small d-none mb-3">
            Selisih Stok: <strong id="selisihText">0</strong>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold small">Alasan Penyesuaian / Keterangan <span class="text-danger">*</span></label>
            <input type="text" name="alasan" class="form-control" placeholder="Contoh: Selisih fisik barang susut / kemasan bocor" required>
          </div>

          <div class="text-end">
            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm" onclick="return confirm('Apakah Anda yakin data hitung fisik sudah benar?')">
              <i class="bi bi-check-lg me-1"></i> Simpan Penyesuaian Stok
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function updateInfoStok() {
  const sel = document.getElementById('selectProduk');
  const opt = sel.options[sel.selectedIndex];
  const stok = parseFloat(opt.getAttribute('data-stok')) || 0;
  const satuan = opt.getAttribute('data-satuan') || '-';

  document.getElementById('stokSistem').value = stok;
  document.getElementById('satuanSistem').textContent = satuan;
  document.getElementById('satuanFisik').textContent = satuan;
  document.getElementById('stokFisik').value = stok;
  hitungSelisih();
}

function hitungSelisih() {
  const sistem = parseFloat(document.getElementById('stokSistem').value) || 0;
  const fisik = parseFloat(document.getElementById('stokFisik').value) || 0;
  const diff = fisik - sistem;
  const alertEl = document.getElementById('selisihAlert');
  const textEl = document.getElementById('selisihText');

  alertEl.classList.remove('d-none');
  if (diff > 0) {
    alertEl.className = 'alert alert-success py-2 small mb-3';
    textEl.textContent = '+' + diff + ' (Stok fisik LEBIH BANYAK dari sistem)';
  } else if (diff < 0) {
    alertEl.className = 'alert alert-danger py-2 small mb-3';
    textEl.textContent = diff + ' (Stok fisik KURANG dari sistem - ada susut/kehilangan)';
  } else {
    alertEl.className = 'alert alert-secondary py-2 small mb-3';
    textEl.textContent = '0 (Stok Cocok)';
  }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
