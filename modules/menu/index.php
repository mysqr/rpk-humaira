<?php
// modules/menu/index.php - Manajemen Menu & Pengaturan Hak Akses Role
$page_title = 'Manajemen Menu & Hak Akses';
$active_menu = 'menu_manajemen';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

ensure_menu_table_exists();

// ==========================================
// PROSES AKSI FORM (TAMBAH, EDIT, HAPUS, TOGGLE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Tambah Menu Baru
    if ($action === 'tambah') {
        $kode_menu = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $_POST['kode_menu'] ?? '')));
        $nama_menu = escape(trim($_POST['nama_menu'] ?? ''));
        $url = escape(trim($_POST['url'] ?? ''));
        $icon = escape(trim($_POST['icon'] ?? 'bi-link-45deg'));
        $kategori = escape(trim($_POST['kategori'] ?? 'Menu Tambahan'));
        $urutan = (int)($_POST['urutan'] ?? 50);
        $akses_pemilik = isset($_POST['akses_pemilik']) ? 1 : 0;
        $akses_kasir = isset($_POST['akses_kasir']) ? 1 : 0;

        if (empty($kode_menu) || empty($nama_menu) || empty($url)) {
            set_flash('danger', 'Kode menu, nama menu, dan tautan URL wajib diisi!');
        } else {
            // Cek apakah kode sudah digunakan
            $cek = query_single("SELECT id_menu FROM app_menu WHERE kode_menu = '$kode_menu'");
            if ($cek) {
                set_flash('danger', "Kode menu <strong>$kode_menu</strong> sudah digunakan. Gunakan kode lain!");
            } else {
                $sql = "INSERT INTO app_menu (kode_menu, nama_menu, url, icon, kategori, urutan, akses_pemilik, akses_kasir, is_bawaan, is_active)
                        VALUES ('$kode_menu', '$nama_menu', '$url', '$icon', '$kategori', $urutan, $akses_pemilik, $akses_kasir, 0, 1)";
                if ($conn->query($sql)) {
                    set_flash('success', "Menu baru <strong>$nama_menu</strong> berhasil ditambahkan ke navigasi sistem!");
                } else {
                    set_flash('danger', 'Gagal menambahkan menu: ' . $conn->error);
                }
            }
        }
        header('Location: ' . BASE_URL . '/modules/menu/index.php');
        exit;
    }

    // 2. Ubah / Edit Menu
    if ($action === 'edit') {
        $id_menu = (int)($_POST['id_menu'] ?? 0);
        $nama_menu = escape(trim($_POST['nama_menu'] ?? ''));
        $url = escape(trim($_POST['url'] ?? ''));
        $icon = escape(trim($_POST['icon'] ?? 'bi-link-45deg'));
        $kategori = escape(trim($_POST['kategori'] ?? 'Menu Lainnya'));
        $urutan = (int)($_POST['urutan'] ?? 0);
        $akses_pemilik = isset($_POST['akses_pemilik']) ? 1 : 0;
        $akses_kasir = isset($_POST['akses_kasir']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $target = query_single("SELECT * FROM app_menu WHERE id_menu = $id_menu");
        if (!$target) {
            set_flash('danger', 'Menu tidak ditemukan!');
        } else {
            // Jika menu bawaan, URL dan kode tidak diubah demi integritas sistem
            $url_update = $target['is_bawaan'] == 1 ? $target['url'] : $url;
            $sql = "UPDATE app_menu SET 
                    nama_menu = '$nama_menu',
                    url = '$url_update',
                    icon = '$icon',
                    kategori = '$kategori',
                    urutan = $urutan,
                    akses_pemilik = $akses_pemilik,
                    akses_kasir = $akses_kasir,
                    is_active = $is_active
                    WHERE id_menu = $id_menu";
            if ($conn->query($sql)) {
                set_flash('success', "Perubahan data menu <strong>$nama_menu</strong> berhasil disimpan!");
            } else {
                set_flash('danger', 'Gagal memperbarui menu: ' . $conn->error);
            }
        }
        header('Location: ' . BASE_URL . '/modules/menu/index.php');
        exit;
    }

    // 3. Toggle Cepat Hak Akses Kasir (On/Off)
    if ($action === 'toggle_kasir') {
        $id_menu = (int)($_POST['id_menu'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        $conn->query("UPDATE app_menu SET akses_kasir = $status WHERE id_menu = $id_menu");
        
        // Response JSON jika via AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit;
        }
        set_flash('success', 'Hak akses kasir untuk menu terkait berhasil diperbarui!');
        header('Location: ' . BASE_URL . '/modules/menu/index.php');
        exit;
    }

    // 4. Hapus Menu (Khusus Menu Kustom)
    if ($action === 'hapus') {
        $id_menu = (int)($_POST['id_menu'] ?? 0);
        $menu = query_single("SELECT * FROM app_menu WHERE id_menu = $id_menu");
        if (!$menu) {
            set_flash('danger', 'Menu tidak ditemukan!');
        } elseif ($menu['is_bawaan'] == 1) {
            set_flash('danger', 'Menu bawaan sistem tidak dapat dihapus, namun Anda bisa menonaktifkannya.');
        } else {
            $conn->query("DELETE FROM app_menu WHERE id_menu = $id_menu");
            set_flash('success', "Menu <strong>{$menu['nama_menu']}</strong> berhasil dihapus dari navigasi.");
        }
        header('Location: ' . BASE_URL . '/modules/menu/index.php');
        exit;
    }
}

// Ambil semua daftar menu
$semua_menu = query("SELECT * FROM app_menu ORDER BY kategori ASC, urutan ASC, id_menu ASC");

// Ambil list kategori unik yang sudah ada untuk autocomplete dropdown
$kategori_list = query("SELECT DISTINCT kategori FROM app_menu WHERE kategori != '' ORDER BY kategori ASC");

// Hitung statistik
$total_menu = count($semua_menu);
$total_kasir = 0;
$total_kustom = 0;
foreach ($semua_menu as $m) {
    if ($m['akses_kasir'] == 1 && $m['is_active'] == 1) $total_kasir++;
    if ($m['is_bawaan'] == 0) $total_kustom++;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Manajemen Menu Navigasi & Hak Akses Role</h5>
    <p class="text-muted small mb-0">Atur menu apa saja yang boleh diakses oleh Kasir, serta tambahkan tautan atau menu kustom baru secara dinamis.</p>
  </div>
  <button type="button" class="btn btn-primary shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambahMenu">
    <i class="bi bi-plus-circle"></i>
    <span>+ Tambah Menu Baru</span>
  </button>
</div>

<!-- Ringkasan Statistik Menu -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="text-muted small fw-semibold">Total Menu Terdaftar</div>
      <h4 class="fw-bold text-dark mt-1 mb-0"><?= $total_menu ?> <span class="fs-6 text-muted fw-normal">Menu</span></h4>
      <small class="text-muted" style="font-size: 0.75rem;">Menu sistem bawaan & kustom</small>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card border-start border-4 border-success">
      <div class="text-muted small fw-semibold">Diizinkan untuk Kasir</div>
      <h4 class="fw-bold text-success mt-1 mb-0"><?= $total_kasir ?> <span class="fs-6 text-muted fw-normal">Menu</span></h4>
      <small class="text-muted" style="font-size: 0.75rem;">Tampil di sidebar Kasir</small>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card border-start border-4 border-primary">
      <div class="text-muted small fw-semibold">Khusus Admin / Pemilik</div>
      <h4 class="fw-bold text-primary mt-1 mb-0"><?= $total_menu - $total_kasir ?> <span class="fs-6 text-muted fw-normal">Menu</span></h4>
      <small class="text-muted" style="font-size: 0.75rem;">Menu manajerial & eksekutif</small>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card border-start border-4 border-warning">
      <div class="text-muted small fw-semibold">Menu Kustom Tambahan</div>
      <h4 class="fw-bold text-warning-emphasis mt-1 mb-0"><?= $total_kustom ?> <span class="fs-6 text-muted fw-normal">Menu</span></h4>
      <small class="text-muted" style="font-size: 0.75rem;">Dibuat secara mandiri</small>
    </div>
  </div>
</div>

<!-- Tabel Pengaturan Menu & Hak Akses -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-sliders2 text-primary fs-5"></i>
      <h6 class="mb-0 fw-bold">Daftar Menu & Pengaturan Izin Akses Kasir</h6>
    </div>
    <span class="badge bg-light text-dark border">
      <i class="bi bi-info-circle me-1"></i> Centang kolom "Kasir" untuk memberi izin akses
    </span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="text-center">Urut</th>
          <th>Nama Menu & Ikon</th>
          <th>Kategori Kelompok</th>
          <th>Tautan URL Target</th>
          <th class="text-center" style="width: 130px;">Tipe Menu</th>
          <th class="text-center" style="width: 120px;">Akses Kasir</th>
          <th class="text-center" style="width: 100px;">Status</th>
          <th class="text-center" style="width: 90px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($semua_menu)): ?>
          <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada menu terdaftar.</td></tr>
        <?php else: ?>
          <?php foreach ($semua_menu as $m): ?>
            <tr class="<?= $m['is_active'] ? '' : 'table-light text-muted' ?>">
              <td class="text-center font-monospace fw-bold text-muted small">
                <?= (int)$m['urutan'] ?>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-2 p-2 bg-light border text-primary d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi <?= htmlspecialchars($m['icon'] ?: 'bi-link-45deg') ?> fs-5"></i>
                  </div>
                  <div>
                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($m['nama_menu']) ?></span>
                    <small class="text-muted font-monospace" style="font-size: 0.72rem;"><?= htmlspecialchars($m['kode_menu']) ?></small>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-light text-secondary border fw-medium px-2 py-1">
                  <?= htmlspecialchars($m['kategori']) ?>
                </span>
              </td>
              <td class="small font-monospace text-secondary">
                <a href="<?= format_menu_url($m['url']) ?>" target="_blank" class="text-decoration-none text-truncate d-inline-block" style="max-width: 200px;" title="Buka Tautan: <?= htmlspecialchars($m['url']) ?>">
                  <?= htmlspecialchars($m['url']) ?> <i class="bi bi-box-arrow-up-right ms-1 text-muted" style="font-size: 0.7rem;"></i>
                </a>
              </td>
              <td class="text-center">
                <?php if ($m['is_bawaan'] == 1): ?>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <i class="bi bi-gear-fill me-1"></i>Sistem Bawaan
                  </span>
                <?php else: ?>
                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                    <i class="bi bi-star-fill me-1"></i>Kustom Baru
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <!-- Toggle Akses Kasir -->
                <div class="form-check form-switch d-inline-block">
                  <input class="form-check-input" type="checkbox" role="switch" 
                         id="switch_kasir_<?= $m['id_menu'] ?>"
                         <?= $m['akses_kasir'] == 1 ? 'checked' : '' ?>
                         onchange="ubahAksesKasir(<?= $m['id_menu'] ?>, this.checked)">
                </div>
              </td>
              <td class="text-center">
                <?php if ($m['is_active'] == 1): ?>
                  <span class="badge bg-success-subtle text-success">Aktif</span>
                <?php else: ?>
                  <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="d-inline-flex align-items-center gap-1">
                  <!-- Tombol Edit -->
                  <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" title="Edit Menu"
                          onclick='bukaModalEdit(<?= json_encode($m) ?>)'>
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <!-- Tombol Hapus (Khusus Menu Kustom) -->
                  <?php if ($m['is_bawaan'] == 0): ?>
                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus menu kustom <?= htmlspecialchars(addslashes($m['nama_menu'])) ?>?')">
                      <input type="hidden" name="action" value="hapus">
                      <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Hapus Menu">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ==============================================================
     MODAL TAMBAH MENU BARU
     ============================================================== -->
<div class="modal fade" id="modalTambahMenu" tabindex="-1" aria-labelledby="modalTambahMenuLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-primary text-white border-0 py-3">
        <h6 class="modal-title fw-bold" id="modalTambahMenuLabel">
          <i class="bi bi-plus-circle-fill me-2"></i>Tambah Menu Navigasi Baru
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="tambah">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Nama Menu <span class="text-danger">*</span></label>
              <input type="text" name="nama_menu" class="form-control" placeholder="Contoh: Katalog Online / Data Mitra" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Kode Unik Menu <span class="text-danger">*</span></label>
              <input type="text" name="kode_menu" class="form-control" placeholder="Contoh: katalog_online (huruf kecil & garis bawah)" required>
              <small class="text-muted" style="font-size: 0.72rem;">Hanya huruf, angka, dan garis bawah</small>
            </div>

            <div class="col-md-8">
              <label class="form-label fw-semibold small">Tautan / URL Tujuan <span class="text-danger">*</span></label>
              <input type="text" name="url" class="form-control" placeholder="Contoh: /modules/laporan/penjualan.php atau https://google.com" required>
              <small class="text-muted" style="font-size: 0.72rem;">Bisa link internal sistem atau link eksternal (https://...)</small>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Urutan Tampil</label>
              <input type="number" name="urutan" class="form-control" value="20" min="1">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Kategori Kelompok Sidebar</label>
              <input type="text" name="kategori" list="listKategori" class="form-control" placeholder="Pilih atau ketik kategori baru..." value="Menu Tambahan" required>
              <datalist id="listKategori">
                <?php foreach ($kategori_list as $kl): ?>
                  <option value="<?= htmlspecialchars($kl['kategori']) ?>">
                <?php endforeach; ?>
              </datalist>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Pilih Ikon Bootstrap Icons</label>
              <select name="icon" class="form-select" id="selectIconTambah" onchange="previewIcon(this.value, 'previewIconTambah')">
                <option value="bi-link-45deg">🔗 bi-link-45deg (Tautan)</option>
                <option value="bi-box-seam">📦 bi-box-seam (Kotak / Barang)</option>
                <option value="bi-cart4">🛒 bi-cart4 (Keranjang Belanja)</option>
                <option value="bi-receipt">🧾 bi-receipt (Struk Transaksi)</option>
                <option value="bi-file-earmark-text">📄 bi-file-earmark-text (Dokumen)</option>
                <option value="bi-graph-up-arrow">📈 bi-graph-up-arrow (Grafik)</option>
                <option value="bi-truck">🚚 bi-truck (Pengiriman / Mobil)</option>
                <option value="bi-people">👥 bi-people (Pengguna / Pelanggan)</option>
                <option value="bi-shop">🏬 bi-shop (Toko / Gudang)</option>
                <option value="bi-whatsapp">💬 bi-whatsapp (Kontak Chat)</option>
                <option value="bi-geo-alt">📍 bi-geo-alt (Lokasi)</option>
                <option value="bi-cash-coin">💰 bi-cash-coin (Uang / Kas)</option>
                <option value="bi-sliders">⚙️ bi-sliders (Pengaturan)</option>
                <option value="bi-globe">🌐 bi-globe (Web / Internet)</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small mb-2">Hak Akses Tampil Menu</label>
              <div class="p-3 bg-light rounded-3 border">
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="akses_pemilik" id="chkPemilikTambah" checked>
                  <label class="form-check-label fw-semibold" for="chkPemilikTambah">
                    <i class="bi bi-shield-lock text-primary me-1"></i> Tampilkan untuk Admin / Pemilik Toko
                  </label>
                </div>
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" name="akses_kasir" id="chkKasirTambah">
                  <label class="form-check-label fw-semibold" for="chkKasirTambah">
                    <i class="bi bi-person-badge text-success me-1"></i> Tampilkan untuk Petugas Kasir
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light py-2 px-4">
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm">
            <i class="bi bi-check-lg me-1"></i> Simpan & Pasang Menu
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==============================================================
     MODAL EDIT MENU
     ============================================================== -->
<div class="modal fade" id="modalEditMenu" tabindex="-1" aria-labelledby="modalEditMenuLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-dark text-white border-0 py-3">
        <h6 class="modal-title fw-bold" id="modalEditMenuLabel">
          <i class="bi bi-pencil-square me-2"></i>Ubah Pengaturan Menu: <span id="editNamaJudul">-</span>
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id_menu" id="editIdMenu" value="">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Nama Menu <span class="text-danger">*</span></label>
              <input type="text" name="nama_menu" id="editNamaMenu" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Kode Unik Menu</label>
              <input type="text" id="editKodeMenu" class="form-control bg-light" readonly>
            </div>

            <div class="col-md-8">
              <label class="form-label fw-semibold small">Tautan / URL Tujuan <span class="text-danger">*</span></label>
              <input type="text" name="url" id="editUrl" class="form-control" required>
              <small class="text-muted" id="editUrlNote" style="font-size: 0.72rem;"></small>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Urutan Tampil</label>
              <input type="number" name="urutan" id="editUrutan" class="form-control" min="1">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Kategori Kelompok Sidebar</label>
              <input type="text" name="kategori" id="editKategori" list="listKategori" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Pilih Ikon Bootstrap Icons</label>
              <input type="text" name="icon" id="editIcon" class="form-control" placeholder="Contoh: bi-cart-check">
              <small class="text-muted" style="font-size: 0.72rem;">Gunakan class ikon Bootstrap (misal: <code>bi-grid</code>, <code>bi-box</code>, dll.)</small>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small mb-2">Hak Akses & Status Tampil</label>
              <div class="p-3 bg-light rounded-3 border">
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="akses_pemilik" id="editAksesPemilik">
                  <label class="form-check-label fw-semibold" for="editAksesPemilik">
                    <i class="bi bi-shield-lock text-primary me-1"></i> Tampilkan untuk Admin / Pemilik Toko
                  </label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="akses_kasir" id="editAksesKasir">
                  <label class="form-check-label fw-semibold" for="editAksesKasir">
                    <i class="bi bi-person-badge text-success me-1"></i> Tampilkan untuk Petugas Kasir
                  </label>
                </div>
                <hr class="my-2">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                  <label class="form-check-label fw-semibold text-dark" for="editIsActive">
                    Status Menu Aktif (Jika tidak dicentang, menu disembunyikan sepenuhnya dari sistem)
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light py-2 px-4">
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm">
            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Fungsi Ubah Akses Kasir secara Cepat (AJAX)
function ubahAksesKasir(idMenu, status) {
  const formData = new FormData();
  formData.append('action', 'toggle_kasir');
  formData.append('id_menu', idMenu);
  formData.append('status', status ? 1 : 0);

  fetch('<?= BASE_URL ?>/modules/menu/index.php', {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(res => res.json())
  .then(data => {
    // Tampilkan notifikasi toast kecil
    console.log('Akses kasir updated:', data);
  })
  .catch(err => {
    console.error('Error updating menu access:', err);
  });
}

// Buka Modal Edit
function bukaModalEdit(m) {
  document.getElementById('editIdMenu').value = m.id_menu;
  document.getElementById('editNamaJudul').textContent = m.nama_menu;
  document.getElementById('editNamaMenu').value = m.nama_menu;
  document.getElementById('editKodeMenu').value = m.kode_menu;
  document.getElementById('editUrl').value = m.url;
  document.getElementById('editUrutan').value = m.urutan;
  document.getElementById('editKategori').value = m.kategori;
  document.getElementById('editIcon').value = m.icon;
  document.getElementById('editAksesPemilik').checked = parseInt(m.akses_pemilik) === 1;
  document.getElementById('editAksesKasir').checked = parseInt(m.akses_kasir) === 1;
  document.getElementById('editIsActive').checked = parseInt(m.is_active) === 1;

  const urlInput = document.getElementById('editUrl');
  const urlNote = document.getElementById('editUrlNote');
  if (parseInt(m.is_bawaan) === 1) {
    urlInput.setAttribute('readonly', 'readonly');
    urlInput.classList.add('bg-light');
    urlNote.textContent = 'Menu bawaan sistem: URL rute terkunci agar fungsionalitas sistem tidak terganggu.';
  } else {
    urlInput.removeAttribute('readonly');
    urlInput.classList.remove('bg-light');
    urlNote.textContent = 'Menu kustom: URL dapat Anda ubah bebas.';
  }

  const modalEl = document.getElementById('modalEditMenu');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
