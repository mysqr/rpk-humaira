<?php
// modules/kasir/proses.php - Proses Simpan Transaksi Penjualan & Pemotongan Stok
require_once __DIR__ . '/../../config/auth.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/kasir/index.php');
    exit;
}

$cart_json = $_POST['cart_data'] ?? '';
$cart = json_decode($cart_json, true);

if (empty($cart) || !is_array($cart)) {
    set_flash('danger', 'Keranjang belanja kosong!');
    header('Location: ' . BASE_URL . '/modules/kasir/index.php');
    exit;
}

$no_faktur = escape($_POST['no_faktur'] ?? '');
$raw_uang_bayar = preg_replace('/[^0-9]/', '', (string)($_POST['uang_bayar'] ?? '0'));
$uang_bayar = (float)$raw_uang_bayar;
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Cek apakah ada Beras SPHP
$ada_sphp = 0;
$total_sphp_kg = 0;
$total_harga = 0;
$total_kuantiti = 0;

foreach ($cart as $item) {
    $subtotal = $item['qty'] * $item['harga_jual'];
    $total_harga += $subtotal;
    $total_kuantiti += $item['qty'];

    if ($item['kode_kategori'] === 'SPHP') {
        $ada_sphp = 1;
        $berat_satuan = (float)($item['berat_kg'] ?? 1.0);
        $total_sphp_kg += ($item['qty'] * $berat_satuan);
    }
}

// Cek Pembayaran
if ($uang_bayar < $total_harga) {
    set_flash('danger', 'Jumlah pembayaran tunai kurang dari total belanja!');
    header('Location: ' . BASE_URL . '/modules/kasir/index.php');
    exit;
}
$uang_kembali = $uang_bayar - $total_harga;

// Data Pembeli
$nik_pembeli = '';
$nama_pembeli = '';
$no_hp_pembeli = '';
$status_konsumen_akhir = 1;

if ($ada_sphp == 1) {
    $nik_pembeli = escape($_POST['nik_pembeli'] ?? '');
    $no_hp_pembeli = escape($_POST['no_hp_pembeli'] ?? '');
    $nama_pembeli = escape($_POST['nama_pembeli'] ?? '');
    $status_konsumen_akhir = isset($_POST['status_konsumen_akhir']) ? 1 : 0;

    // VALIDASI SERVER-SIDE ATURAN SPHP BERDASARKAN NIK:
    if (empty($nik_pembeli) || empty($nama_pembeli)) {
        set_flash('danger', 'Untuk pembelian beras SPHP BULOG, Nomor NIK/KTP dan Nama pembeli WAJIB diisi!');
        header('Location: ' . BASE_URL . '/modules/kasir/index.php');
        exit;
    }

    if (strlen($nik_pembeli) < 16) {
        set_flash('danger', 'Nomor NIK/KTP pembeli harus 16 digit sesuai identitas resmi KTP!');
        header('Location: ' . BASE_URL . '/modules/kasir/index.php');
        exit;
    }

    if ($status_konsumen_akhir != 1) {
        set_flash('danger', 'Beras SPHP hanya boleh dibeli oleh Konsumen Akhir, dilarang untuk reseller/pengecer!');
        header('Location: ' . BASE_URL . '/modules/kasir/index.php');
        exit;
    }

    // Cek batas kuota HARIAN per NIK: maks 25 kg (setara 5 sak @ 5kg) per hari
    $maks_harian_kg = 25.0; // 25 kg per NIK per hari
    $cek_sphp = query_single("SELECT COALESCE(SUM(pd.jumlah * pr.berat_kg), 0) as total_kg_sudah_beli 
        FROM penjualan p 
        JOIN penjualan_detail pd ON p.id_penjualan = pd.id_penjualan 
        JOIN produk pr ON pd.id_produk = pr.id_produk 
        JOIN kategori_beras kb ON pr.id_kategori = kb.id_kategori 
        WHERE kb.kode_kategori = 'SPHP' 
        AND p.nik_pembeli = '$nik_pembeli'
        AND DATE(p.tanggal_penjualan) = '$today'");

    $sudah_beli_kg = (float)($cek_sphp['total_kg_sudah_beli'] ?? 0);
    $total_akumulasi_kg = $sudah_beli_kg + $total_sphp_kg;

    if ($total_akumulasi_kg > $maks_harian_kg) {
        $sisa_boleh_kg = max(0, $maks_harian_kg - $sudah_beli_kg);
        set_flash('danger', "Transaksi GAGAL! NIK/KTP <strong>$nik_pembeli</strong> sudah membeli <strong>{$sudah_beli_kg} Kg</strong> beras SPHP hari ini. Pembelian saat ini (<strong>{$total_sphp_kg} Kg</strong>) melebihi batas regulasi <strong>{$maks_harian_kg} Kg (5 Sak)</strong> per orang per hari. Sisa kuota hari ini hanya <strong>{$sisa_boleh_kg} Kg</strong>.");
        header('Location: ' . BASE_URL . '/modules/kasir/index.php');
        exit;
    }
} else {
    // Non-SPHP
    $nik_pembeli = '';
    $nama_pembeli = escape($_POST['nama_pembeli_umum'] ?? 'Pembeli Umum');
    $no_hp_pembeli = escape($_POST['no_hp_umum'] ?? '');
}

// SIMPAN TRANSAKSI KE DATABASE (TRANSACTION)
$conn->begin_transaction();
try {
    // 1. Insert ke tabel penjualan
    $sql_penjualan = "INSERT INTO penjualan (no_faktur, tanggal_penjualan, total_item, total_kuantiti, total_harga, uang_bayar, uang_kembali, metode_pembayaran, ada_sphp, nik_pembeli, nama_pembeli, no_hp_pembeli, status_konsumen_akhir, id_user)
                      VALUES ('$no_faktur', '$now', " . count($cart) . ", $total_kuantiti, $total_harga, $uang_bayar, $uang_kembali, 'TUNAI', $ada_sphp, '$nik_pembeli', '$nama_pembeli', '$no_hp_pembeli', $status_konsumen_akhir, $user_id)";
    $conn->query($sql_penjualan);
    $id_penjualan = $conn->insert_id;

    // 2. Loop detail produk, cek stok, potong stok, catat kartu stok
    foreach ($cart as $item) {
        $id_produk = (int)$item['id_produk'];
        $qty = (float)$item['qty'];
        $harga_beli = (float)$item['harga_beli'];
        $harga_satuan = (float)$item['harga_jual'];
        $subtotal = $qty * $harga_satuan;
        $laba = ($harga_satuan - $harga_beli) * $qty;

        // Cek stok real-time saat ini
        $p_db = query_single("SELECT stok, nama_produk, satuan FROM produk WHERE id_produk = $id_produk FOR UPDATE");
        if (!$p_db || $p_db['stok'] < $qty) {
            throw new Exception("Stok untuk {$item['nama_produk']} tidak mencukupi saat proses penyimpanan!");
        }

        $stok_sebelum = (float)$p_db['stok'];
        $stok_sesudah = $stok_sebelum - $qty;

        // Insert penjualan_detail
        $conn->query("INSERT INTO penjualan_detail (id_penjualan, id_produk, jumlah, harga_beli, harga_satuan, subtotal, laba)
                      VALUES ($id_penjualan, $id_produk, $qty, $harga_beli, $harga_satuan, $subtotal, $laba)");

        // Update stok produk
        $conn->query("UPDATE produk SET stok = $stok_sesudah WHERE id_produk = $id_produk");

        // Insert ke riwayat_stok (mutasi keluar)
        $ket_stok = "Penjualan Faktur $no_faktur (" . ($nama_pembeli ?: 'Umum') . ")";
        if ($p_db['satuan'] === 'Liter') {
            $kg_eq = round($qty * 0.753, 2);
            $ket_stok .= " ($qty Ltr ≈ $kg_eq Kg)";
        }
        $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                      VALUES ($id_produk, 'KELUAR', 'PENJUALAN', '$no_faktur', $qty, $stok_sebelum, $stok_sesudah, '$ket_stok', $user_id)");
    }

    $conn->commit();
    set_flash('success', "Transaksi faktur <strong>$no_faktur</strong> berhasil diselesaikan!");
    header("Location: " . BASE_URL . "/modules/kasir/struk.php?id=$id_penjualan");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    set_flash('danger', 'Terjadi kesalahan sistem saat menyimpan transaksi: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/modules/kasir/index.php');
    exit;
}
