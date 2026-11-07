<?php
// includes/header.php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}
$page_title = $page_title ?? 'Sistem RPK Humaira';
$active_menu = $active_menu ?? '';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> - RPK Humaira</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
  </style>
</head>
<body>

<div class="app-wrapper">
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <!-- Backdrop Overlay for Mobile -->
  <div class="sidebar-backdrop no-print" id="sidebarBackdrop"></div>

  <!-- Main Content Wrapper -->
  <div class="main-content">
    <!-- Top Navbar -->
    <header class="top-navbar no-print">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggle" type="button">
          <i class="bi bi-list fs-5"></i>
        </button>
        <div>
          <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($page_title) ?></h5>
          <small class="text-muted d-none d-md-inline"><?= htmlspecialchars($toko_info['mitra'] ?? 'RPK Humaira') ?></small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-3">
        <!-- Quick Link POS Kasir -->
        <a href="<?= BASE_URL ?>/modules/kasir/index.php" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-2">
          <i class="bi bi-cart4"></i>
          <span class="d-none d-sm-inline fw-semibold">Kasir POS</span>
        </a>

        <!-- User Dropdown -->
        <div class="dropdown">
          <button class="btn btn-light btn-sm border dropdown-toggle d-flex align-items-center gap-2 px-2 py-1" type="button" data-bs-toggle="dropdown">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 12px; font-weight: bold;">
              <?= strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="text-start d-none d-md-block" style="line-height: 1.2;">
              <span class="d-block fw-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($user['nama_lengkap'] ?? 'Pengguna') ?></span>
              <span class="badge <?= ($user['role'] ?? '') === 'pemilik' ? 'bg-primary' : 'bg-success' ?>" style="font-size: 0.65rem;">
                <?= strtoupper($user['role'] ?? 'KASIR') ?>
              </span>
            </div>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li><h6 class="dropdown-header">Halo, <?= htmlspecialchars($user['nama_lengkap'] ?? '') ?></h6></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/profil.php"><i class="bi bi-person me-2"></i> Profil & Toko</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/manual_book.php" target="_blank"><i class="bi bi-book me-2"></i> Buku Panduan</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
          </ul>
        </div>
      </div>
    </header>

    <!-- Content Body -->
    <main class="content-body">
      <?php require_once __DIR__ . '/alerts.php'; ?>
