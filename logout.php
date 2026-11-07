<?php
// logout.php - Proses Keluar Sistem
require_once __DIR__ . '/config/app.php';

session_unset();
session_destroy();

session_start();
set_flash('info', 'Anda telah berhasil keluar dari sistem.');
header('Location: ' . BASE_URL . '/login.php');
exit;
