<?php
// modules/users/index.php - Manajemen Pengguna Sistem (Pemilik & Kasir)
$page_title = 'Manajemen Pengguna Sistem';
$active_menu = 'users';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']); // Hanya pemilik toko yang berwenang mengelola pengguna

$users = query("SELECT * FROM users ORDER BY role ASC, id_user ASC");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Manajemen Pengguna & Hak Akses Sistem</h5>
    <p class="text-muted small mb-0">Atur wewenang pengguna: <strong>Pemilik</strong> (otorisasi penuh & persetujuan pengadaan) dan <strong>Kasir</strong> (pelayanan transaksi & input giling).</p>
  </div>
  <a href="<?= BASE_URL ?>/modules/users/tambah.php" class="btn btn-primary px-3 shadow-sm d-flex align-items-center gap-2">
    <i class="bi bi-person-plus-fill"></i>
    <span class="fw-semibold">Tambah Pengguna Baru</span>
  </a>
</div>

<!-- Tabel Pengguna -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table table-hover table-custom mb-0 align-middle">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Lengkap</th>
          <th>Username</th>
          <th>Hak Akses (Role)</th>
          <th>Wewenang Utama</th>
          <th>No. HP</th>
          <th>Status</th>
          <th class="text-center" style="width: 120px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($users as $u): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td>
              <div class="fw-bold text-dark"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
              <small class="text-muted">Terdaftar: <?= date('d/m/Y', strtotime($u['created_at'])) ?></small>
            </td>
            <td>
              <code>@<?= htmlspecialchars($u['username']) ?></code>
            </td>
            <td>
              <?php if ($u['role'] === 'pemilik'): ?>
                <span class="badge bg-primary px-2 py-1"><i class="bi bi-shield-lock me-1"></i>PEMILIK</span>
              <?php else: ?>
                <span class="badge bg-success px-2 py-1"><i class="bi bi-person me-1"></i>KASIR</span>
              <?php endif; ?>
            </td>
            <td class="small">
              <?php if ($u['role'] === 'pemilik'): ?>
                <span class="text-primary fw-semibold"><i class="bi bi-check-circle me-1"></i>Persetujuan Pengadaan BULOG, Laporan Omzet, Kelola Produk & User</span>
              <?php else: ?>
                <span class="text-muted"><i class="bi bi-check me-1"></i>Input Transaksi POS, Cetak Struk, Catat Giling, Monitoring Stok</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($u['no_hp'] ?: '-') ?></td>
            <td>
              <?php if ($u['is_active']): ?>
                <span class="badge bg-success-subtle text-success">Aktif</span>
              <?php else: ?>
                <span class="badge bg-danger-subtle text-danger">Non-Aktif</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <a href="<?= BASE_URL ?>/modules/users/edit.php?id=<?= $u['id_user'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Akun">
                <i class="bi bi-pencil-square"></i> Edit
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
