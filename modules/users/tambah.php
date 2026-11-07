<?php
// modules/users/tambah.php - Tambah Pengguna Baru
$page_title = 'Tambah Pengguna Baru';
$active_menu = 'users';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim(escape($_POST['username'] ?? '')));
    $password = $_POST['password'] ?? '';
    $nama_lengkap = escape($_POST['nama_lengkap'] ?? '');
    $role = escape($_POST['role'] ?? 'kasir');
    $no_hp = escape($_POST['no_hp'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        set_flash('danger', 'Username, password, dan nama lengkap wajib diisi!');
    } else {
        $cek = query_single("SELECT id_user FROM users WHERE username = '$username'");
        if ($cek) {
            set_flash('danger', "Username <strong>@$username</strong> sudah digunakan, silakan pilih username lain!");
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO users (username, password, nama_lengkap, role, no_hp, is_active)
                    VALUES ('$username', '$hash', '$nama_lengkap', '$role', '$no_hp', $is_active)";
            if ($conn->query($sql)) {
                set_flash('success', "Pengguna baru <strong>$nama_lengkap</strong> berhasil ditambahkan!");
                header('Location: ' . BASE_URL . '/modules/users/index.php');
                exit;
            } else {
                set_flash('danger', 'Gagal menambahkan user: ' . $conn->error);
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Tambah Pengguna Baru</h6>
        <a href="<?= BASE_URL ?>/modules/users/index.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Budi Prasetyo" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Username <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control" placeholder="username" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required minlength="6">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Peran / Hak Akses <span class="text-danger">*</span></label>
              <select name="role" class="form-select" required>
                <option value="kasir">Kasir (Transaksi & Giling)</option>
                <option value="pemilik">Pemilik (Akses Penuh & Approval)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Nomor HP</label>
              <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx">
            </div>
          </div>

          <div class="mb-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
              <label class="form-check-label fw-semibold small" for="isActive">Status Akun Aktif</label>
            </div>
          </div>

          <div class="text-end">
            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
              <i class="bi bi-check-lg me-1"></i> Simpan Pengguna
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
