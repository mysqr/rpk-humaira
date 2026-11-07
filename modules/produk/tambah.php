la<?php
// modules/produk/tambah.php - Tambah Master Produk
$page_title = 'Tambah Produk Beras Baru';
$active_menu = 'produk';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

$kategori_list = query("SELECT * FROM kategori_beras ORDER BY id_kategori ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $kode_produk = strtoupper(escape($_POST['kode_produk'] ?? ''));
    $nama_produk = escape($_POST['nama_produk'] ?? '');
    $satuan_pilih = $_POST['satuan_pilih'] ?? 'sak_5kg';
    $raw_harga_beli = preg_replace('/[^0-9]/', '', (string)($_POST['harga_beli'] ?? '0'));
    $harga_beli = (float)$raw_harga_beli;

    $raw_harga_jual = preg_replace('/[^0-9]/', '', (string)($_POST['harga_jual'] ?? '0'));
    $harga_jual = (float)$raw_harga_jual;

    $stok_awal = (float)($_POST['stok_awal'] ?? 0);
    $stok_minimum = (float)($_POST['stok_minimum'] ?? 10);
    $keterangan = escape($_POST['keterangan'] ?? '');

    // Cek kategori apakah SPHP
    $kat = query_single("SELECT * FROM kategori_beras WHERE id_kategori = $id_kategori");
    if ($kat && $kat['kode_kategori'] === 'SPHP') {
        // Beras SPHP dibedakan antara Kemasan (5kg) dan Eceran Per Kg
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

    // Validasi kode unik
    $cek_kode = query_single("SELECT id_produk FROM produk WHERE kode_produk = '$kode_produk'");
    if ($cek_kode) {
        set_flash('danger', "Kode produk <strong>$kode_produk</strong> sudah ada, gunakan kode lain!");
    } else {
        $sql = "INSERT INTO produk (id_kategori, kode_produk, nama_produk, satuan, berat_kg, is_eceran, harga_beli, harga_jual, stok, stok_minimum, keterangan) 
                VALUES ($id_kategori, '$kode_produk', '$nama_produk', '$satuan', $berat_kg, $is_eceran, $harga_beli, $harga_jual, $stok_awal, $stok_minimum, '$keterangan')";
        if ($conn->query($sql)) {
            $new_id = $conn->insert_id;
            // Catat stok awal jika > 0
            if ($stok_awal > 0) {
                $user_id = $_SESSION['user_id'];
                $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                              VALUES ($new_id, 'MASUK', 'PENYESUAIAN', 'STOK-AWAL', $stok_awal, 0, $stok_awal, 'Saldo awal produk', $user_id)");
            }
            set_flash('success', "Produk beras <strong>$nama_produk</strong> berhasil ditambahkan!");
            header('Location: ' . BASE_URL . '/modules/produk/index.php');
            exit;
        } else {
            set_flash('danger', 'Gagal menambahkan produk: ' . $conn->error);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Master Produk Beras</h6>
        <a href="<?= BASE_URL ?>/modules/produk/index.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="" id="formProduk">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Kategori Beras <span class="text-danger">*</span></label>
              <select name="id_kategori" id="id_kategori" class="form-select" required onchange="handleKategoriChange()">
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($kategori_list as $kat): ?>
                  <option value="<?= $kat['id_kategori'] ?>" data-kode="<?= $kat['kode_kategori'] ?>">
                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Kode Produk <span class="text-danger">*</span></label>
              <input type="text" name="kode_produk" id="kode_produk" class="form-control" placeholder="Contoh: MED-5KG, PRE-LTR" required>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Nama Produk Beras <span class="text-danger">*</span></label>
              <input type="text" name="nama_produk" class="form-control" placeholder="Contoh: Beras Medium Cap Rojolele 5 Kg" required>
            </div>

            <!-- Satuan / Varian Kemasan -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Satuan / Kemasan Penjualan <span class="text-danger">*</span></label>
              <select name="satuan_pilih" id="satuan_pilih" class="form-select" required onchange="handleSatuanChange()">
                <option value="sak_5kg" selected>Kemasan Sak 5 Kg</option>
                <option value="kg">Per Kg (Eceran Curah)</option>
                <option value="sak_1kg">Kemasan 1 Kg</option>
                <option value="sak_10kg">Kemasan Sak 10 Kg</option>
                <option value="sak_25kg">Kemasan Sak 25 Kg</option>
                <option value="liter">Eceran Curah (Liter - 1 L = 0,753 Kg)</option>
              </select>
              <div id="sphpAlertSatuan" class="text-primary small mt-1 d-none p-2 bg-primary-subtle rounded border border-primary-subtle">
                <i class="bi bi-info-circle-fill me-1"></i> Beras SPHP BULOG resmi dibedakan menjadi:
                <ul class="mb-0 ps-3 mt-1">
                  <li><strong>Kemasan Sak 5 Kg</strong>: Harga Jual HET <strong>Rp 62.500</strong> (HPP Rp 55.000)</li>
                  <li><strong>Per Kg (Eceran)</strong>: Harga Jual HET <strong>Rp 12.500</strong> (HPP Rp 11.000)</li>
                </ul>
              </div>
              <div id="mediumEceranAlert" class="text-success small mt-1 d-none p-2 bg-success-subtle rounded border border-success-subtle">
                <i class="bi bi-info-circle-fill me-1"></i> <strong>Beras Medium Eceran:</strong> HPP Rp <strong>10.500</strong> per Liter / Kg.
              </div>
              <div id="premiumEceranAlert" class="text-warning small mt-1 d-none p-2 bg-warning-subtle rounded border border-warning-subtle">
                <i class="bi bi-info-circle-fill me-1"></i> <strong>Beras Premium Eceran:</strong> HPP Rp <strong>11.300</strong> per Liter / Kg.
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Stok Awal</label>
              <input type="number" step="any" name="stok_awal" class="form-control" value="0" min="0">
              <small class="text-muted">Untuk Medium/Premium hasil giling, stok bisa ditambah via menu Giling Sendiri.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Harga Beli / HPP (Rp) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">Rp</span>
                <input type="text" inputmode="numeric" name="harga_beli" id="harga_beli" class="form-control fw-bold" placeholder="0" autocomplete="off" required oninput="formatRibuanInput(this)">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Harga Jual Konsumen (Rp) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">Rp</span>
                <input type="text" inputmode="numeric" name="harga_jual" id="harga_jual" class="form-control fw-bold text-primary" placeholder="0" autocomplete="off" required oninput="formatRibuanInput(this)">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Batas Stok Minimum</label>
              <input type="number" step="any" name="stok_minimum" class="form-control" value="10" required>
              <small class="text-muted">Peringatan otomatis muncul bila stok di bawah batas ini.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Keterangan / Catatan</label>
              <input type="text" name="keterangan" id="keterangan" class="form-control" placeholder="Catatan spesifikasi beras...">
            </div>

            <div class="col-12 text-end mt-4">
              <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                <i class="bi bi-check-lg me-1"></i> Simpan Data Produk
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function handleKategoriChange() {
  const selectKat = document.getElementById('id_kategori');
  const selectedOption = selectKat.options[selectKat.selectedIndex];
  const kode = selectedOption ? selectedOption.getAttribute('data-kode') : '';
  const satuanSelect = document.getElementById('satuan_pilih');
  const sphpAlert = document.getElementById('sphpAlertSatuan');
  const kodeInput = document.getElementById('kode_produk');
  const namaInput = document.querySelector('input[name="nama_produk"]');

  if (kode === 'SPHP') {
    sphpAlert.classList.remove('d-none');
    document.getElementById('mediumEceranAlert').classList.add('d-none');
    document.getElementById('premiumEceranAlert').classList.add('d-none');
    // Batasi pilihan ke kemasan 5kg dan per kg
    updateSatuanOptionsForSPHP(true);
    handleSatuanChange();
  } else {
    sphpAlert.classList.add('d-none');
    updateSatuanOptionsForSPHP(false);
    handleSatuanChange(); // trigger hint HPP Medium/Premium
  }
}

function updateSatuanOptionsForSPHP(isSPHP) {
  const satuanSelect = document.getElementById('satuan_pilih');
  const currentVal = satuanSelect.value;
  satuanSelect.innerHTML = '';

  if (isSPHP) {
    const opts = [
      { val: 'sak_5kg', label: 'Kemasan Sak 5 Kg (HET Rp 62.500)' },
      { val: 'kg', label: 'Per Kg - Eceran Curah (HET Rp 12.500)' }
    ];
    opts.forEach(o => {
      const opt = document.createElement('option');
      opt.value = o.val;
      opt.textContent = o.label;
      if (currentVal === o.val || (!currentVal && o.val === 'sak_5kg')) opt.selected = true;
      satuanSelect.appendChild(opt);
    });
  } else {
    const opts = [
      { val: 'sak_5kg', label: 'Kemasan Sak 5 Kg' },
      { val: 'sak_10kg', label: 'Kemasan Sak 10 Kg' },
      { val: 'sak_25kg', label: 'Kemasan Sak 25 Kg' },
      { val: 'sak_1kg', label: 'Kemasan 1 Kg' },
      { val: 'kg', label: 'Eceran Curah (Kg)' },
      { val: 'liter', label: 'Eceran Curah (Liter - 1 L = 0,753 Kg)' }
    ];
    opts.forEach(o => {
      const opt = document.createElement('option');
      opt.value = o.val;
      opt.textContent = o.label;
      if (currentVal === o.val) opt.selected = true;
      satuanSelect.appendChild(opt);
    });
  }
}

function handleSatuanChange() {
  const selectKat = document.getElementById('id_kategori');
  const selectedOption = selectKat.options[selectKat.selectedIndex];
  const kodeKat = selectedOption ? selectedOption.getAttribute('data-kode') : '';
  const satuanVal = document.getElementById('satuan_pilih').value;
  const hargaBeliInput = document.getElementById('harga_beli');
  const hargaJualInput = document.getElementById('harga_jual');
  const kodeInput = document.getElementById('kode_produk');
  const namaInput = document.querySelector('input[name="nama_produk"]');
  const mediumAlert = document.getElementById('mediumEceranAlert');
  const premiumAlert = document.getElementById('premiumEceranAlert');

  // Sembunyikan semua hint dulu
  if (mediumAlert) mediumAlert.classList.add('d-none');
  if (premiumAlert) premiumAlert.classList.add('d-none');

  if (kodeKat === 'SPHP') {
    if (satuanVal === 'kg') {
      hargaJualInput.value = '12.500';
      hargaBeliInput.value = '11.000';
      if (!kodeInput.value || kodeInput.value.startsWith('SPHP-')) {
        kodeInput.value = 'SPHP-1KG';
      }
      if (!namaInput.value || namaInput.value.includes('SPHP')) {
        namaInput.value = 'Beras SPHP BULOG (Per Kg)';
      }
    } else {
      hargaJualInput.value = '62.500';
      hargaBeliInput.value = '55.000';
      if (!kodeInput.value || kodeInput.value.startsWith('SPHP-')) {
        kodeInput.value = 'SPHP-5KG';
      }
      if (!namaInput.value || namaInput.value.includes('SPHP')) {
        namaInput.value = 'Beras SPHP BULOG 5 Kg';
      }
    }
  } else if (kodeKat === 'MEDIUM') {
    const isEceran = (satuanVal === 'liter' || satuanVal === 'kg');
    if (isEceran && mediumAlert) {
      mediumAlert.classList.remove('d-none');
      // Isi otomatis HPP eceran medium jika belum diisi
      if (!hargaBeliInput.value || hargaBeliInput.value === '0') {
        hargaBeliInput.value = '10.500';
      }
    }
  } else if (kodeKat === 'PREMIUM') {
    const isEceran = (satuanVal === 'liter' || satuanVal === 'kg');
    if (isEceran && premiumAlert) {
      premiumAlert.classList.remove('d-none');
      // Isi otomatis HPP eceran premium jika belum diisi
      if (!hargaBeliInput.value || hargaBeliInput.value === '0') {
        hargaBeliInput.value = '11.300';
      }
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
