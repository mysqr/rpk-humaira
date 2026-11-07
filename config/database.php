<?php
// config/database.php - Koneksi Database MySQLi (Multi-Environment: Local & Hosting)

// Deteksi apakah sedang berjalan di Localhost atau di Web Hosting
$host_header = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (
    in_array($host_header, ['localhost', '127.0.0.1']) || 
    strpos($host_header, 'localhost:') === 0 || 
    strpos($host_header, '127.0.0.1:') === 0 ||
    (php_sapi_name() === 'cli' && empty($host_header))
);

if ($is_local) {
    // ==========================================
    // 1. PENGATURAN LOKAL (XAMPP)
    // ==========================================
    $db_host = '127.0.0.1';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'db_rpk_humaira';
} else {
    // ==========================================
    // 2. PENGATURAN LIVE HOSTING (cPanel / VPS)
    // ==========================================
    // Silakan sesuaikan dengan kredensial MySQL di cPanel Anda
    $db_host = 'localhost';             // Biasanya tetap 'localhost' di cPanel
    $db_user = 'USER_DATABASE_HOSTING'; // Ganti dengan username database hosting
    $db_pass = 'PASSWORD_DB_HOSTING';   // Ganti dengan password database hosting
    $db_name = 'NAMA_DATABASE_HOSTING'; // Ganti dengan nama database hosting
}

// Koneksi MySQLi
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<div style='font-family: sans-serif; padding: 20px; border: 1px solid #f5c6cb; background: #f8d7da; color: #721c24; border-radius: 6px; max-width: 600px; margin: 40px auto;'>"
        . "<h3 style='margin-top: 0;'>Koneksi Database Gagal!</h3>"
        . "<p>Pastikan nama host, user, password, dan nama database pada <code>config/database.php</code> sudah sesuai dengan konfigurasi database hosting Anda.</p>"
        . "<small>Pesan Error: " . htmlspecialchars($conn->connect_error) . "</small>"
        . "</div>");
}
$conn->set_charset("utf8mb4");

// Helper Query MySQLi
function query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Query Error: " . $conn->error . "<br>SQL: " . $sql);
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function query_single($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Query Error: " . $conn->error . "<br>SQL: " . $sql);
    }
    return $result->fetch_assoc();
}

function escape($string) {
    global $conn;
    return $conn->real_escape_string(trim($string));
}
