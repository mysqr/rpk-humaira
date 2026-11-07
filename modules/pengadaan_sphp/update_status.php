<?php
// modules/pengadaan_sphp/update_status.php - Pembaruan Tahapan Alur Pengadaan SPHP
$page_title = 'Update Tahapan Pengadaan SPHP';
$active_menu = 'pengadaan_sphp';
require_once __DIR__ . '/../../config/auth.php';
check_role(['pemilik']); // Hanya pemilik yang berwenang menyetujui/mengupdate status pengadaan BULOG

$id_pengadaan = (int)($_GET['id'] ?? 0);
$pengadaan = query_single("SELECT p.*, pr.nama_produk, pr.satuan, pr.stok as stok_saat_ini 
    FROM pengadaan_sphp p 
    JOIN produk pr ON p.id_produk = pr.id_produk 
    WHERE p.id_pengadaan = $id_pengadaan");

if (!$pengadaan) {
    set_flash('danger', 'Data pengadaan SPHP tidak ditemukan!');
    header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/index.php');
    exit;
}

if ($pengadaan['status'] === 'selesai' || $pengadaan['status'] === 'batal') {
    set_flash('info', 'Status pengadaan ini sudah selesai / final.');
    header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/detail.php?id=' . $id_pengadaan);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $next_status = escape($_POST['next_status'] ?? '');
    $catatan_tambah = escape($_POST['catatan_tambah'] ?? '');

    if ($next_status === 'disetujui') {
        $tgl_disetujui = escape($_POST['tanggal_disetujui'] ?? date('Y-m-d'));
        $sql = "UPDATE pengadaan_sphp SET 
                status = 'disetujui',
                tanggal_disetujui = '$tgl_disetujui',
                id_user_approved = $user_id,
                catatan = CONCAT(COALESCE(catatan, ''), '\n[Disetujui BULOG: $catatan_tambah]')
                WHERE id_pengadaan = $id_pengadaan";
        $conn->query($sql);
        set_flash('success', "Pesanan pengadaan SPHP disetujui oleh cabang BULOG! Lanjutkan ke tahap pembayaran transfer.");
    }
    elseif ($next_status === 'dibayar') {
        $tgl_bayar = escape($_POST['tanggal_bayar'] ?? date('Y-m-d'));
        $bank = escape($_POST['bank_tujuan'] ?? 'Bank BRI Cabang');
        $no_rek = escape($_POST['no_rekening'] ?? '');
        $sql = "UPDATE pengadaan_sphp SET 
                status = 'dibayar',
                tanggal_bayar = '$tgl_bayar',
                bank_tujuan = '$bank',
                no_rekening = '$no_rek',
                catatan = CONCAT(COALESCE(catatan, ''), '\n[Pembayaran Transfer: $bank - $no_rek]')
                WHERE id_pengadaan = $id_pengadaan";
        $conn->query($sql);
        set_flash('success', "Pembayaran transfer ke rekening BULOG berhasil dicatat! Menunggu penerbitan Surat Pengambilan (DO).");
    }
    elseif ($next_status === 'surat_jalan') {
        $no_surat = escape($_POST['no_surat_pengambilan'] ?? '');
        $tgl_ambil = escape($_POST['tanggal_ambil'] ?? date('Y-m-d'));
        $petugas = escape($_POST['petugas_pengambil'] ?? '');
        $sql = "UPDATE pengadaan_sphp SET 
                status = 'surat_jalan',
                no_surat_pengambilan = '$no_surat',
                tanggal_ambil = '$tgl_ambil',
                petugas_pengambil = '$petugas',
                catatan = CONCAT(COALESCE(catatan, ''), '\n[DO Terbit: $no_surat, Petugas: $petugas]')
                WHERE id_pengadaan = $id_pengadaan";
        $conn->query($sql);
        set_flash('success', "Surat Pengambilan Beras (DO/SPPB) tercatat! Petugas dapat mengambil beras di gudang BULOG.");
    }
    elseif ($next_status === 'selesai') {
        // KONFIRMASI BARANG DITERIMA DI GUDANG RPK HUMAIRA -> STOK SPHP BERTAMBAH
        $tgl_diterima = date('Y-m-d H:i:s');
        $id_produk = $pengadaan['id_produk'];
        $jumlah_sak = (int)$pengadaan['jumlah_sak'];

        $conn->begin_transaction();
        try {
            // Update status pengadaan
            $conn->query("UPDATE pengadaan_sphp SET 
                          status = 'selesai',
                          tanggal_diterima = '$tgl_diterima',
                          catatan = CONCAT(COALESCE(catatan, ''), '\n[Barang diterima di RPK Humaira pada $tgl_diterima]')
                          WHERE id_pengadaan = $id_pengadaan");

            // Tambah stok beras SPHP
            $stok_sebelum = (float)$pengadaan['stok_saat_ini'];
            $stok_sesudah = $stok_sebelum + $jumlah_sak;
            $conn->query("UPDATE produk SET stok = $stok_sesudah WHERE id_produk = $id_produk");

            // Catat di kartu riwayat stok
            $ket_kartu = "Penerimaan Pengadaan Beras SPHP BULOG (No. DO: {$pengadaan['no_surat_pengambilan']})";
            $conn->query("INSERT INTO riwayat_stok (id_produk, jenis_transaksi, sumber, referensi_kode, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user)
                          VALUES ($id_produk, 'MASUK', 'PENGADAAN_BULOG', '{$pengadaan['no_pengadaan']}', $jumlah_sak, $stok_sebelum, $stok_sesudah, '$ket_kartu', $user_id)");

            $conn->commit();
            set_flash('success', "ALHAMDULILLAH! Beras SPHP sebanyak <strong>$jumlah_sak Sak (5kg)</strong> telah diterima di RPK Humaira. Stok di sistem otomatis bertambah!");
        } catch (Exception $e) {
            $conn->rollback();
            set_flash('danger', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }
    elseif ($next_status === 'batal') {
        $conn->query("UPDATE pengadaan_sphp SET status = 'batal', catatan = CONCAT(COALESCE(catatan, ''), '\n[Dibatalkan: $catatan_tambah]') WHERE id_pengadaan = $id_pengadaan");
        set_flash('warning', "Pengadaan SPHP telah dibatalkan.");
    }

    header('Location: ' . BASE_URL . '/modules/pengadaan_sphp/detail.php?id=' . $id_pengadaan);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-arrow-repeat text-primary me-2"></i>Perbarui Status Alur Pengadaan SPHP</h6>
        <a href="<?= BASE_URL ?>/modules/pengadaan_sphp/detail.php?id=<?= $id_pengadaan ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Batal
        </a>
      </div>
      <div class="card-body p-4">

        <!-- Ringkasan Info Order -->
        <div class="p-3 bg-light rounded-3 border mb-4">
          <div class="row g-2 small">
            <div class="col-sm-6">
              <span class="text-muted">No. Pengadaan:</span> <strong><?= htmlspecialchars($pengadaan['no_pengadaan']) ?></strong>
            </div>
            <div class="col-sm-6">
              <span class="text-muted">Ref Aplikasi BULOG:</span> <strong><?= htmlspecialchars($pengadaan['no_ref_bulog'] ?: '-') ?></strong>
            </div>
            <div class="col-sm-6">
              <span class="text-muted">Cabang BULOG:</span> <strong><?= htmlspecialchars($pengadaan['cabang_bulog']) ?></strong>
            </div>
            <div class="col-sm-6">
              <span class="text-muted">Volume:</span> <strong class="text-primary"><?= number_format($pengadaan['jumlah_sak']) ?> Sak (<?= number_format($pengadaan['total_berat_kg']) ?> Kg)</strong>
            </div>
            <div class="col-sm-6">
              <span class="text-muted">Total Biaya:</span> <strong><?= rupiah($pengadaan['total_biaya']) ?></strong>
            </div>
            <div class="col-sm-6">
              <span class="text-muted">Status Saat Ini:</span> 
              <span class="badge bg-secondary text-uppercase"><?= $pengadaan['status'] ?></span>
            </div>
          </div>
        </div>

        <form method="POST" action="">
          
          <?php if ($pengadaan['status'] === 'diajukan'): ?>
            <!-- Tahap 1 -> 2: Verifikasi Cabang BULOG Menerima -->
            <input type="hidden" name="next_status" value="disetujui">
            <div class="alert alert-info border-0 rounded-3">
              <h6 class="fw-bold mb-1"><i class="bi bi-check2-circle me-1"></i> Langkah 2: Konfirmasi Persetujuan Cabang BULOG</h6>
              <p class="small mb-0">Pastikan cabang BULOG telah menerima dan mengonfirmasi alokasi pesanan SPHP.</p>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Tanggal Persetujuan Cabang BULOG</label>
              <input type="date" name="tanggal_disetujui" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold small">Catatan Tambahan</label>
              <input type="text" name="catatan_tambah" class="form-control" placeholder="Contoh: Dikonfirmasi oleh petugas BULOG Seksi Penjualan">
            </div>
            <div class="d-flex justify-content-between">
              <button type="submit" class="btn btn-success px-4 fw-semibold">
                <i class="bi bi-check-lg me-1"></i> Setujui Pesanan (Lanjut ke Pembayaran)
              </button>
              <button type="submit" name="next_status" value="batal" class="btn btn-outline-danger" onclick="return confirm('Yakin ingin membatalkan pengadaan ini?')">
                Batalkan Pengadaan
              </button>
            </div>

          <?php elseif ($pengadaan['status'] === 'disetujui'): ?>
            <!-- Tahap 2 -> 3: Pembayaran Transfer Rekening -->
            <input type="hidden" name="next_status" value="dibayar">
            <div class="alert alert-warning border-0 rounded-3 text-dark">
              <h6 class="fw-bold mb-1"><i class="bi bi-credit-card me-1"></i> Langkah 3: Pembayaran Transfer ke Rekening BULOG</h6>
              <p class="small mb-0">Catat bukti transfer tebusan beras SPHP sejumlah <strong><?= rupiah($pengadaan['total_biaya']) ?></strong>.</p>
            </div>
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Tanggal Pembayaran Transfer <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Bank Tujuan BULOG <span class="text-danger">*</span></label>
                <input type="text" name="bank_tujuan" class="form-control" value="Bank BRI Kantor Cabang" required>
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold small">Nomor Rekening / Referensi Transfer <span class="text-danger">*</span></label>
                <input type="text" name="no_rekening" class="form-control" placeholder="Contoh: 0123-01-000456-30-1 a.n PERUM BULOG" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
              <i class="bi bi-wallet2 me-1"></i> Konfirmasi Pembayaran Selesai
            </button>

          <?php elseif ($pengadaan['status'] === 'dibayar'): ?>
            <!-- Tahap 3 -> 4: Penerbitan Surat Pengambilan (DO / SPPB) -->
            <input type="hidden" name="next_status" value="surat_jalan">
            <div class="alert alert-primary border-0 rounded-3">
              <h6 class="fw-bold mb-1"><i class="bi bi-file-earmark-check me-1"></i> Langkah 4: Penerbitan Surat Pengambilan Beras (DO / SPPB)</h6>
              <p class="small mb-0">Masukkan nomor Surat Perintah Penyerahan Barang (SPPB) atau Delivery Order dari BULOG untuk dibawa ke gudang.</p>
            </div>
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Nomor Surat Pengambilan (DO / SPPB) <span class="text-danger">*</span></label>
                <input type="text" name="no_surat_pengambilan" class="form-control" placeholder="Contoh: DO/BULOG/SPHP/IX/2026/0550" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Tanggal Rencana Ambil <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_ambil" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold small">Nama Petugas Pengambil / Supir Pick-up</label>
                <input type="text" name="petugas_pengambil" class="form-control" placeholder="Contoh: Budi Santoso (Driver Pick-up)">
              </div>
            </div>
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
              <i class="bi bi-file-earmark-text me-1"></i> Simpan Surat DO & Siap Diambil di Gudang
            </button>

          <?php elseif ($pengadaan['status'] === 'surat_jalan'): ?>
            <!-- Tahap 4 -> 5: Beras Selesai Diambil & Sampai di RPK Humaira (STOK MASUK) -->
            <input type="hidden" name="next_status" value="selesai">
            <div class="alert alert-success border-0 rounded-3">
              <h6 class="fw-bold mb-1"><i class="bi bi-truck-flatbed me-1"></i> Langkah Terakhir: Konfirmasi Beras Diterima di Gudang RPK Humaira</h6>
              <p class="small mb-0">Petugas telah menyerahkan surat ke gudang BULOG dan mengangkut beras sampai ke toko. Dengan menekan tombol di bawah, <strong>Stok Beras SPHP di sistem akan OTOMATIS BERTAMBAH sejumlah <?= $pengadaan['jumlah_sak'] ?> Sak</strong>.</p>
            </div>
            <div class="p-3 bg-white border border-success rounded-3 mb-4 text-center">
              <div class="text-muted small">Penambahan Stok Otomatis:</div>
              <h3 class="fw-bold text-success mb-0">+<?= number_format($pengadaan['jumlah_sak']) ?> Sak Beras SPHP (<?= number_format($pengadaan['total_berat_kg']) ?> Kg)</h3>
              <small class="text-muted">Stok saat ini: <?= number_format($pengadaan['stok_saat_ini']) ?> Sak &rarr; Akan menjadi <strong><?= number_format($pengadaan['stok_saat_ini'] + $pengadaan['jumlah_sak']) ?> Sak</strong></small>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" onclick="return confirm('Konfirmasi bahwa barang fisik telah tiba di toko RPK Humaira dan stok akan ditambah?')">
              <i class="bi bi-check-circle-fill me-2"></i> Konfirmasi Barang Tiba & Perbarui Stok SPHP
            </button>

          <?php endif; ?>

        </form>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
