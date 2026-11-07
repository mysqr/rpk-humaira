<?php
// modules/produksi/detail.php - Rincian Hasil Giling Padi
$page_title = 'Detail Hasil Giling Padi';
$active_menu = 'produksi';
require_once __DIR__ . '/../../config/auth.php';
check_login();

$id_produksi = (int)($_GET['id'] ?? 0);
$sql = "SELECT pg.*, p.nama_produk, p.satuan, p.is_eceran, p.stok, k.nama_kategori, k.kode_kategori, u.nama_lengkap as nama_petugas
        FROM produksi_giling pg
        JOIN produk p ON pg.id_produk = p.id_produk
        JOIN kategori_beras k ON p.id_kategori = k.id_kategori
        LEFT JOIN users u ON pg.id_user = u.id_user
        WHERE pg.id_produksi = $id_produksi";

$data = query_single($sql);
if (!$data) {
    set_flash('danger', 'Data produksi tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/produksi/index.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Print Letterhead (Hanya muncul saat print) -->
<div class="d-none d-print-block print-header">
  <h4><?= strtoupper(htmlspecialchars($toko_info['nama_toko'])) ?></h4>
  <div class="print-sub"><?= htmlspecialchars($toko_info['mitra']) ?> | <?= htmlspecialchars($toko_info['alamat']) ?></div>
  <div class="print-doc-title">BUKTI HASIL PRODUKSI GILING PADI MANDIRI</div>
  <div class="print-period">No. Referensi: <?= htmlspecialchars($data['kode_produksi']) ?> | Tanggal: <?= tgl_indo($data['tanggal_giling']) ?></div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-file-text text-success fs-5"></i>
          <h6 class="mb-0 fw-bold">Rincian Produksi: <?= htmlspecialchars($data['kode_produksi']) ?></h6>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak Bukti Giling
          </button>
          <a href="<?= BASE_URL ?>/modules/produksi/edit.php?id=<?= $data['id_produksi'] ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil-square me-1"></i> Edit
          </a>
          <a href="<?= BASE_URL ?>/modules/produksi/hapus.php?id=<?= $data['id_produksi'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus catatan produksi <?= htmlspecialchars($data['kode_produksi']) ?>? Stok beras (+<?= $data['jumlah_hasil'] ?> <?= $data['satuan'] ?>) akan otomatis dikurangi kembali.')">
            <i class="bi bi-trash me-1"></i> Hapus
          </a>
          <a href="<?= BASE_URL ?>/modules/produksi/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
          </a>
        </div>
      </div>
      <div class="card-body p-4">
        <table class="table table-bordered mb-0">
          <tbody>
            <tr>
              <th class="bg-light" style="width: 35%;">Nomor Produksi</th>
              <td class="fw-bold text-dark"><?= htmlspecialchars($data['kode_produksi']) ?></td>
            </tr>
            <tr>
              <th class="bg-light">Tanggal Giling</th>
              <td><?= tgl_indo($data['tanggal_giling']) ?></td>
            </tr>
            <tr>
              <th class="bg-light">Kategori Beras</th>
              <td>
                <span class="badge <?= $data['kode_kategori'] === 'PREMIUM' ? 'badge-premium' : 'badge-medium' ?>">
                  <?= htmlspecialchars($data['nama_kategori']) ?>
                </span>
              </td>
            </tr>
            <tr>
              <th class="bg-light">Produk Beras Dihasilkan</th>
              <td class="fw-semibold text-primary"><?= htmlspecialchars($data['nama_produk']) ?> (<?= $data['satuan'] ?>)</td>
            </tr>
            <tr>
              <th class="bg-light">Bahan Baku Gabah</th>
              <td><?= $data['jumlah_gabah_kg'] > 0 ? number_format($data['jumlah_gabah_kg'], 1) . ' Kg Gabah' : 'Tidak dicatat' ?></td>
            </tr>
            <tr>
              <th class="bg-light">Jumlah Hasil Giling</th>
              <td>
                <span class="badge bg-success fs-6 fw-bold">
                  +<?= number_format($data['jumlah_hasil'], ($data['is_eceran'] ? 1 : 0)) ?> <?= $data['satuan'] ?>
                </span>
              </td>
            </tr>
            <tr>
              <th class="bg-light">Biaya Operasional Giling</th>
              <td><?= $data['biaya_giling'] > 0 ? rupiah($data['biaya_giling']) : 'Rp 0' ?></td>
            </tr>
            <tr>
              <th class="bg-light">Petugas yang Mencatat</th>
              <td><?= htmlspecialchars($data['nama_petugas'] ?? '-') ?></td>
            </tr>
            <tr>
              <th class="bg-light">Waktu Input Sistem</th>
              <td class="small text-muted"><?= tgl_indo($data['created_at'], true) ?></td>
            </tr>
            <tr>
              <th class="bg-light">Catatan</th>
              <td><?= nl2br(htmlspecialchars($data['catatan'] ?? '-')) ?></td>
            </tr>
          </tbody>
        </table>

        <!-- Tanda Tangan Cetak -->
        <div class="d-none d-print-block print-signature">
          <div class="sign-box">
            <div>Petugas Penggilingan,</div>
            <div class="sign-space"></div>
            <div class="sign-name">( <?= htmlspecialchars($data['nama_petugas'] ?? 'Petugas') ?> )</div>
          </div>
          <div class="sign-box">
            <div>Mengetahui, Pemilik Toko</div>
            <div class="sign-space"></div>
            <div class="sign-name">( <?= htmlspecialchars($toko_info['nama_pemilik']) ?> )</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
