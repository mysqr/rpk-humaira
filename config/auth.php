<?php
// config/auth.php - Pengelolaan Autentikasi & Hak Akses (Role-based)
require_once __DIR__ . '/app.php';

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function check_role($allowed_roles = []) {
    check_login();
    $current_role = $_SESSION['user_role'] ?? '';
    $allowed = (array)$allowed_roles;
    if (in_array('pemilik', $allowed) && !in_array('admin', $allowed)) {
        $allowed[] = 'admin';
    }
    if (!in_array($current_role, $allowed)) {
        set_flash('danger', 'Akses ditolak! Anda tidak memiliki wewenang untuk membuka halaman tersebut.');
        header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
        exit;
    }
}

function is_pemilik() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['pemilik', 'admin']);
}

function is_admin() {
    return is_pemilik();
}

function is_kasir() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'kasir';
}

function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    global $conn;
    $uid = (int)$_SESSION['user_id'];
    if (isset($conn) && $conn instanceof mysqli) {
        $u = query_single("SELECT * FROM users WHERE id_user = $uid LIMIT 1");
        if ($u) {
            $_SESSION['nama_lengkap'] = $u['nama_lengkap'];
            $_SESSION['user_role'] = $u['role'];
            $_SESSION['user_hp'] = $u['no_hp'] ?? '';
            return $u;
        }
    }
    return [
        'id_user' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'role' => $_SESSION['user_role'],
        'no_hp' => $_SESSION['user_hp'] ?? ''
    ];
}
