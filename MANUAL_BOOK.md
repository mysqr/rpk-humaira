# BUKU PANDUAN PENGGUNA (MANUAL BOOK)
## SISTEM INFORMASI PENJUALAN DAN PERSEDIAAN BERAS RPK HUMAIRA
**Mitra Resmi Perum BULOG - Stabilisasi Pasokan dan Harga Pangan (SPHP)**  
*Dokumentasi Sistem untuk Keperluan Tugas Akhir / Skripsi Sistem Informasi*

---

## DAFTAR ISI
1. [BAB 1: Pendahuluan & Gambaran Umum Sistem](#bab-1-pendahuluan--gambaran-umum-sistem)
2. [BAB 2: Petunjuk Instalasi & Menjalankan Aplikasi](#bab-2-petunjuk-instalasi--menjalankan-aplikasi)
3. [BAB 3: Hak Akses & Akun Pengguna](#bab-3-hak-akses--akun-pengguna)
4. [BAB 4: Manajemen Master Data Produk Beras](#bab-4-manajemen-master-data-produk-beras)
5. [BAB 5: Alur Transaksi Kasir (POS) & Aturan SPHP](#bab-5-alur-transaksi-kasir-pos--aturan-sphp)
6. [BAB 6: Modul Produksi Giling Sendiri (Medium & Premium)](#bab-6-modul-produksi-giling-sendiri-medium--premium)
7. [BAB 7: Alur Pengadaan Beras SPHP dari BULOG (Batas 2 Ton)](#bab-7-alur-pengadaan-beras-sphp-dari-bulog-batas-2-ton)
8. [BAB 8: Monitoring & Kartu Mutasi Stok Beras](#bab-8-monitoring--kartu-mutasi-stok-beras)
9. [BAB 9: Pencatatan Laporan & Analisis Omzet](#bab-9-pencatatan-laporan--analisis-omzet)

---

## BAB 1: Pendahuluan & Gambaran Umum Sistem

**RPK Humaira (Rumah Pangan Kita)** adalah unit usaha mitra resmi **Perum BULOG** yang berfokus pada penyaluran komoditas beras murah berkualitas melalui program **Stabilisasi Pasokan dan Harga Pangan (SPHP)**, serta penjualan beras hasil penggilingan gabah mandiri (**Beras Medium** dan **Beras Premium**).

### Latar Belakang & Masalah Bisnis:
1. **Regulasi Ketat Beras SPHP BULOG:**
   - Beras SPHP hanya boleh disalurkan kepada **konsumen akhir (rumah tangga)** dan dilarang keras diborong oleh reseller/pengecer untuk dijual kembali di atas Harga Eceran Tertinggi (HET).
   - Terdapat kuota pembelian maksimal **5 kemasan (5 sak @ 5kg = 25kg) per orang per hari**.
   - Diperlukan mekanisme pencatatan identitas resmi (**Nomor NIK / KTP 16 digit**) untuk mencegah satu pembeli memborong berulang kali pada hari yang sama.
2. **Karakteristik Beras Giling Sendiri (Medium & Premium):**
   - Beras Medium dan Premium berasal dari gabah panen petani yang digiling sendiri di tempat.
   - Sistem perlu mencatat tanggal giling, jumlah gabah yang diproses, biaya giling, dan jumlah beras yang dihasilkan untuk menambah stok secara otomatis.
   - Seringkali terdapat permintaan konsumen untuk membeli secara eceran dalam satuan **liter**, yang harus didukung oleh sistem kasir.
3. **Regulasi Kuota Pengadaan BULOG:**
   - Pengambilan beras SPHP dari gudang BULOG dibatasi maksimal **2 Ton (2.000 kg = 400 sak @ 5kg) per minggu**.
   - Siklus pengadaan melibatkan 7 langkah: verifikasi aplikasi resmi SPHP BULOG, persetujuan kantor cabang, pembayaran transfer rekening, penerbitan surat jalan (DO/SPPB), pengambilan di gudang, dan penerimaan fisik di toko.

---

## BAB 2: Petunjuk Instalasi & Menjalankan Aplikasi

Aplikasi dibangun menggunakan **PHP Native 8.x** dan **MySQL/MariaDB**, tanpa framework rumit sehingga kode sangat mudah dipelajari dan dipertanggungjawabkan saat sidang Tugas Akhir.

### Kebutuhan Perangkat Lunak:
- XAMPP (Apache + MySQL/MariaDB + PHP 8.x)
- Web Browser (Google Chrome / Mozilla Firefox / Microsoft Edge)

### Langkah-langkah Instalasi:
1. Jalankan **XAMPP Control Panel**, lalu klik **Start** pada modul **Apache** dan **MySQL**.
2. Letakkan folder aplikasi di direktori web server XAMPP:
   ```
   C:\xampp\htdocs\pos\
   ```
3. Database MySQL:
   - Nama database: `db_rpk_humaira`
   - File database tersedia di: `c:\xampp\htdocs\pos\database\db_rpk_humaira.sql`
   - Skema dan data awal telah terpasang secara otomatis.
4. Buka browser dan ketik alamat:
   ```
   http://localhost/pos/
   ```
5. Layar Login akan muncul dan siap digunakan.

---

## BAB 3: Hak Akses & Akun Pengguna

Sistem menerapkan **Role-Based Access Control (RBAC)** untuk 2 jenis pengguna:

| Role | Username | Password | Wewenang & Hak Akses |
| :--- | :--- | :--- | :--- |
| **Pemilik (Owner)** | `pemilik` | `admin123` | Akses penuh seluruh sistem: Kelola Master Produk, Menyetujui & Memperbarui Status Pengadaan BULOG, Melihat Laporan Omzet & Keuntungan Bersih, Kelola Akun Pengguna, dan Konfigurasi Toko. |
| **Kasir (Petugas)** | `kasir1` | `kasir123` | Pelayanan transaksi kasir POS, Cetak Struk Nota Belanja, Input Hasil Giling Mandiri, Monitoring Stok Beras, dan Melihat Riwayat Transaksi Penjualan. |

---

## BAB 4: Manajemen Master Data Produk Beras

Produk dikelompokkan ke dalam 3 jenis kategori utama:

1. **Beras SPHP (BULOG):**
   - Satuan terkunci hanya **Kemasan 5 Kg**.
   - **Aturan baku:** Tidak boleh dijual eceran (liter/kg curah).
   - Pengurangan stok terjadi otomatis saat transaksi kasir.
   - Penambahan stok hanya berasal dari modul **Pengadaan SPHP BULOG**.
2. **Beras Medium:**
   - Hasil giling padi sendiri.
   - Varian kemasan: Sak 5 Kg, Sak 10 Kg, Sak 25 Kg, dan **Eceran Curah (Liter)**.
   - Penambahan stok berasal dari modul **Giling Sendiri**.
3. **Beras Premium:**
   - Hasil giling padi pilihan berbulir utuh.
   - Varian kemasan: Sak 5 Kg, Sak 10 Kg, Sak 25 Kg, dan **Eceran Curah (Liter)**.
   - Penambahan stok berasal dari modul **Giling Sendiri**.

---

## BAB 5: Alur Transaksi Kasir (POS) & Aturan SPHP

### Prosedur Transaksi Penjualan:
1. Kasir membuka menu **Kasir Penjualan** (`modules/kasir/index.php`).
2. Klik kartu produk untuk memasukkan ke keranjang belanja (bisa dicari lewat kolom search atau tab filter kategori).
3. Atur kuantiti yang dibeli.
4. **Validasi Khusus Beras SPHP:**
   - Jika keranjang mengandung produk Beras SPHP, form verifikasi biru akan aktif.
   - Kasir **wajib mengisi Nomor NIK / KTP (16 digit) dan Nama Pembeli** (Nomor HP opsional).
   - Sistem melakukan pengecekan kuota harian via AJAX ke database berdasarkan NIK pembeli secara real-time.
   - **Batas Pembelian:** Maksimal 5 kemasan/hari/orang.
   - Jika pembeli sudah pernah membeli 3 sak tadi pagi, maka sisa kuota hari ini hanya 2 sak. Jika diinput > 2 sak, sistem kasir akan mengunci tombol checkout dan memunculkan peringatan merah kuota terlampaui.
   - Kasir wajib memastikan checkbox: *"Pembeli adalah konsumen akhir (rumah tangga), bukan reseller/pengecer"* telah tercentang.
5. **Pembelian Beras Medium & Premium:**
   - Tidak ada batas kuota.
   - Nama pembeli opsional.
   - Mendukung kuantiti pecahan untuk eceran liter.
6. **Pembayaran Tunai (Cash):**
   - Masukkan nominal uang yang diterima.
   - Kembalian dihitung otomatis.
   - Klik **"Bayar Tunai & Cetak Struk"**.
7. Struk belanja thermal (lebar 58mm/80mm) akan tercetak dengan keterangan lengkap verifikasi pembeli akhir BULOG.

---

## BAB 6: Modul Produksi Giling Sendiri (Medium & Premium)

Khusus beras medium dan premium yang merupakan hasil gilingan sendiri:
1. Buka menu **Giling Sendiri (Med/Pre) &rarr; Catat Hasil Giling Baru**.
2. Pilih varian beras yang dihasilkan (misal *Beras Medium Cap Melati 5 Kg* atau *Beras Medium Curah Liter*).
3. Masukkan:
   - Tanggal giling
   - Jumlah gabah yang digiling (kg)
   - Jumlah hasil beras bersih (sak atau liter)
   - Biaya ongkos operasional giling
   - Catatan
4. Klik **Simpan & Tambah Stok Beras**.
5. Sistem secara otomatis:
   - Menambah stok produk terkait.
   - Mencatat kartu stok masuk (`sumber = 'PRODUKSI'`).

---

## BAB 7: Alur Pengadaan Beras SPHP dari BULOG (Batas 2 Ton)

### Regulasi Kuota 2 Ton:
RPK Humaira memiliki kuota pengambilan maksimal **2.000 Kg (2 Ton = 400 sak @ 5kg) per minggu** (dihitung dari Senin sampai Minggu).

### Siklus 7 Langkah Pengadaan:
1. **Deteksi Stok Menipis/Habis:** Sistem menampilkan notifikasi banner di dashboard dan halaman stok.
2. **Pengajuan Pesanan:** Petugas membuat order pengadaan dengan mencantumkan Nomor Referensi pesanan di aplikasi resmi SPHP BULOG. Sistem menghitung sisa kuota minggu berjalan.
3. **Persetujuan Cabang BULOG:** Menunggu kantor cabang BULOG menyetujui kuota (Status: *Disetujui*).
4. **Pembayaran Transfer:** Pemilik mencatat bukti pembayaran transfer rekening resmi BULOG (Status: *Dibayar*).
5. **Penerbitan Surat DO/SPPB:** BULOG menerbitkan Delivery Order (Status: *Surat Jalan Terbit*).
6. **Pengambilan di Gudang BULOG:** Petugas/driver mengambil beras di gudang BULOG membawa surat DO.
7. **Penerimaan di Toko & Update Stok:** Pemilik menekan tombol konfirmasi barang tiba di toko. **Stok beras SPHP di sistem otomatis bertambah dan kartu stok dicatat**.

---

## BAB 8: Monitoring & Kartu Mutasi Stok Beras

1. **Monitoring Stok Terpisah:** Halaman stok memisahkan tabel stok SPHP, Medium, dan Premium beserta indikator status (Aman, Menipis, Habis).
2. **Kartu Stok (Audit Trail):** Menampilkan riwayat kronologis setiap mutasi masuk dan keluar, kode referensi, saldo sebelum, saldo sesudah, dan petugas yang bertransaksi.
3. **Stock Opname:** Fasilitas koreksi stok fisik berkala jika terjadi susut gabah atau kemasan rusak.

---

## BAB 9: Pencatatan Laporan & Analisis Omzet

Sistem menyediakan 4 laporan komprehensif yang siap dicetak (Print-Friendly):
1. **Laporan Penjualan:** Harian, rentang tanggal, dan bulanan, dengan filter jenis beras (SPHP / Medium / Premium).
2. **Laporan Realisasi Kuota SPHP Mingguan:** Menampilkan tabel serapan kuota per minggu kalender, tonase terpakai, sisa alokasi, progress bar, dan status kepatuhan batas 2 Ton.
3. **Laporan Pergerakan Stok:** Rekapitulasi pergerakan barang masuk vs barang keluar.
4. **Laporan Omzet & Laba:** Khusus pemilik untuk menganalisis pendapatan kotor, modal HPP, margin laba bersih, dan persentase kontribusi omzet per kategori beras.

*Buku Panduan Pengguna Sistem Informasi RPK Humaira &copy; 2026*
