<?php
// modules/produksi/edit.php - Edit / Koreksi Catatan Hasil Giling Beras
$page_title = 'Edit Catatan Hasil Giling';
$active_menu = 'produksi';
require_once __DIR__ . '/../../config/auth.php';
check_login();

$id_produksi = (int)($_GET['id'] ?? 0);
$prd = query_single("SELECT pg.*, p.nama_produk, p.satuan, p.is_eceran, p.stok, k.nama_kategori, k.kode_kategori 
                     FROM produksi_giling pg 
                     JOIN produk p ON pg.id_produk = p.id_produk 
                     JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
                     WHERE pg.id_produksi = $id_produksi");

if (!$prd) {
    set_flash('danger', 'Data catatan produksi tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/produksi/index.php');
    exit;
}

// Daftar varian produk beras Medium dan Premium
$produk_giling = query("SELECT p.*, k.nama_kategori, k.kode_kategori 
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    WHERE k.kode_kategori IN ('MEDIUM', 'PREMIUM') 
    ORDER BY k.kode_kategori ASC, p.is_eceran ASC, p.berat_kg ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $tanggal_giling = escape($_POST['tanggal_giling'] ?? date('Y-m-d'));
    $jumlah_gabah_kg = !empty($_POST['jumlah_gabah_kg']) ? (float)$_POST['jumlah_gabah_kg'] : 0;
    $jumlah_hasil = (float)($_POST['jumlah_hasil'] ?? 0);
    $raw_biaya_giling = preg_replace('/[^0-9]/', '', (string)($_POST['biaya_giling'] ?? '0'));
    $biaya_giling = (float)$raw_biaya_giling;
    $catatan = escape($_POST['catatan'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($id_produk <= 0 || $jumlah_hasil <= 0) {
        set_flash('danger', 'Silakan pilih varian beras dan masukkan jumlah hasil giling lebih dari 0!');
    } else {
        $old_id_produk = (int)$prd['id_produk'];
        $old_jumlah_hasil = (float)$prd['jumlah_hasil'];
        $kode_produksi = $prd['kode_produksi'];

        $conn->begin_transaction();
        try {
            if ($old_id_produk === $id_produk) {
                // Produk sama, hitung selisih
                $diff = $jumlah_hasil - $old_jumlah_hasil;
                $target_p = query_single("SELECT * FROM produk WHERE id_produk = $id_produk");
                $stok_sebelum = (float)$target_p['stok'];
                $stok_sesudah = $stok_sebelum + $diff;

                if ($stok_sesudah < 0) {
                    throw new Exception("Stok tidak mencukupi untuk pengurangan ini (Stok saat ini: $stok_sebelum {$target_p['satuan']})");
                }

                $conn->query("UPDATE produk SET stok = $stok_sesudah WHERE id_produk = $id_produk");

                if ($diff != 0) {
                    $jenis_trx = $diff > 0 ? 'MASUK' : 'KELUAR';
                    $jml_abs = abs($diff);
                    $ket_stok = "Koreksi hasil giling $kode_produksi: dari $old_jumlah_hasil menjadi $jumlah_hasil {$target_p['satuan']}";
                    $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                                  VALUES ($id_produk, '$jenis_trx', 'PRODUKSI', '$kode_produksi', $jml_abs, $stok_sebelum, $stok_sesudah, '$ket_stok', $user_id)");
                }
            } else {
                // Produk berubah: kurangi stok produk lama, tambah stok produk baru
                $old_p = query_single("SELECT * FROM produk WHERE id_produk = $old_id_produk");
                $stok_lama_sebelum = (float)$old_p['stok'];
                $stok_lama_sesudah = max(0, $stok_lama_sebelum - $old_jumlah_hasil);
                $conn->query("UPDATE produk SET stok = $stok_lama_sesudah WHERE id_produk = $old_id_produk");

                $ket_old = "Koreksi ganti varian giling $kode_produksi: pembatalan stok lama $old_jumlah_hasil {$old_p['satuan']}";
                $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                              VALUES ($old_id_produk, 'KELUAR', 'PRODUKSI', '$kode_produksi', $old_jumlah_hasil, $stok_lama_sebelum, $stok_lama_sesudah, '$ket_old', $user_id)");

                $new_p = query_single("SELECT * FROM produk WHERE id_produk = $id_produk");
                $stok_baru_sebelum = (float)$new_p['stok'];
                $stok_baru_sesudah = $stok_baru_sebelum + $jumlah_hasil;
                $conn->query("UPDATE produk SET stok = $stok_baru_sesudah WHERE id_produk = $id_produk");

                $ket_new = "Koreksi ganti varian giling $kode_produksi: penambahan stok baru $jumlah_hasil {$new_p['satuan']}";
                $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                              VALUES ($id_produk, 'MASUK', 'PRODUKSI', '$kode_produksi', $jumlah_hasil, $stok_baru_sebelum, $stok_baru_sesudah, '$ket_new', $user_id)");
            }

            // Update tabel produksi_giling
            $sql_update = "UPDATE produksi_giling SET 
                           id_produk = $id_produk,
                           tanggal_giling = '$tanggal_giling',
                           jumlah_gabah_kg = $jumlah_gabah_kg,
                           jumlah_hasil = $jumlah_hasil,
                           biaya_giling = $biaya_giling,
                           catatan = '$catatan'
                           WHERE id_produksi = $id_produksi";
            $conn->query($sql_update);

            $conn->commit();
            set_flash('success', "Catatan produksi giling <strong>$kode_produksi</strong> berhasil diperbarui dan stok telah disesuaikan!");
            header('Location: ' . BASE_URL . '/modules/produksi/index.php');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            set_flash('danger', 'Gagal memperbarui catatan produksi: ' . $e->getMessage());
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
          <i class="bi bi-pencil-square text-primary fs-5"></i>
          <h6 class="mb-0 fw-bold">Edit Catatan Produksi Giling: <?= htmlspecialchars($prd['kode_produksi']) ?></h6>
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
              <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($prd['kode_produksi']) ?>" readonly>
              <small class="text-muted">Kode unik referensi transaksi giling.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Tanggal Giling <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_giling" class="form-control" value="<?= htmlspecialchars($prd['tanggal_giling']) ?>" required>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Pilih Varian Beras Yang Dihasilkan <span class="text-danger">*</span></label>
              <select name="id_produk" id="select_produk" class="form-select form-select-lg fs-6" required onchange="updateSatuanLabel()">
                <?php foreach ($produk_giling as $pg): ?>
                  <option value="<?= $pg['id_produk'] ?>" 
                          data-satuan="<?= htmlspecialchars($pg['satuan']) ?>" 
                          <?= $pg['id_produk'] == $prd['id_produk'] ? 'selected' : '' ?>>
                    [<?= $pg['kode_kategori'] ?>] <?= htmlspecialchars($pg['nama_produk']) ?> (<?= $pg['satuan'] ?>) - Stok Saat Ini: <?= number_format($pg['stok'], ($pg['is_eceran']?1:0)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Jumlah Gabah Digiling (Kg)</label>
              <div class="input-group">
                <input type="number" step="0.1" name="jumlah_gabah_kg" class="form-control" value="<?= (float)$prd['jumlah_gabah_kg'] ?>">
                <span class="input-group-text">Kg Gabah</span>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small text-success">Jumlah Beras Bersih Dihasilkan <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" step="any" name="jumlah_hasil" class="form-control fw-bold fs-5 text-success" value="<?= (float)$prd['jumlah_hasil'] ?>" required min="0.1">
                <span class="input-group-text fw-bold text-success" id="satuanLabel"><?= htmlspecialchars($prd['satuan']) ?></span>
              </div>
              <small class="text-muted">Perubahan jumlah akan otomatis menyesuaikan stok produk.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Biaya Jasa / Ongkos Giling (Rp)</label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">Rp</span>
                <input type="text" inputmode="numeric" name="biaya_giling" class="form-control fw-bold" value="<?= number_format($prd['biaya_giling'], 0, ',', '.') ?>" autocomplete="off" oninput="formatRibuanInput(this)">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Catatan Tambahan</label>
              <input type="text" name="catatan" class="form-control" value="<?= htmlspecialchars($prd['catatan'] ?? '') ?>" placeholder="Keterangan koreksi / sumber gabah">
            </div>

            <div class="col-12 mt-4 d-flex justify-content-between">
              <a href="<?= BASE_URL ?>/modules/produksi/hapus.php?id=<?= $prd['id_produksi'] ?>" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin membatalkan/menghapus catatan produksi giling <?= htmlspecialchars($prd['kode_produksi']) ?>? Stok beras (+<?= $prd['jumlah_hasil'] ?> <?= $prd['satuan'] ?>) akan otomatis dikurangi kembali.')">
                <i class="bi bi-trash me-1"></i> Hapus Catatan Ini
              </a>
              <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                <i class="bi bi-save me-1"></i> Simpan Perubahan & Sesuaikan Stok
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
