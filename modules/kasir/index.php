<?php
// modules/kasir/index.php - Layar Kasir Point of Sale (POS)
$page_title = 'Kasir Penjualan Beras';
$active_menu = 'kasir';
require_once __DIR__ . '/../../config/auth.php';
check_login();

// Sinkronisasi berat konversi eceran (Massa jenis: 0.753 kg/liter, 1 liter = 0.753 kg, 1 kg ≈ 1.328 liter)
$conn->query("ALTER TABLE produk MODIFY COLUMN berat_kg DECIMAL(8,3) NOT NULL DEFAULT 5.000");
$conn->query("UPDATE produk SET berat_kg = 0.753 WHERE is_eceran = 1 AND satuan = 'Liter'");

// Ambil semua produk beras yang aktif beserta kategori
$produk_list = query("SELECT p.*, k.kode_kategori, k.nama_kategori 
    FROM produk p 
    JOIN kategori_beras k ON p.id_kategori = k.id_kategori 
    ORDER BY p.id_kategori ASC, p.is_eceran ASC, p.berat_kg ASC");

// Auto No Faktur: TRX-YYYYMMDD-XXX
$today_str = date('Ymd');
$cek_trx = query_single("SELECT no_faktur FROM penjualan WHERE no_faktur LIKE 'TRX-$today_str%' ORDER BY id_penjualan DESC LIMIT 1");
if ($cek_trx) {
    $last_num = (int)substr($cek_trx['no_faktur'], -3);
    $new_num = str_pad($last_num + 1, 3, '0', STR_PAD_LEFT);
} else {
    $new_num = '001';
}
$auto_faktur = "TRX-$today_str-$new_num";

$maks_sphp_harian = (int)($toko_info['maks_sphp_harian_per_orang'] ?? 5);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3">
  <!-- Kolom Kiri: Katalog & Pencarian Produk Beras -->
  <div class="col-lg-7 col-xl-8">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-body p-3">
        
        <!-- Bar Pencarian & Filter Tab Kategori -->
        <div class="row g-2 align-items-center mb-3">
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="searchProduct" class="form-control border-start-0" placeholder="Ketik nama / kode beras..." onkeyup="filterProducts()">
            </div>
          </div>
          <div class="col-md-7">
            <div class="d-flex flex-wrap gap-1" id="categoryTabs">
              <button type="button" class="btn btn-sm btn-primary active px-3" onclick="filterCategory('all', this)">Semua</button>
              <button type="button" class="btn btn-sm btn-outline-primary px-2" onclick="filterCategory('SPHP', this)">
                <i class="bi bi-shield-check me-1"></i>Beras SPHP
              </button>
              <button type="button" class="btn btn-sm btn-outline-success px-2" onclick="filterCategory('MEDIUM', this)">Medium</button>
              <button type="button" class="btn btn-sm btn-outline-secondary px-2" onclick="filterCategory('PREMIUM', this)">Premium</button>
              <button type="button" class="btn btn-sm btn-outline-warning text-dark px-2" onclick="filterCategory('ECERAN', this)">Eceran (Kg/Ltr)</button>
            </div>
          </div>
        </div>

        <!-- Grid Produk Beras -->
        <div class="pos-products-col" style="max-height: 600px; overflow-y: auto;">
          <div class="row g-2" id="productList">
            <?php foreach ($produk_list as $prod): ?>
              <?php
                $card_theme = 'medium-card';
                if ($prod['kode_kategori'] === 'SPHP') $card_theme = 'sphp-card';
                elseif ($prod['kode_kategori'] === 'PREMIUM') $card_theme = 'premium-card';

                $is_out_of_stock = $prod['stok'] <= 0;
              ?>
              <div class="col-sm-6 col-md-4 col-xl-3 product-item" 
                   data-kategori="<?= $prod['kode_kategori'] ?>" 
                   data-eceran="<?= $prod['is_eceran'] ? 'ECERAN' : 'KEMASAN' ?>"
                   data-nama="<?= strtolower(htmlspecialchars($prod['nama_produk'])) ?>"
                   data-kode="<?= strtolower(htmlspecialchars($prod['kode_produk'])) ?>">
                
                <div class="card h-100 pos-product-card <?= $card_theme ?> p-2 <?= $is_out_of_stock ? 'opacity-50' : '' ?>"
                     <?= !$is_out_of_stock ? 'onclick="addToCart(' . htmlspecialchars(json_encode($prod)) . ')"' : '' ?>>
                  
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <?php if ($prod['kode_kategori'] === 'SPHP'): ?>
                      <?php if ($prod['is_eceran']): ?>
                        <span class="badge badge-sphp" style="font-size: 0.65rem;">SPHP PER KG</span>
                      <?php else: ?>
                        <span class="badge badge-sphp" style="font-size: 0.65rem;">SPHP 5KG</span>
                      <?php endif; ?>
                    <?php elseif ($prod['kode_kategori'] === 'MEDIUM'): ?>
                      <span class="badge badge-medium" style="font-size: 0.65rem;">MEDIUM</span>
                    <?php else: ?>
                      <span class="badge badge-premium" style="font-size: 0.65rem;">PREMIUM</span>
                    <?php endif; ?>

                    <?php if ($prod['is_eceran']): ?>
                      <?php if ($prod['satuan'] === 'Kg'): ?>
                        <span class="badge badge-eceran" style="font-size: 0.65rem;"><i class="bi bi-basket me-1"></i>Per Kg</span>
                      <?php else: ?>
                        <span class="badge badge-eceran" style="font-size: 0.65rem;" title="1 Liter = 0,753 Kg | 1 Kg ≈ 1,328 Liter"><i class="bi bi-arrow-repeat me-1"></i>1 Ltr = 0,753 Kg</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="badge bg-light text-dark border" style="font-size: 0.65rem;"><?= htmlspecialchars($prod['satuan']) ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="fw-bold text-dark text-truncate mb-1" style="font-size: 0.85rem;" title="<?= htmlspecialchars($prod['nama_produk']) ?>">
                    <?= htmlspecialchars($prod['nama_produk']) ?>
                  </div>

                  <div class="d-flex justify-content-between align-items-baseline mt-auto">
                    <span class="fw-bold text-primary" style="font-size: 0.95rem;">
                      <?= rupiah($prod['harga_jual']) ?>
                    </span>
                    <small class="<?= $prod['stok'] <= $prod['stok_minimum'] ? 'text-danger fw-bold' : 'text-muted' ?>" style="font-size: 0.75rem;">
                      Stok: <?= number_format($prod['stok'], ($prod['is_eceran'] ? 1 : 0)) ?>
                    </small>
                  </div>

                  <?php if ($is_out_of_stock): ?>
                    <span class="badge bg-danger mt-1 text-center py-1">STOK HABIS</span>
                  <?php endif; ?>
                </div>

              </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Kolom Kanan: Keranjang Kasir & Panel Checkout -->
  <div class="col-lg-5 col-xl-4">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-2 border-bottom d-flex align-items-center justify-content-between">
        <div>
          <span class="fw-bold"><i class="bi bi-cart3 text-primary me-1"></i>Keranjang Kasir</span>
          <span class="badge bg-light text-dark border ms-1"><?= $auto_faktur ?></span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="clearCart()" title="Kosongkan Keranjang">
          <i class="bi bi-trash"></i> Reset
        </button>
      </div>

      <div class="card-body p-3">
        <!-- List Item Keranjang -->
        <div id="cartItemsContainer" style="max-height: 240px; overflow-y: auto; min-height: 120px;" class="mb-3">
          <div class="text-center py-4 text-muted small" id="emptyCartText">
            <i class="bi bi-cart-x fs-2 d-block mb-1 opacity-50"></i>
            Keranjang masih kosong.<br>Klik produk di sebelah kiri untuk menambahkan.
          </div>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/modules/kasir/proses.php" id="formCheckout" onsubmit="return validateCheckout(event)">
          <input type="hidden" name="no_faktur" value="<?= $auto_faktur ?>">
          <input type="hidden" name="cart_data" id="cartDataInput">

          <!-- Section Aturan Khusus Beras SPHP (Muncul Jika Ada SPHP di Cart) -->
          <div id="sphpValidationBox" class="p-3 bg-primary-subtle border border-primary rounded-3 mb-3 d-none">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center gap-1 text-primary fw-bold small">
                <i class="bi bi-person-vcard-fill fs-6"></i>
                <span>VERIFIKASI NIK/KTP PEMBELI SPHP BULOG</span>
              </div>
              <span class="badge bg-danger text-white shadow-sm" style="font-size: 0.7rem;"><i class="bi bi-shield-lock me-1"></i>Maks 25 Kg / NIK / Hari</span>
            </div>
            
            <div class="mb-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold mb-0">Nomor NIK / KTP Pembeli (16 Digit) <span class="text-danger">*</span></label>
                <span id="nikCounterBadge" class="badge bg-secondary" style="font-size: 0.75rem;">0 / 16 digit</span>
              </div>
              <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                <input type="text" inputmode="numeric" name="nik_pembeli" id="nikPembeli" class="form-control fw-bold" placeholder="Contoh: 3201xxxxxxxxxxxx" maxlength="16" autocomplete="off" oninput="handleNikInput(this)">
                <span class="input-group-text bg-white" id="nikStatusIcon"><i class="bi bi-dash-circle text-muted"></i></span>
              </div>
              <div id="kuotaStatusMsg" class="mt-1 small" style="font-size: 0.75rem;"></div>
            </div>

            <div class="row g-2 mb-2">
              <div class="col-7">
                <label class="form-label small fw-semibold mb-1">Nama Pembeli <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input type="text" name="nama_pembeli" id="namaPembeli" class="form-control" placeholder="Nama sesuai KTP">
                </div>
              </div>
              <div class="col-5">
                <label class="form-label small fw-semibold mb-1">No. HP / WA</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                  <input type="text" name="no_hp_pembeli" id="noHpPembeli" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
              </div>
            </div>

            <div class="form-check form-check-inline mt-1">
              <input class="form-check-input" type="checkbox" name="status_konsumen_akhir" id="checkKonsumenAkhir" value="1" checked required>
              <label class="form-check-label small fw-semibold" for="checkKonsumenAkhir">
                Konfirmasi: Pembeli adalah <u>Konsumen Akhir (Rumah Tangga)</u>, BUKAN Reseller/Pengecer.
              </label>
            </div>
          </div>

          <!-- Section Data Pembeli untuk Medium & Premium (Jika Tidak Ada SPHP) -->
          <div id="regularCustomerBox" class="mb-3">
            <div class="row g-2">
              <div class="col-6">
                <input type="text" name="nama_pembeli_umum" id="namaPembeliUmum" class="form-control form-control-sm" placeholder="Nama Pembeli (Opsional)">
              </div>
              <div class="col-6">
                <input type="text" name="no_hp_umum" id="noHpUmum" class="form-control form-control-sm" placeholder="No. HP (Opsional)">
              </div>
            </div>
          </div>

          <!-- Rincian Pembayaran Kasir -->
          <div class="bg-light p-3 rounded-3 border mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-muted small">Total Kuantiti Item:</span>
              <span class="fw-bold" id="totalQtyLabel">0</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold fs-6">Total Belanja:</span>
              <span class="fw-bold fs-5 text-primary" id="totalHargaLabel">Rp 0</span>
            </div>

            <!-- Input Pembayaran Tunai -->
            <div class="mb-2">
              <label class="form-label small fw-semibold mb-1">Uang Tunai Diterima (Rp) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">Rp</span>
                <input type="text" inputmode="numeric" name="uang_bayar" id="uangBayarInput" class="form-control fw-bold fs-6" placeholder="0" autocomplete="off" required oninput="handleUangBayarInput(this)">
              </div>
            </div>

            <!-- Tombol Cepat Nominal Uang -->
            <div class="d-flex flex-wrap gap-1 mb-2">
              <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="setUangPas()">Uang Pas</button>
              <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="addUang(50000)">50rb</button>
              <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="addUang(100000)">100rb</button>
              <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="addUang(200000)">200rb</button>
            </div>

            <!-- Kembalian -->
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <span class="fw-bold small">Kembalian:</span>
              <span class="fw-bold fs-6 text-success" id="kembalianLabel">Rp 0</span>
            </div>
          </div>

          <!-- Tombol Bayar & Selesai -->
          <button type="submit" id="btnCheckout" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" disabled>
            <i class="bi bi-printer me-1"></i> Bayar Tunai & Cetak Struk
          </button>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
// State Keranjang Belanja
let cart = [];
const maksSphpHarianKg = 25; // Maks 25 kg (setara 5 sak @ 5kg) per NIK per hari
let sphpQuotaRemainingKg = maksSphpHarianKg;
let sphpBoughtHariIniKg = 0;
let debounceTimer = null;

// Tambah Produk ke Cart
function addToCart(produk) {
  const prodId = parseInt(produk.id_produk);
  const existing = cart.find(item => parseInt(item.id_produk) === prodId);
  const step = produk.is_eceran == 1 ? 1 : 1; // eceran atau sak
  const beratItem = parseFloat(produk.berat_kg || (produk.satuan === 'Liter' ? 0.753 : (produk.is_eceran == 1 ? 1.0 : 5.0)));
  
  // Validasi batas maksimal SPHP 25 kg per NIK per hari
  if (produk.kode_kategori === 'SPHP') {
    let currentSphpKg = 0;
    cart.forEach(i => {
      if (i.kode_kategori === 'SPHP') currentSphpKg += (i.qty * (i.berat_kg || 1.0));
    });
    if (currentSphpKg + (step * beratItem) > maksSphpHarianKg) {
      alert(`Peringatan Regulasi SPHP:\nMaksimal pembelian Beras SPHP adalah 25 Kg per 1 NIK per hari!\nSaat ini di keranjang sudah ada: ${currentSphpKg.toFixed(1)} Kg.`);
      return;
    }
  }

  if (existing) {
    if (existing.qty + step > produk.stok) {
      alert('Stok produk ' + produk.nama_produk + ' tidak mencukupi (Tersisa: ' + produk.stok + ')');
      return;
    }
    existing.qty += step;
  } else {
    if (produk.stok < 1) {
      alert('Stok produk ' + produk.nama_produk + ' habis!');
      return;
    }
    cart.push({
      id_produk: prodId,
      kode_produk: produk.kode_produk,
      nama_produk: produk.nama_produk,
      kode_kategori: produk.kode_kategori,
      satuan: produk.satuan,
      berat_kg: beratItem,
      is_eceran: parseInt(produk.is_eceran),
      harga_beli: parseFloat(produk.harga_beli),
      harga_jual: parseFloat(produk.harga_jual),
      stok_max: parseFloat(produk.stok),
      qty: 1
    });
  }

  renderCart();
}

// Update Kuantiti di Cart
function updateQty(id_produk, delta) {
  const item = cart.find(i => parseInt(i.id_produk) === parseInt(id_produk));
  if (!item) return;

  const newQty = item.qty + delta;
  if (newQty <= 0) {
    removeFromCart(id_produk);
    return;
  }
  if (newQty > item.stok_max) {
    alert('Kuantiti melebihi stok yang tersedia (' + item.stok_max + ')');
    return;
  }

  // Validasi batas maksimal SPHP 25 kg
  if (item.kode_kategori === 'SPHP' && delta > 0) {
    let otherSphpKg = 0;
    cart.forEach(i => {
      if (i.kode_kategori === 'SPHP' && parseInt(i.id_produk) !== parseInt(id_produk)) {
        otherSphpKg += (i.qty * (i.berat_kg || 1.0));
      }
    });
    const itemTotalKg = newQty * (item.berat_kg || 1.0);
    if (otherSphpKg + itemTotalKg > maksSphpHarianKg) {
      alert(`Peringatan Regulasi SPHP:\nMaksimal pembelian Beras SPHP adalah 25 Kg per 1 NIK per hari!`);
      return;
    }
  }

  item.qty = newQty;
  renderCart();
}

function setExactQty(id_produk, value) {
  const item = cart.find(i => parseInt(i.id_produk) === parseInt(id_produk));
  if (!item) return;
  const val = parseFloat(value);
  if (isNaN(val) || val <= 0) {
    removeFromCart(id_produk);
    return;
  }
  if (val > item.stok_max) {
    alert('Kuantiti melebihi stok tersedia (' + item.stok_max + ')');
    item.qty = item.stok_max;
  } else {
    // Validasi batas maksimal SPHP 25 kg
    if (item.kode_kategori === 'SPHP') {
      let otherSphpKg = 0;
      cart.forEach(i => {
        if (i.kode_kategori === 'SPHP' && parseInt(i.id_produk) !== parseInt(id_produk)) {
          otherSphpKg += (i.qty * (i.berat_kg || 1.0));
        }
      });
      const itemTotalKg = val * (item.berat_kg || 1.0);
      if (otherSphpKg + itemTotalKg > maksSphpHarianKg) {
        alert(`Peringatan Regulasi SPHP:\nMaksimal pembelian Beras SPHP adalah 25 Kg per 1 NIK per hari!`);
        const maxBolehKg = Math.max(0, maksSphpHarianKg - otherSphpKg);
        const maxQtyBoleh = (item.berat_kg > 0) ? (maxBolehKg / item.berat_kg) : maxBolehKg;
        item.qty = Math.min(item.stok_max, maxQtyBoleh);
        renderCart();
        return;
      }
    }
    item.qty = val;
  }
  renderCart();
}

function removeFromCart(id_produk) {
  cart = cart.filter(i => parseInt(i.id_produk) !== parseInt(id_produk));
  renderCart();
}

function clearCart() {
  if (cart.length > 0 && confirm('Kosongkan keranjang belanja?')) {
    cart = [];
    renderCart();
  }
}

// Render Tampilan Keranjang
function renderCart() {
  const container = document.getElementById('cartItemsContainer');
  const emptyText = document.getElementById('emptyCartText');
  const cartDataInput = document.getElementById('cartDataInput');
  const sphpBox = document.getElementById('sphpValidationBox');
  const regularBox = document.getElementById('regularCustomerBox');
  const totalQtyLabel = document.getElementById('totalQtyLabel');
  const totalHargaLabel = document.getElementById('totalHargaLabel');
  const btnCheckout = document.getElementById('btnCheckout');

  if (cart.length === 0) {
    container.innerHTML = `
      <div class="text-center py-4 text-muted small" id="emptyCartText">
        <i class="bi bi-cart-x fs-2 d-block mb-1 opacity-50"></i>
        Keranjang masih kosong.<br>Klik produk di sebelah kiri untuk menambahkan.
      </div>`;
    totalQtyLabel.textContent = '0';
    totalHargaLabel.textContent = 'Rp 0';
    cartDataInput.value = '';
    btnCheckout.disabled = true;
    sphpBox.classList.add('d-none');
    regularBox.classList.remove('d-none');
    hitungKembalian();
    return;
  }

  let html = '';
  let totalQty = 0;
  let totalHarga = 0;
  let hasSPHP = false;
  let totalSphpKg = 0;

  cart.forEach(item => {
    const subtotal = item.qty * item.harga_jual;
    totalQty += item.qty;
    totalHarga += subtotal;

    if (item.kode_kategori === 'SPHP') {
      hasSPHP = true;
      totalSphpKg += (item.qty * (item.berat_kg || 1.0));
    }

    const stepAttr = item.is_eceran == 1 ? 'step="0.5"' : 'step="1"';
    const isEceran = item.is_eceran == 1;
    const isLiter = isEceran && item.satuan === 'Liter';
    const kgEquivalent = (item.qty * 0.753).toFixed(2);

    html += `
      <div class="cart-item">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <div class="fw-semibold text-truncate small" style="max-width: 60%;">
            ${item.nama_produk}
          </div>
          <div class="d-flex align-items-center gap-1">
            ${isLiter ? `<button type="button" class="btn btn-outline-warning btn-sm py-0 px-1 border" style="font-size: 0.7rem;" onclick="openKonversiModal(${item.id_produk}, '${encodeURIComponent(item.nama_produk)}', ${item.qty}, ${item.harga_jual}, ${item.stok_max})" title="Konversi Kg ⇄ Liter"><i class="bi bi-arrow-left-right me-1"></i>Kg ⇄ Ltr</button>` : ''}
            <button type="button" class="btn btn-link text-danger p-0 text-decoration-none" style="cursor: pointer;" onclick="removeFromCart(${item.id_produk})" title="Hapus dari keranjang">
              <i class="bi bi-x-circle fs-5"></i>
            </button>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="updateQty(${item.id_produk}, -1)">-</button>
            <input type="number" ${stepAttr} class="form-control form-control-sm text-center fw-bold px-1" style="width: 55px;" value="${item.qty}" min="0.1" onchange="setExactQty(${item.id_produk}, this.value)">
            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="updateQty(${item.id_produk}, 1)">+</button>
            <small class="text-muted ms-1">${item.satuan}</small>
            ${isLiter ? `<span class="badge bg-light text-dark border ms-1" style="font-size: 0.7rem;" title="1 Liter = 0,753 Kg | 1 Kg ≈ 1,328 Liter">≈ ${kgEquivalent} Kg</span>` : ''}
          </div>
          <div class="text-end">
            <div class="fw-bold text-dark small">${formatRupiah(subtotal)}</div>
            <small class="text-muted" style="font-size: 0.7rem;">@${formatRupiah(item.harga_jual)}</small>
          </div>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;
  totalQtyLabel.textContent = totalQty % 1 === 0 ? totalQty : totalQty.toFixed(1);
  totalHargaLabel.textContent = formatRupiah(totalHarga);
  cartDataInput.value = JSON.stringify(cart);

  // Cek Aturan SPHP
  if (hasSPHP) {
    sphpBox.classList.remove('d-none');
    regularBox.classList.add('d-none');
    checkSphpLimits(totalSphpKg);
  } else {
    sphpBox.classList.add('d-none');
    regularBox.classList.remove('d-none');
    btnCheckout.disabled = false;
  }

  hitungKembalian();
}

// Handler Input NIK: Auto-hitung digit, filter hanya angka, dan update status UI
function handleNikInput(el) {
  // Hanya simpan angka dan maksimal 16 digit
  el.value = el.value.replace(/[^0-9]/g, '').slice(0, 16);
  const len = el.value.length;
  const badge = document.getElementById('nikCounterBadge');
  const icon = document.getElementById('nikStatusIcon');

  if (len === 0) {
    if (badge) {
      badge.className = 'badge bg-secondary';
      badge.innerHTML = '0 / 16 digit';
    }
    if (icon) icon.innerHTML = '<i class="bi bi-dash-circle text-muted"></i>';
  } else if (len < 16) {
    const sisa = 16 - len;
    if (badge) {
      badge.className = 'badge bg-warning text-dark fw-bold';
      badge.innerHTML = `<i class="bi bi-hourglass-split me-1"></i>${len} / 16 digit (Kurang ${sisa})`;
    }
    if (icon) icon.innerHTML = `<span class="badge bg-warning text-dark" style="font-size: 0.65rem;">${len}/16</span>`;
  } else {
    // Tepat 16 digit
    if (badge) {
      badge.className = 'badge bg-success fw-bold';
      badge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>16 / 16 digit (Lengkap)`;
    }
    if (icon) icon.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-6"></i>';
  }

  // Hitung ulang batasan jika ada SPHP di cart
  let totalSphpKg = 0;
  cart.forEach(i => {
    if (i.kode_kategori === 'SPHP') totalSphpKg += (i.qty * (i.berat_kg || 1.0));
  });
  checkSphpLimits(totalSphpKg);

  // Jalankan debounce query kuota ke server
  debounceCekKuota();
}

// Validasi Batasan Kuota SPHP Berdasarkan NIK (Maks 25 Kg / setara 5 Sak per hari)
function checkSphpLimits(sphpKgInCart) {
  const btnCheckout = document.getElementById('btnCheckout');
  const msgEl = document.getElementById('kuotaStatusMsg');
  const nikInput = document.getElementById('nikPembeli');
  const nik = nikInput ? nikInput.value.trim() : '';
  const len = nik.length;

  if (len === 0) {
    msgEl.innerHTML = `<span class="text-warning"><i class="bi bi-info-circle"></i> Masukkan nomor NIK / KTP pembeli untuk cek kuota SPHP (Maks ${maksSphpHarianKg} Kg / 5 Sak per orang per hari).</span>`;
    btnCheckout.disabled = false;
    return;
  }

  if (len < 16) {
    const kurang = 16 - len;
    msgEl.innerHTML = `<span class="text-warning fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i> NIK masih <strong>${len} digit</strong>. Kurang <strong>${kurang} digit</strong> lagi untuk memenuhi syarat 16 digit KTP.</span>`;
    btnCheckout.disabled = false;
    return;
  }

  const sisaKg = sphpQuotaRemainingKg;
  const sudahKg = sphpBoughtHariIniKg;

  if (sphpKgInCart > sisaKg) {
    msgEl.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-x-octagon-fill me-1"></i> Kuota Hari Ini Terlampaui! NIK ini sudah membeli <strong>${sudahKg} Kg</strong> hari ini. Pembelian di keranjang (<strong>${sphpKgInCart.toFixed(1)} Kg</strong>) melebihi sisa kuota hari ini (<strong>${sisaKg} Kg</strong>). Batas maksimal <strong>${maksSphpHarianKg} Kg (5 Sak)</strong> per orang per hari.</span>`;
    btnCheckout.disabled = true;
  } else {
    msgEl.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> NIK Terverifikasi: Sudah beli <strong>${sudahKg} Kg</strong> hari ini. Sisa kuota hari ini: <strong>${sisaKg} Kg</strong> dari maks ${maksSphpHarianKg} Kg (setara 5 Sak)/orang/hari.</span>`;
    btnCheckout.disabled = false;
  }
}

// Debounce Cek Kuota Nomor NIK via AJAX
function debounceCekKuota() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    const nik = document.getElementById('nikPembeli').value.trim();
    if (nik.length < 10) return;

    fetch('<?= BASE_URL ?>/modules/kasir/cek_kuota_sphp.php?nik=' + encodeURIComponent(nik))
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          sphpBoughtHariIniKg = data.sudah_beli_kg !== undefined ? parseFloat(data.sudah_beli_kg) : 0;
          sphpQuotaRemainingKg = data.sisa_kuota_kg !== undefined ? parseFloat(data.sisa_kuota_kg) : (maksSphpHarianKg - sphpBoughtHariIniKg);
          if (data.nama_pembeli && !document.getElementById('namaPembeli').value) {
            document.getElementById('namaPembeli').value = data.nama_pembeli;
          }
          if (data.no_hp && !document.getElementById('noHpPembeli').value) {
            document.getElementById('noHpPembeli').value = data.no_hp;
          }
          // Hitung ulang jika ada produk SPHP di cart
          let totalSphpKg = 0;
          cart.forEach(i => {
            if (i.kode_kategori === 'SPHP') totalSphpKg += (i.qty * (i.berat_kg || 1.0));
          });
          checkSphpLimits(totalSphpKg);
        }
      })
      .catch(err => console.error(err));
  }, 400);
}

// Format angka ribuan dengan pemisah titik (misal: 375000 -> 375.000)
function formatRibuan(angka) {
  if (angka === null || angka === undefined || angka === '') return '';
  const numStr = angka.toString().replace(/[^0-9]/g, '');
  if (!numStr) return '';
  return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function parseRibuan(str) {
  if (!str) return 0;
  return parseInt(String(str).replace(/[^0-9]/g, ''), 10) || 0;
}

function handleUangBayarInput(el) {
  const raw = parseRibuan(el.value);
  el.value = raw > 0 ? formatRibuan(raw) : '';
  hitungKembalian();
}

// Hitung Uang Kembalian
function hitungKembalian() {
  let total = 0;
  cart.forEach(i => total += (i.qty * i.harga_jual));
  const bayar = parseRibuan(document.getElementById('uangBayarInput').value);
  const kembali = bayar - total;
  const kembaliEl = document.getElementById('kembalianLabel');

  if (bayar >= total && total > 0) {
    kembaliEl.textContent = formatRupiah(kembali);
    kembaliEl.className = 'fw-bold fs-6 text-success';
  } else if (total > 0 && bayar > 0) {
    kembaliEl.textContent = 'Kurang ' + formatRupiah(total - bayar);
    kembaliEl.className = 'fw-bold fs-6 text-danger';
  } else {
    kembaliEl.textContent = 'Rp 0';
    kembaliEl.className = 'fw-bold fs-6 text-muted';
  }
}

function setUangPas() {
  let total = 0;
  cart.forEach(i => total += (i.qty * i.harga_jual));
  document.getElementById('uangBayarInput').value = total > 0 ? formatRibuan(total) : '';
  hitungKembalian();
}

function addUang(amount) {
  const current = parseRibuan(document.getElementById('uangBayarInput').value);
  const totalBaru = current + amount;
  document.getElementById('uangBayarInput').value = formatRibuan(totalBaru);
  hitungKembalian();
}

// Filter Kategori Tab
function filterCategory(cat, btn) {
  document.querySelectorAll('#categoryTabs button').forEach(b => {
    b.classList.remove('btn-primary', 'active');
    b.classList.add('btn-outline-primary', 'btn-outline-success', 'btn-outline-secondary', 'btn-outline-warning');
  });
  btn.classList.add('btn-primary', 'active');

  const items = document.querySelectorAll('.product-item');
  items.forEach(item => {
    const k = item.getAttribute('data-kategori');
    const e = item.getAttribute('data-eceran');
    if (cat === 'all') {
      item.classList.remove('d-none');
    } else if (cat === 'ECERAN') {
      if (e === 'ECERAN') item.classList.remove('d-none');
      else item.classList.add('d-none');
    } else {
      if (k === cat && e !== 'ECERAN') item.classList.remove('d-none');
      else item.classList.add('d-none');
    }
  });
}

// Filter Pencarian Teks
function filterProducts() {
  const query = document.getElementById('searchProduct').value.toLowerCase();
  const items = document.querySelectorAll('.product-item');
  items.forEach(item => {
    const nama = item.getAttribute('data-nama');
    const kode = item.getAttribute('data-kode');
    if (nama.includes(query) || kode.includes(query)) {
      item.classList.remove('d-none');
    } else {
      item.classList.add('d-none');
    }
  });
}

// Validasi Form Checkout Sebelum Submit
function validateCheckout(e) {
  if (cart.length === 0) {
    alert('Keranjang belanja masih kosong!');
    return false;
  }

  let total = 0;
  let hasSPHP = false;
  let totalSphpKg = 0;
  cart.forEach(i => {
    total += (i.qty * i.harga_jual);
    if (i.kode_kategori === 'SPHP') {
      hasSPHP = true;
      totalSphpKg += (i.qty * (i.berat_kg || 1.0));
    }
  });

  const bayar = parseRibuan(document.getElementById('uangBayarInput').value);
  if (bayar < total) {
    alert('Uang pembayaran tunai masih kurang! Total belanja: ' + formatRupiah(total) + ', uang diterima: ' + formatRupiah(bayar));
    return false;
  }

  if (hasSPHP) {
    const nik = document.getElementById('nikPembeli').value.trim();
    const nama = document.getElementById('namaPembeli').value.trim();
    const checkAkhir = document.getElementById('checkKonsumenAkhir').checked;

    if (!nik) {
      alert('Untuk pembelian Beras SPHP, Nomor NIK / KTP Pembeli WAJIB diisi guna pemantauan kuota harian BULOG (Maksimal 25 Kg/hari)!');
      document.getElementById('nikPembeli').focus();
      return false;
    }

    if (nik.length < 16) {
      alert('Nomor NIK / KTP Pembeli harus 16 digit angka sesuai KTP resmi!');
      document.getElementById('nikPembeli').focus();
      return false;
    }

    if (!nama) {
      alert('Nama Pembeli Beras SPHP WAJIB diisi sesuai KTP!');
      document.getElementById('namaPembeli').focus();
      return false;
    }

    if (!checkAkhir) {
      alert('Beras SPHP HANYA diperuntukkan bagi Konsumen Akhir (Rumah Tangga), TIDAK BOLEH untuk reseller/pengecer!');
      return false;
    }

    if (totalSphpKg > sphpQuotaRemainingKg) {
      alert(`Transaksi DITOLAK!\nAkumulasi pembelian Beras SPHP untuk NIK ini melebihi batas maksimal 25 Kg per hari!\nSisa kuota hari ini: ${sphpQuotaRemainingKg.toFixed(1)} Kg, sedangkan di keranjang ada ${totalSphpKg.toFixed(1)} Kg.`);
      return false;
    }
  }

  return true;
}

// Logika Modal Kalkulator Konversi Beras Eceran (1 Liter = 0,753 Kg | 1 Kg ≈ 1,328 Liter)
let currentKonversiProdId = null;
let currentKonversiHarga = 0;
let currentKonversiStokMax = 0;

function openKonversiModal(id_produk, nama_produk, qtySaatIni, hargaJual, stokMax) {
  currentKonversiProdId = parseInt(id_produk);
  currentKonversiHarga = parseFloat(hargaJual || 0);
  currentKonversiStokMax = parseFloat(stokMax || 9999);

  document.getElementById('konversiNamaProduk').textContent = decodeURIComponent(nama_produk);
  document.getElementById('konversiInfoHarga').textContent = 'Harga: ' + formatRupiah(currentKonversiHarga) + '/Liter | Stok Tersedia: ' + currentKonversiStokMax + ' Liter';

  const liter = parseFloat(qtySaatIni) || 1;
  document.getElementById('inputKonversiLiter').value = liter;
  document.getElementById('inputKonversiKg').value = (liter * 0.753).toFixed(2);
  document.getElementById('konversiSubtotalPreview').textContent = formatRupiah(liter * currentKonversiHarga);

  const modalEl = new bootstrap.Modal(document.getElementById('modalKonversiEceran'));
  modalEl.show();
}

function hitungDariKg() {
  const kg = parseFloat(document.getElementById('inputKonversiKg').value);
  if (!isNaN(kg) && kg > 0) {
    // 1 kg beras = sekitar 1.328 liter (atau kg / 0.753)
    const liter = Math.round((kg * 1.328) * 100) / 100;
    document.getElementById('inputKonversiLiter').value = liter;
    document.getElementById('konversiSubtotalPreview').textContent = formatRupiah(liter * currentKonversiHarga);
  } else {
    document.getElementById('inputKonversiLiter').value = '';
    document.getElementById('konversiSubtotalPreview').textContent = 'Rp 0';
  }
}

function hitungDariLiter() {
  const liter = parseFloat(document.getElementById('inputKonversiLiter').value);
  if (!isNaN(liter) && liter > 0) {
    // 1 liter beras = 0.753 kg
    const kg = (liter * 0.753).toFixed(2);
    document.getElementById('inputKonversiKg').value = kg;
    document.getElementById('konversiSubtotalPreview').textContent = formatRupiah(liter * currentKonversiHarga);
  } else {
    document.getElementById('inputKonversiKg').value = '';
    document.getElementById('konversiSubtotalPreview').textContent = 'Rp 0';
  }
}

function terapkanKonversi() {
  const liter = parseFloat(document.getElementById('inputKonversiLiter').value);
  if (isNaN(liter) || liter <= 0) {
    alert('Masukkan kuantiti yang valid!');
    return;
  }
  if (liter > currentKonversiStokMax) {
    alert('Kuantiti melebihi stok tersedia (' + currentKonversiStokMax + ' Liter)');
    return;
  }

  setExactQty(currentKonversiProdId, liter);
  const modalEl = bootstrap.Modal.getInstance(document.getElementById('modalKonversiEceran'));
  if (modalEl) modalEl.hide();
}
</script>

<!-- Modal Kalkulator Konversi Beras Eceran (1 Liter = 0,753 Kg | 1 Kg ≈ 1,328 Liter) -->
<div class="modal fade" id="modalKonversiEceran" tabindex="-1" aria-labelledby="modalKonversiLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-warning text-dark border-0 py-3">
        <h6 class="modal-title fw-bold" id="modalKonversiLabel">
          <i class="bi bi-arrow-left-right me-2"></i>Kalkulator Konversi Beras Eceran
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
          <div>
            <strong>Standar Konversi Beras:</strong><br>
            • Massa jenis beras: <strong>0,753 kg/liter</strong><br>
            • <strong>1 liter beras: 0,753 kg</strong><br>
            • <strong>1 kg beras: Sekitar 1,328 liter</strong>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold small text-dark mb-1" id="konversiNamaProduk">Produk Beras</label>
          <div class="text-muted small" id="konversiInfoHarga">Harga per Liter: Rp 0 | Sisa Stok: 0 Ltr</div>
        </div>

        <div class="card bg-light border p-3 rounded-3 mb-3">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label small fw-semibold text-primary mb-1">
                <i class="bi bi-speedometer2 me-1"></i>Berat (Kilogram / Kg)
              </label>
              <div class="input-group input-group-sm">
                <input type="number" step="0.01" min="0.01" class="form-control fw-bold text-center" id="inputKonversiKg" placeholder="Contoh: 1" oninput="hitungDariKg()">
                <span class="input-group-text bg-white fw-semibold">Kg</span>
              </div>
              <small class="text-muted" style="font-size: 0.72rem;">Jika pembeli minta Kg (× 1,328)</small>
            </div>

            <div class="col-6">
              <label class="form-label small fw-semibold text-success mb-1">
                <i class="bi bi-cup-straw me-1"></i>Volume (Liter / Ltr)
              </label>
              <div class="input-group input-group-sm">
                <input type="number" step="0.01" min="0.01" class="form-control fw-bold text-center text-success" id="inputKonversiLiter" placeholder="Contoh: 1" oninput="hitungDariLiter()">
                <span class="input-group-text bg-white fw-semibold">Liter</span>
              </div>
              <small class="text-muted" style="font-size: 0.72rem;">Kuantiti tercatat POS (× 0,753)</small>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center p-2 bg-white border rounded-2 mb-2">
          <span class="small text-muted">Estimasi Subtotal:</span>
          <span class="fw-bold fs-6 text-primary" id="konversiSubtotalPreview">Rp 0</span>
        </div>
      </div>
      <div class="modal-footer border-0 bg-light py-2 px-4">
        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm" onclick="terapkanKonversi()">
          <i class="bi bi-check2-circle me-1"></i> Terapkan ke Keranjang
        </button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
