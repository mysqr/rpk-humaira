<?php
// modules/pengadaan_sphp/detail.php - Dokumen Resmi Order Penjualan BULOG & Persetujuan (Approve) Stok
$page_title = 'Order Penjualan SPHP - Perum BULOG';
$active_menu = 'pengadaan_sphp';
require_once __DIR__ . '/../../config/auth.php';
check_login();

// Cek dan tambahkan kolom tambahan jika belum ada di database
$check_gudang = query("SHOW COLUMNS FROM pengadaan_sphp LIKE 'gudang_bulog'");
if (empty($check_gudang)) {
    $conn->query("ALTER TABLE pengadaan_sphp ADD COLUMN gudang_bulog VARCHAR(100) NULL DEFAULT 'KOMPLEKS PERGUDANGAN BOJONGHERANG'");
}
$check_syarat = query("SHOW COLUMNS FROM pengadaan_sphp LIKE 'syarat_pembayaran'");
if (empty($check_syarat)) {
    $conn->query("ALTER TABLE pengadaan_sphp ADD COLUMN syarat_pembayaran VARCHAR(50) NULL DEFAULT 'Tunai'");
}
$check_metode = query("SHOW COLUMNS FROM pengadaan_sphp LIKE 'metode_pembayaran'");
if (empty($check_metode)) {
    $conn->query("ALTER TABLE pengadaan_sphp ADD COLUMN metode_pembayaran VARCHAR(50) NULL DEFAULT 'Transfer'");
}
$check_rek = query("SHOW COLUMNS FROM pengadaan_sphp LIKE 'rekening_tujuan'");
if (empty($check_rek)) {
    $conn->query("ALTER TABLE pengadaan_sphp ADD COLUMN rekening_tujuan VARCHAR(100) NULL DEFAULT 'BRI-010501002479306'");
}

$id_pengadaan = (int)($_GET['id'] ?? 0);
$pengadaan = query_single("SELECT p.*, pr.nama_produk, pr.satuan, pr.stok as stok_saat_ini, 
                                  u.nama_lengkap as pembuat, ua.nama_lengkap as penyetuju
    FROM pengadaan_sphp p 
    JOIN produk pr ON p.id_produk = pr.id_produk 
    LEFT JOIN users u ON p.id_user_created = u.id_user
    LEFT JOIN users ua ON p.id_user_approved = ua.id_user
    WHERE p.id_pengadaan = $id_pengadaan");

if (!$pengadaan) {
    set_flash('danger', 'Data pengadaan tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/index.php');
    exit;
}

// PROSES APPROVE (PENERIMAAN BARANG & TAMBAH STOK OTOMATIS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_pengadaan') {
    check_role(['pemilik']);

    if ($pengadaan['status'] === 'selesai') {
        set_flash('info', 'Pengadaan ini sudah disetujui (Approved) sebelumnya.');
        header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/detail.php?id=' . $id_pengadaan);
        exit;
    }

    $conn->begin_transaction();
    try {
        $user_id = (int)$_SESSION['user_id'];
        $now = date('Y-m-d H:i:s');
        $id_produk = (int)$pengadaan['id_produk'];
        $jumlah_sak = (int)$pengadaan['jumlah_sak'];
        $total_kg = (float)$pengadaan['total_berat_kg'];
        $no_so = !empty($pengadaan['no_ref_bulog']) ? $pengadaan['no_ref_bulog'] : $pengadaan['no_pengadaan'];
        $no_ref_mitra = $pengadaan['no_pengadaan'];

        // 1. Kunci baris produk beras SPHP untuk konsistensi stok
        $p_db = query_single("SELECT stok, nama_produk, satuan FROM produk WHERE id_produk = $id_produk FOR UPDATE");
        if (!$p_db) {
            throw new Exception("Produk Beras SPHP tidak ditemukan!");
        }

        $stok_sebelum = (float)$p_db['stok'];
        $stok_sesudah = $stok_sebelum + $jumlah_sak;

        // 2. Tambah stok beras SPHP di tabel produk
        $conn->query("UPDATE produk SET stok = $stok_sesudah WHERE id_produk = $id_produk");

        // 3. Update status pengadaan menjadi 'selesai' (approved)
        $conn->query("UPDATE pengadaan_sphp SET 
                      status = 'selesai',
                      tanggal_disetujui = CURDATE(),
                      tanggal_bayar = CURDATE(),
                      tanggal_ambil = CURDATE(),
                      tanggal_diterima = '$now',
                      id_user_approved = $user_id,
                      catatan = CONCAT(COALESCE(catatan, ''), '\n[Approved & Stok Masuk: $jumlah_sak Sak ($total_kg Kg) pada $now]')
                      WHERE id_pengadaan = $id_pengadaan");

        // 4. Catat ke riwayat_stok (mutasi MASUK)
        $ket_stok = "Penerimaan Order Penjualan SPHP BULOG ($no_so) [+$jumlah_sak Sak / $total_kg Kg]";
        $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                      VALUES ($id_produk, 'MASUK', 'PENGADAAN', '$no_ref_mitra', $jumlah_sak, $stok_sebelum, $stok_sesudah, '$ket_stok', $user_id)");

        $conn->commit();
        set_flash('success', "Order Penjualan BULOG <strong>$no_so</strong> BERHASIL DISETUJUI (APPROVED)! Stok beras SPHP toko otomatis bertambah <strong>+" . number_format($jumlah_sak) . " Sak (" . number_format($total_kg) . " Kg)</strong>.");
        header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/detail.php?id=' . $id_pengadaan);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        set_flash('danger', 'Gagal memproses persetujuan stok: ' . $e->getMessage());
        header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/detail.php?id=' . $id_pengadaan);
        exit;
    }
}

// Data Pendukung Dokumen
$no_so_tampil = !empty($pengadaan['no_ref_bulog']) ? $pengadaan['no_ref_bulog'] : ('SO/8052/' . date('m/Y', strtotime($pengadaan['tanggal_order'])) . '/10020');
$cabang_tampil = !empty($pengadaan['cabang_bulog']) ? $pengadaan['cabang_bulog'] : '10020 - KANCAB CIANJUR';
$gudang_tampil = !empty($pengadaan['gudang_bulog']) ? $pengadaan['gudang_bulog'] : 'KOMPLEKS PERGUDANGAN BOJONGHERANG';
$rek_tampil = !empty($pengadaan['rekening_tujuan']) ? $pengadaan['rekening_tujuan'] : (!empty($pengadaan['no_rekening']) ? $pengadaan['no_rekening'] : 'BRI-010501002479306');
$syarat_tampil = !empty($pengadaan['syarat_pembayaran']) ? $pengadaan['syarat_pembayaran'] : 'Tunai';
$metode_tampil = !empty($pengadaan['metode_pembayaran']) ? $pengadaan['metode_pembayaran'] : 'Transfer';
$tgl_dipesan_tampil = date('d/m/Y', strtotime($pengadaan['tanggal_order']));
$waktu_cetak = date('d-m-Y | H:i', strtotime($pengadaan['created_at']));

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Styling Lembar Order Penjualan BULOG */
.bulog-paper-wrapper {
  background-color: #f1f5f9;
  padding: 20px 0;
}

.bulog-order-sheet {
  background: #ffffff;
  width: 100%;
  max-width: 820px;
  margin: 0 auto;
  padding: 40px 48px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.08);
  border: 1px solid #e2e8f0;
  color: #111827;
  font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
  font-size: 11pt;
  line-height: 1.35;
}

.bulog-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 9.5pt;
  margin-top: 15px;
}

.bulog-table th, .bulog-table td {
  padding: 6px 8px;
  vertical-align: top;
}

.bulog-table th {
  border-top: 1px solid #111;
  border-bottom: 1px solid #111;
  font-weight: bold;
}

.bulog-table tbody tr td {
  border-bottom: 1px solid #e5e7eb;
}

.bulog-meta-label {
  font-weight: 600;
  font-size: 9pt;
  color: #1f2937;
  margin-bottom: 1px;
}

.bulog-meta-value {
  font-size: 9.5pt;
  color: #111;
  margin-bottom: 8px;
}

@media print {
  body {
    background: #fff !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  .no-print, nav, .navbar, .sidebar, footer, .main-footer {
    display: none !important;
  }
  .main-content {
    margin: 0 !important;
    padding: 0 !important;
  }
  .bulog-paper-wrapper {
    background: transparent !important;
    padding: 0 !important;
  }
  .bulog-order-sheet {
    box-shadow: none !important;
    border: none !important;
    max-width: 100% !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  @page {
    size: A4 portrait;
    margin: 12mm 15mm;
  }
}
</style>

<!-- Action Bar (Hanya Tampil di Layar, Sembunyi Saat Cetak) -->
<div class="no-print mb-4">
  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/index.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
        <span class="text-muted">|</span>
        <span class="fw-bold text-dark">No. Order BULOG:</span>
        <span class="badge bg-light text-primary border font-monospace fs-6"><?= htmlspecialchars($no_so_tampil) ?></span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <!-- Tombol Cetak Dokumen -->
        <button type="button" class="btn btn-outline-dark btn-sm shadow-sm" onclick="window.print()">
          <i class="bi bi-printer me-1"></i> Cetak Dokumen Order Penjualan
        </button>

        <!-- Tombol APPROVE / STATUS -->
        <?php if ($pengadaan['status'] !== 'selesai'): ?>
          <?php if (is_pemilik()): ?>
            <form method="POST" action="" onsubmit="return confirm('APPROVE PENGADAAN SPHP:\n\nApakah Anda yakin ingin menyetujui penerimaan beras SPHP sebanyak <?= number_format($pengadaan['jumlah_sak']) ?> Sak (<?= number_format($pengadaan['total_berat_kg']) ?> Kg)?\n\nStok beras SPHP di toko akan OTOMATIS BERTAMBAH.');">
              <input type="hidden" name="action" value="approve_pengadaan">
              <button type="submit" class="btn btn-success btn-sm fw-bold shadow-sm px-3 py-1">
                <i class="bi bi-check-circle-fill me-1"></i> APPROVE & TAMBAH STOK (+<?= number_format($pengadaan['jumlah_sak']) ?> SAK)
              </button>
            </form>
          <?php else: ?>
            <span class="badge bg-warning text-dark px-3 py-2">Menunggu Approve Pemilik Toko</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="badge bg-success fs-6 px-3 py-2 shadow-sm">
            <i class="bi bi-check2-circle me-1"></i> ORDER APPROVED (STOK SUDAH MASUK)
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Lembar Order Penjualan BULOG (Identik dengan format cetak resmi BULOG) -->
<div class="bulog-paper-wrapper">
  <div class="bulog-order-sheet">
    
    <!-- Header: Logo BULOG (Kiri) & Alamat Mitra RPK (Kanan) -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 22px;">
      <!-- Kiri: Logo Resmi BULOG & Cabang -->
      <div style="width: 48%;">
        <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
          <!-- SVG Logo Bulog Khas Biru Teal & Aksen Daun Hijau -->
          <svg width="150" height="42" viewBox="0 0 170 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Teks "bulog" lowercase khas -->
            <text x="2" y="36" font-family="'Trebuchet MS', Arial, sans-serif" font-size="38" font-weight="900" fill="#0095a8" letter-spacing="-1">bulog</text>
            <!-- Aksen daun hijau khas di atas huruf g -->
            <path d="M142 10 C146 5, 155 4, 158 8 C158 13, 150 18, 142 16 Z" fill="#78be20"/>
            <path d="M140 13 C144 11, 150 12, 152 15 C150 17, 145 17, 140 14 Z" fill="#4a9b18"/>
          </svg>
        </div>
        <div style="font-size: 9pt; font-weight: bold; color: #1f2937; letter-spacing: 0.5px;">
          <?= htmlspecialchars($cabang_tampil) ?>
        </div>
      </div>

      <!-- Kanan: QR Code & Alamat Mitra RPK Humaira -->
      <div style="width: 50%; text-align: right;">
        <!-- QR Code Standar Validasi Dokumen -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 8px;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=64x64&data=<?= urlencode(htmlspecialchars($no_so_tampil . '|RPK-HUMAIRA|' . $pengadaan['total_biaya'])) ?>" 
               alt="QR Code" width="60" height="60" style="border: 1px solid #ddd; padding: 2px;"
               onerror="this.style.display='none'; document.getElementById('qrFallback').style.display='inline-block';">
          <div id="qrFallback" style="display: none; border: 1px solid #111; padding: 4px; font-size: 7pt; font-family: monospace;">
            [QR BULOG]<br><?= htmlspecialchars(substr($no_so_tampil, -8)) ?>
          </div>
        </div>

        <div style="font-size: 8pt; line-height: 1.35; color: #222;">
          <div style="color: #444;">Alamat Mitra:</div>
          <div style="font-weight: bold; font-size: 8.5pt;">RPK HUMAIRA</div>
          <div>KP PASIR KALAPA RT 03 RW 06 DESA PEUTEUYCONDONG KEC CIBEBER KAB CIANJUR</div>
          <div>Peuteuycondong, Cibeber (Cianjur), Cianjur</div>
          <div>10, Jawa Barat (ID), Indonesia</div>
          <div>087727668194 087727668194</div>
          <div>NPWP: 00.000.000.0-000.000</div>
        </div>
      </div>
    </div>

    <!-- Judul Dokumen (Center) -->
    <div style="text-align: center; margin: 15px 0 25px 0;">
      <h3 style="font-weight: bold; font-size: 14pt; margin: 0 0 2px 0; color: #000; letter-spacing: 0.3px;">
        Order Penjualan
      </h3>
      <div style="font-size: 11pt; font-weight: 500; color: #222;">
        <?= htmlspecialchars($no_so_tampil) ?>
      </div>
    </div>

    <!-- Metadata Informasi Order Penjualan (2 Kolom) -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 9pt;">
      <!-- Kolom Kiri -->
      <div style="width: 48%;">
        <div class="bulog-meta-label">Referensi Mitra:</div>
        <div class="bulog-meta-value"><?= htmlspecialchars($pengadaan['no_pengadaan']) ?></div>

        <div class="bulog-meta-label">Rekening Tujuan:</div>
        <div class="bulog-meta-value" style="line-height: 1.3;">
          <?= htmlspecialchars($rek_tampil) ?><br>
          <span style="font-family: monospace; font-size: 8.5pt; color: #444;">010501002479306</span>
        </div>
      </div>

      <!-- Kolom Kanan -->
      <div style="width: 48%;">
        <div class="bulog-meta-label">Tanggal Dipesan:</div>
        <div class="bulog-meta-value"><?= $tgl_dipesan_tampil ?></div>

        <div style="display: flex; justify-content: space-between;">
          <div>
            <div class="bulog-meta-label">Syarat Pembayaran:</div>
            <div class="bulog-meta-value"><?= htmlspecialchars($syarat_tampil) ?></div>
          </div>
          <div style="text-align: right;">
            <div class="bulog-meta-label">Metode Pembayaran:</div>
            <div class="bulog-meta-value"><?= htmlspecialchars($metode_tampil) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Rincian Item Komoditas -->
    <table class="bulog-table">
      <thead>
        <tr>
          <th style="width: 32%; text-align: left;">Deskripsi</th>
          <th style="width: 20%; text-align: left;">Gudang:</th>
          <th style="width: 13%; text-align: center;">Jumlah</th>
          <th style="width: 10%; text-align: center;">Kuantum</th>
          <th style="width: 11%; text-align: right;">Harga Satuan</th>
          <th style="width: 14%; text-align: right;">Harga</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="font-weight: 600; font-size: 9pt;">
            [B0010201Z] BERAS MEDIUM HASIL GILING 25% LOGO SPHP 5 KG PSO DN
          </td>
          <td style="font-size: 8.5pt; color: #333;">
            <?= htmlspecialchars($gudang_tampil) ?>
          </td>
          <td style="text-align: center; font-size: 9pt;">
            <?= number_format($pengadaan['jumlah_sak'], 2, ',', '.') ?> Pack 5 KG
          </td>
          <td style="text-align: center; font-size: 9pt;">
            <?= number_format($pengadaan['total_berat_kg'], 2, ',', '.') ?> Kg
          </td>
          <td style="text-align: right; font-size: 9pt;">
            <?= number_format($pengadaan['harga_satuan'], 2, ',', '.') ?>
            <div style="font-size: 7.5pt; color: #666;">PPN 12% (dibebaskan)</div>
          </td>
          <td style="text-align: right; font-weight: 600; font-size: 9pt;">
            Rp <?= number_format($pengadaan['total_biaya'], 2, ',', '.') ?>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Ringkasan Total & PPN -->
    <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
      <div style="width: 48%; font-size: 9pt;">
        <div style="display: flex; justify-content: space-between; padding: 3px 0;">
          <span style="font-weight: 600;">Total Sebelum Pajak</span>
          <span style="font-weight: 600;">Rp <?= number_format($pengadaan['total_biaya'], 2, ',', '.') ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 3px 0; color: #555;">
          <span>PPN Dibebaskan</span>
          <span>Rp 0,00</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 5px 0; border-top: 1px solid #111; font-weight: bold; font-size: 9.5pt;">
          <span>Total</span>
          <span>Rp <?= number_format($pengadaan['total_biaya'], 2, ',', '.') ?></span>
        </div>
      </div>
    </div>

    <!-- Tanda Tangan Resmi (Yang Menerima & Yang Menyerahkan) -->
    <div style="display: flex; justify-content: space-between; margin-top: 45px; font-size: 9pt;">
      <!-- Kiri: Yang Menerima RPK Humaira -->
      <div style="width: 45%;">
        <div>Yang Menerima,</div>
        <!-- Stylized Signature Simbolis -->
        <div style="height: 55px; display: flex; align-items: flex-end;">
          <svg width="120" height="45" viewBox="0 0 120 45" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 35 C20 10, 35 5, 40 25 C45 40, 50 15, 60 20 C70 25, 80 5, 95 30 L110 32" stroke="#111827" stroke-width="2" stroke-linecap="round" fill="none"/>
          </svg>
        </div>
        <div style="font-weight: bold; text-decoration: underline; margin-top: 5px;">RPK HUMAIRA</div>
      </div>

      <!-- Kanan: Yang Menyerahkan Kepala Gudang BULOG -->
      <div style="width: 45%; text-align: left;">
        <div>Cianjur, <?= $tgl_dipesan_tampil ?></div>
        <div>Yang Menyerahkan,</div>
        <div style="margin-top: 1px;">Kepala</div>
        <div style="height: 48px;"></div>
        <div style="border-bottom: 1px solid #111; width: 180px;"></div>
      </div>
    </div>

    <!-- Garis Pemisah Footer Dokumen -->
    <div style="border-top: 1px solid #111; margin-top: 35px; padding-top: 6px; font-size: 8pt; color: #333; display: flex; justify-content: space-between;">
      <div>
        <span>Dicetak oleh : </span><strong>Manager Sales Cianjur</strong>
      </div>
      <div>
        <span>Pada waktu : </span><strong><?= $waktu_cetak ?></strong>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
