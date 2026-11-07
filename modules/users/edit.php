<?php
// modules/users/edit.php - Edit Pengguna Sistem
$page_title = 'Ubah Data Pengguna';
$active_menu = 'users';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

$id_user = (int)($_GET['id'] ?? 0);
$user_edit = query_single("SELECT * FROM users WHERE id_user = $id_user");

if (!$user_edit) {
    set_flash('danger', 'Data pengguna tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/users/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = escape($_POST['nama_lengkap'] ?? '');
    $role = escape($_POST['role'] ?? 'kasir');
    $no_hp = escape($_POST['no_hp'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password_baru = $_POST['password_baru'] ?? '';

    $pass_query = "";
    if (!empty($password_baru)) {
        if (strlen($password_baru) < 6) {
            set_flash('danger', 'Password baru minimal 6 karakter!');
            header("Location: " . BASE_URL . "/modules/users/edit.php?id=$id_user");
            exit;
        }
        $hash = password_hash($password_baru, PASSWORD_BCRYPT);
        $pass_query = ", password = '$hash'";
    }

    $sql = "UPDATE users SET 
            nama_lengkap = '$nama_lengkap',
            role = '$role',
            no_hp = '$no_hp',
            is_active = $is_active
            $pass_query
            WHERE id_user = $id_user";

    if ($conn->query($sql)) {
        if ($id_user == ($_SESSION['user_id'] ?? 0)) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['user_role'] = $role;
            $_SESSION['user_hp'] = $no_hp;
        }
        if ($role === 'pemilik') {
            $conn->query("UPDATE pengaturan SET nama_pemilik = '$nama_lengkap' WHERE id = 1");
        }
        set_flash('success', "Data pengguna <strong>$nama_lengkap</strong> berhasil diperbarui!");
        header('Location: ' . BASE_URL . '/modules/users/index.php');
        exit;
    } else {
        set_flash('danger', 'Gagal memperbarui pengguna: ' . $conn->error);
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Pengguna: <?= htmlspecialchars($user_edit['nama_lengkap']) ?></h6>
        <a href="<?= BASE_URL ?>/modules/users/index.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label fw-semibold small">Username</label>
            <input type="text" class="form-control bg-light" value="@<?= htmlspecialchars($user_edit['username']) ?>" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user_edit['nama_lengkap']) ?>" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Peran / Hak Akses <span class="text-danger">*</span></label>
              <select name="role" class="form-select" required>
                <option value="kasir" <?= $user_edit['role'] === 'kasir' ? 'selected' : '' ?>>Kasir (Transaksi & Giling)</option>
                <option value="pemilik" <?= $user_edit['role'] === 'pemilik' ? 'selected' : '' ?>>Pemilik (Akses Penuh & Approval)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Nomor HP</label>
              <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user_edit['no_hp'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Reset Password (Kosongkan jika tidak ingin diubah)</label>
            <input type="password" name="password_baru" class="form-control" placeholder="Masukkan password baru">
          </div>

          <div class="mb-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" <?= $user_edit['is_active'] ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold small" for="isActive">Status Akun Aktif</label>
            </div>
          </div>

          <div class="text-end">
            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
              <i class="bi bi-save me-1"></i> Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
