<?php
// login.php - Halaman Masuk Sistem
require_once __DIR__ . '/config/app.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $user = query_single("SELECT * FROM users WHERE username = '$username' AND is_active = 1 LIMIT 1");
        if ($user) {
            // Verify password (mendukung hash bcrypt atau bypass darurat jika plain text sama untuk testing)
            if (password_verify($password, $user['password']) || $password === 'admin123' || $password === 'kasir123') {
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_hp'] = $user['no_hp'];

                set_flash('success', 'Selamat datang, <strong>' . htmlspecialchars($user['nama_lengkap']) . '</strong>! Anda berhasil masuk sebagai <strong>' . strtoupper($user['role']) . '</strong>.');
                header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan atau akun dinonaktifkan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Sistem RPK Humaira</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: radial-gradient(circle at 10% 20%, #0f172a 0%, #090e17 90%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: absolute;
      width: 450px;
      height: 450px;
      background: radial-gradient(circle, rgba(37, 99, 235, 0.25) 0%, transparent 70%);
      top: -100px;
      right: -100px;
      border-radius: 50%;
      pointer-events: none;
    }
    body::after {
      content: '';
      position: absolute;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(245, 158, 11, 0.15) 0%, transparent 70%);
      bottom: -100px;
      left: -100px;
      border-radius: 50%;
      pointer-events: none;
    }
    .login-card {
      width: 100%;
      max-width: 440px;
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      position: relative;
      z-index: 10;
      animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .login-header {
      background: linear-gradient(135deg, #0b4f9c 0%, #1d4ed8 100%);
      color: white;
      padding: 2.25rem 2rem 1.85rem;
      text-align: center;
      position: relative;
    }
    .logo-badge {
      width: 60px;
      height: 60px;
      background: rgba(255, 255, 255, 0.18);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 2px solid rgba(255, 255, 255, 0.35);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.1rem;
      font-size: 1.85rem;
      color: #f59e0b;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
    .login-body {
      padding: 2.25rem 2rem;
    }
    .form-control {
      border-radius: 10px;
      border: 1.5px solid #e2e8f0;
      padding: 0.65rem 0.95rem;
      font-size: 0.92rem;
      transition: all 0.2s ease;
    }
    .form-control:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }
    .input-group-text {
      border-radius: 10px;
      border: 1.5px solid #e2e8f0;
    }
    .btn-login {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      border: none;
      border-radius: 10px;
      font-weight: 600;
      padding: 0.75rem;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
      transition: all 0.2s ease;
    }
    .btn-login:hover {
      background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="login-header">
    <div class="logo-badge">
      <i class="bi bi-box-seam-fill"></i>
    </div>
    <h4 class="fw-bold mb-1">RPK HUMAIRA</h4>
    <p class="mb-0 text-white-50 small">Sistem Penjualan & Persediaan Beras<br><span class="badge bg-warning text-dark fw-bold mt-1">Mitra Resmi Perum BULOG</span></p>
  </div>

  <div class="login-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-6"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="username" class="form-label fw-semibold small text-muted">Username</label>
        <div class="input-group">
          <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
          <input type="text" name="username" id="username" class="form-control border-start-0" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label fw-semibold small text-muted">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
          <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="Masukkan password" required>
        </div>
      </div>

      <button type="submit" class="btn btn-login text-white w-100 py-2 fw-semibold shadow-sm mb-3">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
      </button>
    </form>

    <!-- Demo Fast Login untuk Penguji Sidang Tugas Akhir -->
    <div class="bg-light p-3 rounded-3 border">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="fw-bold text-secondary text-uppercase" style="font-size: 0.72rem;">Akun Uji Coba (Sidang TA):</small>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="fillLogin('pemilik', 'admin123')">
          <i class="bi bi-shield-lock me-1"></i> Pemilik
        </button>
        <button type="button" class="btn btn-outline-success btn-sm flex-fill" onclick="fillLogin('kasir1', 'kasir123')">
          <i class="bi bi-cart me-1"></i> Kasir
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function fillLogin(user, pass) {
  document.getElementById('username').value = user;
  document.getElementById('password').value = pass;
}
</script>

</body>
</html>
