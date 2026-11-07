<?php
// manual_book.php - Buku Panduan Pengguna Sistem (Manual Book) untuk Tugas Akhir
$page_title = 'Buku Panduan Pengguna (Manual Book)';
$active_menu = 'manual_book';
require_once __DIR__ . '/config/app.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buku Panduan Pengguna - Sistem POS RPK Humaira</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; }
    .manual-nav { position: sticky; top: 1rem; }
    .nav-manual-link { color: #475569; font-weight: 500; padding: 0.4rem 0.75rem; border-radius: 6px; display: block; text-decoration: none; font-size: 0.9rem; }
    .nav-manual-link:hover, .nav-manual-link.active { background: #e2e8f0; color: #0d6efd; font-weight: 600; }
    .manual-section { margin-bottom: 2.5rem; scroll-margin-top: 2rem; }
    .code-box { background: #1e293b; color: #f8fafc; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.85rem; }
    @media print {
      .no-print { display: none !important; }
      body { 
        background: #ffffff !important; 
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      body * {
        visibility: visible !important;
      }
      .container { 
        max-width: 100% !important; 
        width: 100% !important; 
        padding: 0 !important; 
        margin: 0 !important; 
      }
      .col-lg-3 { display: none !important; }
      .col-lg-9 { 
        width: 100% !important; 
        max-width: 100% !important; 
        flex: 0 0 100% !important; 
        padding: 0 !important;
      }
      .card { 
        border: none !important; 
        box-shadow: none !important; 
        padding: 0 !important; 
      }
      .manual-nav { display: none !important; }
      .manual-section { 
        margin-bottom: 2rem; 
      }
      .alert {
        border: 1px solid #cbd5e1 !important;
      }
    }
  </style>
</head>
<body>

<!-- Navbar Manual Book -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 no-print sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>">
      <div class="bg-warning text-dark rounded-2 px-2 py-1"><i class="bi bi-box-seam-fill"></i></div>
      <span>RPK HUMAIRA &bull; BUKU PANDUAN (MANUAL BOOK)</span>
    </a>
    <div class="d-flex gap-2">
      <button onclick="window.print()" class="btn btn-outline-light btn-sm">
        <i class="bi bi-printer me-1"></i> Cetak / Simpan PDF
      </button>
      <a href="<?= BASE_URL ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-house-door me-1"></i> Masuk Aplikasi
      </a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row g-4">
    <!-- Sidebar Navigasi Bab -->
    <div class="col-lg-3 no-print">
      <div class="card border-0 shadow-sm rounded-3 manual-nav p-3">
        <h6 class="fw-bold mb-3 text-secondary small text-uppercase"><i class="bi bi-list-nested me-1"></i> Daftar Isi Panduan</h6>
        <nav class="d-flex flex-column gap-1">
          <a href="#bab1" class="nav-manual-link">1. Pendahuluan & Gambaran Umum</a>
          <a href="#bab2" class="nav-manual-link">2. Petunjuk Instalasi (XAMPP)</a>
          <a href="#bab3" class="nav-manual-link">3. Hak Akses & Akun Pengguna</a>
          <a href="#bab4" class="nav-manual-link">4. Master Data Produk Beras</a>
          <a href="#bab5" class="nav-manual-link">5. Transaksi POS & Aturan SPHP</a>
          <a href="#bab6" class="nav-manual-link">6. Produksi Giling (Medium/Premium)</a>
          <a href="#bab7" class="nav-manual-link">7. Pengadaan SPHP BULOG (2 Ton)</a>
          <a href="#bab8" class="nav-manual-link">8. Monitoring & Mutasi Stok</a>
          <a href="#bab9" class="nav-manual-link">9. Laporan & Evaluasi Omzet</a>
        </nav>
      </div>
    </div>

    <!-- Isi Buku Panduan -->
    <div class="col-lg-9">
      <div class="card border-0 shadow-sm rounded-3 p-4 p-md-5">

        <!-- Cover / Judul Dokumen -->
        <div class="text-center pb-4 mb-4 border-bottom">
          <span class="badge bg-primary px-3 py-2 fs-6 mb-2">BUKU PANDUAN PENGGUNA (USER MANUAL)</span>
          <h2 class="fw-bold text-dark mb-2">SISTEM INFORMASI PENJUALAN DAN PERSEDIAAN BERAS RPK HUMAIRA</h2>
          <p class="text-muted mb-0">Mitra Resmi Perum BULOG - Stabilisasi Pasokan dan Harga Pangan (SPHP)</p>
          <small class="text-secondary fw-semibold">Disusun untuk Keperluan Tugas Akhir / Skripsi Sistem Informasi</small>
        </div>

        <!-- BAB 1 -->
        <section id="bab1" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-bookmark-check-fill me-2"></i>BAB 1: Pendahuluan & Gambaran Umum Sistem</h4>
          <p>
            <strong>RPK Humaira (Rumah Pangan Kita)</strong> merupakan salah satu mitra resmi Perum BULOG yang bertugas mendistribusikan beras program Stabilisasi Pasokan dan Harga Pangan (SPHP) sekaligus menjual beras hasil penggilingan gabah mandiri (Beras Medium dan Beras Premium).
          </p>
          <div class="alert alert-light border p-3 rounded-3 mb-3">
            <h6 class="fw-bold text-dark mb-2">Tujuan & Ruang Lingkup Sistem:</h6>
            <ul class="mb-0 small ps-3">
              <li>Mencatat penjualan beras secara akurat dengan metode pembayaran tunai kasir (POS).</li>
              <li>Menegakkan <strong>regulasi BULOG</strong>: memverifikasi bahwa pembeli beras SPHP adalah konsumen akhir (rumah tangga), serta membatasi pembelian maksimal <strong>5 kemasan (sak 5kg) per orang dalam sehari</strong> menggunakan <strong>Nomor NIK / KTP (16 digit)</strong> pembeli sebagai identifikasi utama.</li>
              <li>Mencatat pertambahan stok beras medium dan premium dari <strong>hasil produksi/giling gabah sendiri</strong> (baik dalam kemasan sak maupun eceran liter).</li>
              <li>Memantau siklus 7 langkah pengadaan beras SPHP ke kantor cabang BULOG serta membatasi alokasi pengambilan maksimal <strong>2 Ton (2.000 kg) per minggu</strong>.</li>
              <li>Menyajikan laporan pergerakan kartu stok, realisasi kuota SPHP, omzet dan laba bersih per kategori beras.</li>
            </ul>
          </div>
        </section>

        <!-- BAB 2 -->
        <section id="bab2" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-laptop me-2"></i>BAB 2: Petunjuk Instalasi & Menjalankan Aplikasi</h4>
          <p>Aplikasi ini dibangun menggunakan <strong>PHP Native 8.x</strong> dan database <strong>MySQL / MariaDB</strong> pada server lokal XAMPP.</p>
          
          <h6 class="fw-bold">Langkah-langkah Menjalankan:</h6>
          <ol class="small ps-3 mb-3">
            <li>Pastikan modul <strong>Apache</strong> dan <strong>MySQL</strong> pada <em>XAMPP Control Panel</em> dalam kondisi <strong>Running</strong>.</li>
            <li>Folder aplikasi terletak di: <code>C:\xampp\htdocs\pos</code></li>
            <li>Database menggunakan nama: <code>db_rpk_humaira</code> (File SQL skema dan data awal tersedia di folder <code>database/db_rpk_humaira.sql</code>).</li>
            <li>Buka browser (Google Chrome / Mozilla Firefox / Edge), lalu akses URL:
              <div class="code-box my-2">http://localhost/pos/</div>
            </li>
            <li>Sistem akan menampilkan halaman login.</li>
          </ol>
        </section>

        <!-- BAB 3 -->
        <section id="bab3" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-people-fill me-2"></i>BAB 3: Hak Akses & Akun Pengguna</h4>
          <p>Sistem memiliki 2 (dua) tingkat hak akses (Role-Based Access Control):</p>
          <div class="table-responsive mb-3">
            <table class="table table-bordered small">
              <thead class="bg-light">
                <tr>
                  <th>Role</th>
                  <th>Username Default</th>
                  <th>Password Default</th>
                  <th>Wewenang Sistem</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="badge bg-primary">Pemilik (Owner)</span></td>
                  <td><code>pemilik</code></td>
                  <td><code>admin123</code></td>
                  <td>Akses penuh seluruh sistem: Kelola Master Produk, Menyetujui Alur Pengadaan SPHP, Melihat Laporan Omzet & Laba Bersih, Kelola Akun Pengguna, dan Pengaturan Toko.</td>
                </tr>
                <tr>
                  <td><span class="badge bg-success">Kasir (Petugas)</span></td>
                  <td><code>kasir1</code></td>
                  <td><code>kasir123</code></td>
                  <td>Melayani transaksi penjualan POS, Cetak Struk Belanja, Input Hasil Giling Mandiri, Monitoring Stok Beras, dan Melihat Riwayat Transaksi.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- BAB 4 -->
        <section id="bab4" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-box-seam me-2"></i>BAB 4: Pengelompokan Data Master Produk Beras</h4>
          <p>Master produk beras dikelompokkan secara ketat menjadi tiga (3) kategori:</p>
          <div class="row g-3 small">
            <div class="col-md-4">
              <div class="p-3 bg-light rounded-3 border h-100">
                <span class="badge badge-sphp mb-2">1. BERAS SPHP</span>
                <p class="mb-1"><strong>Varian Kemasan:</strong> Kemasan Sak 5 Kg (HET Rp 62.500) & Eceran Per Kg (HET Rp 12.500).</p>
                <p class="mb-1 text-primary fw-semibold"><strong>Aturan Harga:</strong> Harga jual dipisah antara per kemasan Rp 62.500 dan per kg Rp 12.500.</p>
                <p class="mb-0 text-muted">Beras penugasan BULOG dengan batas beli per konsumen akhir maks 25 Kg (setara 5 Sak) per hari.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 bg-light rounded-3 border h-100">
                <span class="badge badge-medium mb-2">2. BERAS MEDIUM</span>
                <p class="mb-1"><strong>Asal Usul:</strong> Hasil giling padi sendiri.</p>
                <p class="mb-1"><strong>Varian:</strong> Sak 5kg, Sak 10kg, Sak 25kg, dan <strong>Eceran Curah (Liter)</strong>.</p>
                <p class="mb-0 text-muted">Penambahan stok diinput melalui modul Giling Sendiri.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 bg-light rounded-3 border h-100">
                <span class="badge badge-premium mb-2">3. BERAS PREMIUM</span>
                <p class="mb-1"><strong>Asal Usul:</strong> Hasil giling padi sendiri kualitas tinggi (bulir utuh/wangi).</p>
                <p class="mb-1"><strong>Varian:</strong> Sak 5kg, Sak 10kg, Sak 25kg, dan <strong>Eceran Curah (Liter)</strong>.</p>
                <p class="mb-0 text-muted">Penambahan stok diinput melalui modul Giling Sendiri.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- BAB 5 -->
        <section id="bab5" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-cart4 me-2"></i>BAB 5: Alur Transaksi Kasir POS & Validasi Aturan SPHP</h4>
          <p>Modul Kasir POS dirancang ramah pengguna dan memiliki proteksi otomatis regulasi BULOG:</p>
          
          <div class="p-3 bg-light rounded-3 border mb-3 small">
            <h6 class="fw-bold text-dark">Langkah Transaksi Penjualan:</h6>
            <ol class="mb-2 ps-3">
              <li>Kasir memilih produk beras dari katalog (bisa mencari nama produk atau memfilter tab: <em>Semua, SPHP, Medium, Premium, Eceran Liter</em>).</li>
              <li>Klik produk untuk memasukkan ke keranjang belanja (Cart). Kuantiti dapat ditambah/dikurang sesuai permintaan pembeli.</li>
              <li><strong>Jika keranjang mengandung Beras SPHP:</strong>
                <ul class="ps-3 mt-1">
                  <li>Form validasi SPHP otomatis aktif (berwarna biru).</li>
                  <li>Kasir <strong>wajib mengisi Nomor NIK / KTP (16 digit)</strong> dan <strong>Nama Pembeli</strong> (Nomor HP opsional).</li>
                  <li>Sistem langsung memeriksa akumulasi pembelian NIK tersebut pada hari yang bersangkutan via AJAX real-time.</li>
                  <li><strong>Batas Pembelian:</strong> Maksimal 5 kemasan/hari/orang. Bila sisa kuota tidak mencukupi, tombol bayar terkunci otomatis dengan pesan peringatan.</li>
                  <li>Centang konfirmasi: <em>Pembeli adalah konsumen akhir (rumah tangga), bukan reseller</em>.</li>
                </ul>
              </li>
              <li><strong>Jika keranjang HANYA Beras Medium / Premium:</strong>
                <ul class="ps-3 mt-1">
                  <li>Data nama/no HP bersifat opsional. Tidak ada batasan kuota pembelian. Eceran dalam satuan liter didukung penuh.</li>
                </ul>
              </li>
              <li>Masukkan nominal uang tunai yang diterima (tersedia tombol cepat 50rb, 100rb, 200rb, atau uang pas). Sistem menghitung uang kembalian secara otomatis.</li>
              <li>Klik <strong>"Bayar Tunai & Cetak Struk"</strong>. Stok beras di sistem langsung berkurang, mutasi stok tercatat, dan halaman nota/struk belanja thermal ditampilkan siap cetak.</li>
            </ol>
          </div>
        </section>

        <!-- BAB 6 -->
        <section id="bab6" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-gear-wide-connected me-2"></i>BAB 6: Modul Produksi Giling Sendiri (Medium & Premium)</h4>
          <p>Karena RPK Humaira menggiling gabah sendiri untuk beras medium dan premium, sistem menyediakan pencatatan produksi:</p>
          <ul class="small ps-3 mb-3">
            <li>Buka menu <strong>Giling Sendiri (Med/Pre) &rarr; Catat Hasil Giling Baru</strong>.</li>
            <li>Pilih varian produk beras yang dihasilkan (misal Beras Medium Kemasan 5 Kg, atau Beras Premium Curah Liter).</li>
            <li>Input: Tanggal Giling, Jumlah gabah digiling (Kg - opsional), Jumlah beras bersih yang dihasilkan, dan Biaya jasa operasional giling.</li>
            <li>Saat tombol <strong>Simpan</strong> ditekan:
              <ul class="ps-3">
                <li>Sistem otomatis <strong>menambah stok</strong> produk bersangkutan.</li>
                <li>Sistem mencatat transaksi mutasi masuk di <strong>Kartu Stok</strong> dengan keterangan nomor produksi giling.</li>
              </ul>
            </li>
          </ul>
        </section>

        <!-- BAB 7 -->
        <section id="bab7" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-truck me-2"></i>BAB 7: Alur Pengadaan Beras SPHP dari BULOG (SOP 7 Langkah & Batas 2 Ton)</h4>
          <p>Pengadaan beras SPHP diatur dengan SOP ketat dan batasan kuota mingguan:</p>

          <div class="alert alert-warning border-0 p-3 rounded-3 small text-dark mb-3">
            <h6 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-1"></i> Batas Kuota Mingguan BULOG:</h6>
            RPK Humaira memiliki hak alokasi maksimal <strong>2.000 Kg (2 Ton = 400 Sak @ 5Kg) per pekan</strong>. Sistem menghitung secara otomatis total tonase pengadaan dari hari Senin sampai Minggu berjalan. Jika pengajuan baru melebihi sisa alokasi pekan tersebut, sistem menolak input pengadaan demi kepatuhan regulasi.
          </div>

          <div class="p-3 bg-light rounded-3 border small mb-3">
            <h6 class="fw-bold text-dark">Urutan 7 Tahapan Pengadaan SPHP:</h6>
            <ol class="mb-0 ps-3">
              <li><strong>Deteksi Stok:</strong> Sistem memberi peringatan di dashboard bila stok SPHP menipis (<= stok minimum) atau habis (0 sak).</li>
              <li><strong>Pemesanan di Aplikasi BULOG:</strong> Petugas/Kasir menginput pengajuan di sistem POS dengan mencantumkan Nomor Referensi pesanan dari aplikasi resmi SPHP BULOG.</li>
              <li><strong>Verifikasi Cabang BULOG:</strong> Menunggu cabang/gudang BULOG menerima dan menyetujui kuota alokasi pesanan (status: <em>Disetujui</em>).</li>
              <li><strong>Pembayaran Transfer Rekening:</strong> Dilakukan pembayaran tebusan melalui transfer ke rekening bank resmi BULOG (catat bank, no rekening, tanggal bayar).</li>
              <li><strong>Penerbitan Surat Jalan DO/SPPB:</strong> BULOG menerbitkan Surat Perintah Penyerahan Barang (catat nomor surat DO dan petugas pengambil).</li>
              <li><strong>Pengambilan di Gudang BULOG:</strong> Petugas/driver datang ke gudang BULOG dengan membawa surat DO untuk memuat beras SPHP.</li>
              <li><strong>Penerimaan di Toko RPK Humaira:</strong> Setelah pick-up tiba di gudang toko, pemilik menekan tombol <strong>"Konfirmasi Barang Tiba & Perbarui Stok"</strong>. Stok Beras SPHP otomatis <strong>bertambah</strong> sejumlah sak yang dipesan.</li>
            </ol>
          </div>
        </section>

        <!-- BAB 8 -->
        <section id="bab8" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-boxes me-2"></i>BAB 8: Monitoring & Kartu Mutasi Stok Beras</h4>
          <p>Pengelolaan persediaan beras di RPK Humaira:</p>
          <ul class="small ps-3 mb-3">
            <li><strong>Pemisahan Kategori:</strong> Halaman Monitoring Stok memisahkan secara visual stok SPHP (kemasan 5kg), stok Medium (kemasan & liter), dan stok Premium (kemasan & liter).</li>
            <li><strong>Indikator Low Stock:</strong> Badge hijau (Aman), kuning (Menipis di bawah minimum), dan merah (Habis).</li>
            <li><strong>Kartu Stok (Audit Mutasi):</strong> Mencatat detail setiap keluar-masuk barang, waktu, saldo sebelum, saldo sesudah, dan petugas yang bertanggung jawab.</li>
            <li><strong>Stock Opname:</strong> Fasilitas koreksi stok fisik berkala jika terjadi penyusutan atau kerusakan kemasan beras.</li>
          </ul>
        </section>

        <!-- BAB 9 -->
        <section id="bab9" class="manual-section">
          <h4 class="fw-bold text-primary mb-3"><i class="bi bi-file-earmark-bar-graph me-2"></i>BAB 9: Laporan & Evaluasi Omzet</h4>
          <p>Sistem menyediakan 4 modul laporan lengkap:</p>
          <div class="row g-3 small">
            <div class="col-md-6">
              <div class="border p-3 rounded-3">
                <h6 class="fw-bold text-dark mb-1">1. Laporan Penjualan</h6>
                <p class="mb-0 text-muted">Dapat dilihat per hari, rentang tanggal, maupun bulanan. Memiliki filter khusus jenis beras (SPHP, Medium, Premium).</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="border p-3 rounded-3">
                <h6 class="fw-bold text-dark mb-1">2. Laporan Realisasi Kuota SPHP (2 Ton)</h6>
                <p class="mb-0 text-muted">Merekapitulasi tingkat serapan beras SPHP mingguan dibanding batas 2.000 kg BULOG beserta persentase kepatuhan.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="border p-3 rounded-3">
                <h6 class="fw-bold text-dark mb-1">3. Laporan Mutasi Stok</h6>
                <p class="mb-0 text-muted">Menyajikan ringkasan barang masuk (giling & BULOG) vs barang keluar (penjualan) dalam bentuk kartu stok lengkap.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="border p-3 rounded-3">
                <h6 class="fw-bold text-dark mb-1">4. Laporan Omzet & Laba</h6>
                <p class="mb-0 text-muted">Khusus pemilik toko: menganalisis perolehan omzet, modal HPP, margin laba kotor, dan kontribusi omzet per kategori produk.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Footer Pengesahan Manual Book -->
        <div class="text-center pt-4 border-top mt-5">
          <p class="small text-muted mb-1">Dokumen Manual Book Sistem Informasi Penjualan dan Persediaan Beras RPK Humaira</p>
          <small class="text-secondary">&copy; <?= date('Y') ?> RPK Humaira &bull; Hak Cipta Dilindungi Undang-Undang</small>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
