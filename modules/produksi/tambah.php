<?php
// modules/produksi/tambah.php - Input Hasil Giling Beras Sendiri
$page_title = 'Input Hasil Giling Beras Sendiri';
$active_menu = 'produksi';
require_once __DIR__ . '/../../config/auth.php';
check_login();

// Hanya ambil produk beras Medium dan Premium (karena SPHP berasal dari BULOG)
$produk_giling = query("SELECT p.*, k.nama_kategori, k.kode_kategori 
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    WHERE k.kode_kategori IN ('MEDIUM', 'PREMIUM') 
    ORDER BY k.kode_kategori ASC, p.is_eceran ASC, p.berat_kg ASC");

// Generate Auto Kode Produksi: PRD-YYYYMMDD-XXX
$today_str = date('Ymd');
$cek_last = query_single("SELECT kode_produksi FROM produksi_giling WHERE kode_produksi LIKE 'PRD-$today_str%' ORDER BY id_produksi DESC LIMIT 1");
if ($cek_last) {
    $last_num = (int)substr($cek_last['kode_produksi'], -3);
    $new_num = str_pad($last_num + 1, 3, '0', STR_PAD_LEFT);
} else {
    $new_num = '001';
}
$auto_kode = "PRD-$today_str-$new_num";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_produksi = escape($_POST['kode_produksi'] ?? $auto_kode);
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $tanggal_giling = escape($_POST['tanggal_giling'] ?? date('Y-m-d'));
    $jumlah_gabah_kg = !empty($_POST['jumlah_gabah_kg']) ? (float)$_POST['jumlah_gabah_kg'] : 0;
    $jumlah_hasil = (float)($_POST['jumlah_hasil'] ?? 0);
    $raw_biaya_giling = preg_replace('/[^0-9]/', '', (string)($_POST['biaya_giling'] ?? '0'));
    $biaya_giling = (float)$raw_biaya_giling;
    $catatan = escape($_POST['catatan'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($id_produk <= 0 || $jumlah_hasil <= 0) {
        set_flash('danger', 'Silakan pilih produk beras dan masukkan jumlah hasil giling lebih dari 0!');
    } else {
        $p = query_single("SELECT * FROM produk WHERE id_produk = $id_produk");
        if (!$p) {
            set_flash('danger', 'Produk beras tidak ditemukan!');
        } else {
            // Mulai transaksi
            $conn->begin_transaction();
            try {
                // 1. Simpan ke produksi_giling
                $sql_prd = "INSERT INTO produksi_giling (kode_produksi, id_produk, tanggal_giling, jumlah_gabah_kg, jumlah_hasil, biaya_giling, catatan, id_user)
                            VALUES ('$kode_produksi', $id_produk, '$tanggal_giling', $jumlah_gabah_kg, $jumlah_hasil, $biaya_giling, '$catatan', $user_id)";
                $conn->query($sql_prd);
                $id_produksi = $conn->insert_id;

                // 2. Tambah stok produk
                $stok_sebelum = (float)$p['stok'];
                $stok_sesudah = $stok_sebelum + $jumlah_hasil;
                $conn->query("UPDATE produk SET stok = $stok_sesudah WHERE id_produk = $id_produk");

                // 3. Catat di riwayat/kartu stok
                $ket_stok = "Hasil giling padi mandiri: " . ($jumlah_gabah_kg > 0 ? "$jumlah_gabah_kg Kg gabah -> " : "") . "$jumlah_hasil {$p['satuan']}";
                $sql_stok = "INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                             VALUES ($id_produk, 'MASUK', 'PRODUKSI', '$kode_produksi', $jumlah_hasil, $stok_sebelum, $stok_sesudah, '$ket_stok', $user_id)";
                $conn->query($sql_stok);

                $conn->commit();
                set_flash('success', "Hasil giling berhasil dicatat! Stok <strong>{$p['nama_produk']}</strong> otomatis bertambah <strong>+$jumlah_hasil {$p['satuan']}</strong>.");
                header('Location: ' . BASE_URL . '/modules/produksi/index.php');
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                set_flash('danger', 'Gagal memproses catatan produksi: ' . $e->getMessage());
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-gear-wide-connected text-success fs-5"></i>
          <h6 class="mb-0 fw-bold">Pencatatan Hasil Giling Beras (Medium & Premium)</h6>
        </div>
        <a href="<?= BASE_URL ?>/modules/produksi/index.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Nomor / Kode Produksi</label>
              <input type="text" name="kode_produksi" class="form-control bg-light" value="<?= $auto_kode ?>" readonly>
              <small class="text-muted">Nomor unik otomatis sistem</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Tanggal Giling <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_giling" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Pilih Varian Beras Yang Dihasilkan <span class="text-danger">*</span></label>
              <select name="id_produk" id="select_produk" class="form-select form-select-lg fs-6" required onchange="updateSatuanLabel()">
                <option value="">-- Pilih Beras Medium / Premium --</option>
                <?php foreach ($produk_giling as $pg): ?>
                  <option value="<?= $pg['id_produk'] ?>" data-satuan="<?= htmlspecialchars($pg['satuan']) ?>" data-stok="<?= $pg['stok'] ?>">
                    [<?= $pg['kode_kategori'] ?>] <?= htmlspecialchars($pg['nama_produk']) ?> (<?= $pg['satuan'] ?>) - Stok Saat Ini: <?= number_format($pg['stok'], ($pg['is_eceran']?1:0)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Jumlah Gabah Digiling (Opsional)</label>
              <div class="input-group">
                <input type="number" step="0.1" name="jumlah_gabah_kg" class="form-control" placeholder="Contoh: 500">
                <span class="input-group-text">Kg Gabah</span>
              </div>
              <small class="text-muted">Berat gabah kering panen / gabah giling yang diolah.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small text-success">Jumlah Beras Bersih Dihasilkan <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" step="any" name="jumlah_hasil" class="form-control fw-bold fs-5 text-success" placeholder="0" required min="0.1">
                <span class="input-group-text fw-bold text-success" id="satuanLabel">Sak / Liter</span>
              </div>
              <small class="text-muted">Jumlah ini akan otomatis menambah stok produk di sistem.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Biaya Jasa / Ongkos Giling (Rp)</label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">Rp</span>
                <input type="text" inputmode="numeric" name="biaya_giling" class="form-control fw-bold" placeholder="0" autocomplete="off" oninput="formatRibuanInput(this)">
              </div>
              <small class="text-muted">Biaya operasional mesin atau ongkos jasa perontokan/giling.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Catatan Tambahan</label>
              <input type="text" name="catatan" class="form-control" placeholder="Contoh: Gabah petani asal Desa Sukamaju">
            </div>

            <div class="col-12 mt-4 text-end">
              <button type="submit" class="btn btn-success px-4 py-2 fw-semibold shadow-sm">
                <i class="bi bi-check-circle me-1"></i> Simpan & Tambah Stok Beras
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function updateSatuanLabel() {
  const sel = document.getElementById('select_produk');
  const opt = sel.options[sel.selectedIndex];
  const satuan = opt.getAttribute('data-satuan') || 'Sak / Liter';
  document.getElementById('satuanLabel').textContent = satuan;
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
