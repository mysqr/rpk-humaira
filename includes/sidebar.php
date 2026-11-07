<?php
// includes/sidebar.php - Navigasi Dinamis Berdasarkan Hak Akses & Kustomisasi Menu
$active_menu = $active_menu ?? '';
$user_role = $_SESSION['user_role'] ?? 'kasir';
$is_admin = is_pemilik();
$user = current_user();

// Ambil menu dinamis dari database sesuai role
$menu_grouped = [];
if (function_exists('get_sidebar_menus_by_role')) {
    $menu_grouped = get_sidebar_menus_by_role($user_role);
}
?>
<aside class="sidebar no-print" id="appSidebar">
  <!-- Brand Header -->
  <div class="sidebar-brand d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <div class="logo-icon">
        <i class="bi bi-box-seam-fill"></i>
      </div>
      <div>
        <div class="text-white fw-bold" style="font-size: 1.1rem; line-height: 1.1;">RPK HUMAIRA</div>
        <div class="text-warning fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.02em;">MITRA RESMI BULOG</div>
      </div>
    </div>
    <button type="button" class="btn btn-sm btn-link text-white-50 d-lg-none p-0 text-decoration-none" id="sidebarCloseBtn" aria-label="Tutup Menu">
      <i class="bi bi-x-lg fs-5"></i>
    </button>
  </div>

  <!-- Role Indicator Badge -->
  <div class="px-3 pt-3 pb-2">
    <div class="p-2 rounded-3 text-center" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.07);">
      <div class="d-flex align-items-center justify-content-center gap-1">
        <span class="badge <?= $is_admin ? 'bg-primary' : 'bg-success' ?> px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.4px;">
          <i class="bi <?= $is_admin ? 'bi-shield-lock-fill' : 'bi-person-badge-fill' ?> me-1"></i>
          <?= $is_admin ? 'PORTAL ADMIN / PEMILIK' : 'PORTAL KASIR (PETUGAS)' ?>
        </span>
      </div>
      <small class="text-white-50 d-block mt-1" style="font-size: 0.7rem;">
        <?= $is_admin ? 'Akses Penuh Seluruh Sistem' : 'Pelayanan Transaksi & Stok' ?>
      </small>
    </div>
  </div>

  <!-- Menu Navigation (Dinamis dari Database) -->
  <nav class="sidebar-menu pt-1">
    <?php if (!empty($menu_grouped)): ?>
      <?php foreach ($menu_grouped as $kategori => $items): ?>
        <div class="menu-category"><?= htmlspecialchars($kategori) ?></div>
        <?php foreach ($items as $m): ?>
          <?php 
            $is_active_link = ($active_menu === $m['kode_menu']);
            $url_href = format_menu_url($m['url']);
            $is_external = (strpos($m['url'], 'http://') === 0 || strpos($m['url'], 'https://') === 0);
          ?>
          <a href="<?= $url_href ?>" <?= $is_external ? 'target="_blank"' : '' ?> class="sidebar-link <?= $is_active_link ? 'active' : '' ?>">
            <i class="bi <?= htmlspecialchars($m['icon'] ?: 'bi-link-45deg') ?>"></i>
            <span><?= htmlspecialchars($m['nama_menu']) ?></span>
            <?php if ($is_external): ?>
              <i class="bi bi-box-arrow-up-right ms-auto text-white-50" style="font-size: 0.65rem;"></i>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>

    <?php else: ?>
      <!-- Fallback Navigasi Statis jika Database Sedang Offline -->
      <div class="menu-category">Menu Utama</div>
      <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="sidebar-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2"></i>
        <span>Dashboard</span>
      </a>
      <a href="<?= BASE_URL ?>/modules/kasir/index.php" class="sidebar-link <?= $active_menu === 'kasir' ? 'active' : '' ?>">
        <i class="bi bi-cart-check"></i>
        <span>Kasir Penjualan (POS)</span>
      </a>
      <a href="<?= BASE_URL ?>/modules/kasir/riwayat.php" class="sidebar-link <?= $active_menu === 'riwayat_penjualan' ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i>
        <span>Riwayat Transaksi</span>
      </a>
      <a href="<?= BASE_URL ?>/modules/stok/index.php" class="sidebar-link <?= $active_menu === 'stok' ? 'active' : '' ?>">
        <i class="bi bi-boxes"></i>
        <span>Monitoring Stok</span>
      </a>
      <a href="<?= BASE_URL ?>/manual_book.php" class="sidebar-link <?= $active_menu === 'manual_book' ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark-fill text-warning"></i>
        <span>Buku Panduan</span>
      </a>
    <?php endif; ?>
  </nav>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer text-center">
    <div class="small text-white-50" style="font-size: 0.72rem;">
      <span class="fw-semibold text-white"><?= htmlspecialchars($user['nama_lengkap'] ?? 'Pengguna') ?></span><br>
      <span class="badge <?= $is_admin ? 'bg-primary' : 'bg-success' ?> mt-1" style="font-size: 0.65rem;">
        <?= strtoupper($user_role) ?>
      </span>
    </div>
  </div>
</aside>
