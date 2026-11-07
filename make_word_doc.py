# -*- coding: utf-8 -*-
"""
make_word_doc.py
Membangun dokumen Word: BUKU_PENJELASAN_KODE_POS_RPK_HUMAIRA.docx
Berisi penjelasan rinci baris demi baris, alur logika, query, dan integrasi antar file.
"""

import os
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

doc = Document()

# Page Margins
sections = doc.sections
for section in sections:
    section.top_margin = Inches(1.0)
    section.bottom_margin = Inches(1.0)
    section.left_margin = Inches(1.0)
    section.right_margin = Inches(1.0)

# Colors
CLR_PRIMARY = RGBColor(0x0F, 0x2A, 0x5C)     # Navy Dark
CLR_SECONDARY = RGBColor(0x1E, 0x3A, 0x8A)   # Royal Navy
CLR_ACCENT = RGBColor(0x05, 0x96, 0x69)      # Emerald
CLR_TEXT = RGBColor(0x1E, 0x29, 0x3B)        # Slate Dark
CLR_MUTED = RGBColor(0x47, 0x55, 0x69)       # Slate Gray

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(f'<w:tcMar {nsdecls("w")}><w:top w:w="{top}" w:type="dxa"/><w:bottom w:w="{bottom}" w:type="dxa"/><w:left w:w="{left}" w:type="dxa"/><w:right w:w="{right}" w:type="dxa"/></w:tcMar>')
    tcPr.append(tcMar)

def add_callout(text, title="CATATAN LOGIKA KODE", fill_hex="EFF6FF", border_hex="2563EB"):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    table.columns[0].width = Inches(6.5)
    
    cell = table.cell(0, 0)
    set_cell_background(cell, fill_hex)
    set_cell_margins(cell, top=90, bottom=90, left=150, right=150)
    
    tcPr = cell._tc.get_or_add_tcPr()
    borders = parse_xml(f'<w:tcBorders {nsdecls("w")}><w:top w:val="none"/><w:left w:val="single" w:sz="20" w:space="0" w:color="{border_hex}"/><w:bottom w:val="none"/><w:right w:val="none"/></w:tcBorders>')
    tcPr.append(borders)
    
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(2)
    run_t = p.add_run(f"[{title}] ")
    run_t.bold = True
    run_t.font.name = "Arial"
    run_t.font.size = Pt(9.5)
    run_t.font.color.rgb = CLR_SECONDARY
    
    run_c = p.add_run(text)
    run_c.font.name = "Arial"
    run_c.font.size = Pt(9.5)
    run_c.font.color.rgb = CLR_TEXT
    
    p_sp = doc.add_paragraph()
    p_sp.paragraph_format.space_before = Pt(0)
    p_sp.paragraph_format.space_after = Pt(3)

def add_code_block(code_str, filename=""):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    table.columns[0].width = Inches(6.5)
    
    cell = table.cell(0, 0)
    set_cell_background(cell, "F8FAFC")
    set_cell_margins(cell, top=80, bottom=80, left=140, right=140)
    
    tcPr = cell._tc.get_or_add_tcPr()
    borders = parse_xml(f'<w:tcBorders {nsdecls("w")}><w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/><w:left w:val="single" w:sz="18" w:space="0" w:color="2563EB"/><w:bottom w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/><w:right w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/></w:tcBorders>')
    tcPr.append(borders)
    
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(2)
    if filename:
        rf = p.add_run(f"// File: {filename}\n")
        rf.font.name = "Consolas"
        rf.font.size = Pt(8.5)
        rf.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)
        rf.bold = True
    
    rc = p.add_run(code_str)
    rc.font.name = "Consolas"
    rc.font.size = Pt(8.5)
    rc.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    
    p_sp = doc.add_paragraph()
    p_sp.paragraph_format.space_before = Pt(0)
    p_sp.paragraph_format.space_after = Pt(3)

def add_file_table(data):
    table = doc.add_table(rows=len(data), cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    table.columns[0].width = Inches(1.8)
    table.columns[1].width = Inches(4.7)
    
    for i, (k, v) in enumerate(data):
        row = table.rows[i]
        c0 = row.cells[0]
        c1 = row.cells[1]
        
        set_cell_background(c0, "F1F5F9")
        set_cell_background(c1, "FFFFFF" if i % 2 == 0 else "F8FAFC")
        set_cell_margins(c0, top=50, bottom=50, left=100, right=100)
        set_cell_margins(c1, top=50, bottom=50, left=100, right=100)
        
        tcPr0 = c0._tc.get_or_add_tcPr()
        borders0 = parse_xml(f'<w:tcBorders {nsdecls("w")}><w:top w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/></w:tcBorders>')
        tcPr0.append(borders0)
        
        tcPr1 = c1._tc.get_or_add_tcPr()
        borders1 = parse_xml(f'<w:tcBorders {nsdecls("w")}><w:top w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/></w:tcBorders>')
        tcPr1.append(borders1)
        
        p0 = c0.paragraphs[0]
        p0.paragraph_format.space_before = Pt(1)
        p0.paragraph_format.space_after = Pt(1)
        r0 = p0.add_run(k)
        r0.bold = True
        r0.font.name = "Arial"
        r0.font.size = Pt(9)
        r0.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
        
        p1 = c1.paragraphs[0]
        p1.paragraph_format.space_before = Pt(1)
        p1.paragraph_format.space_after = Pt(1)
        r1 = p1.add_run(v)
        r1.font.name = "Arial"
        r1.font.size = Pt(9)
        r1.font.color.rgb = CLR_TEXT
        
    p_sp = doc.add_paragraph()
    p_sp.paragraph_format.space_before = Pt(0)
    p_sp.paragraph_format.space_after = Pt(3)

def h1(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(18)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = "Arial"
    r.font.size = Pt(15)
    r.bold = True
    r.font.color.rgb = CLR_PRIMARY
    return p

def h2(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(13)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = "Arial"
    r.font.size = Pt(12)
    r.bold = True
    r.font.color.rgb = CLR_SECONDARY
    return p

def h3(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(9)
    p.paragraph_format.space_after = Pt(2)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = "Arial"
    r.font.size = Pt(10.5)
    r.bold = True
    r.font.color.rgb = CLR_ACCENT
    return p

def para(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.line_spacing = 1.15
    r = p.add_run(text)
    r.font.name = "Arial"
    r.font.size = Pt(10)
    r.font.color.rgb = CLR_TEXT
    return p

def bullet(title, desc):
    p = doc.add_paragraph(style='List Bullet')
    p.paragraph_format.space_before = Pt(1)
    p.paragraph_format.space_after = Pt(2)
    p.paragraph_format.line_spacing = 1.15
    r1 = p.add_run(title + ": ")
    r1.bold = True
    r1.font.name = "Arial"
    r1.font.size = Pt(9.5)
    r1.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    r2 = p.add_run(desc)
    r2.font.name = "Arial"
    r2.font.size = Pt(9.5)
    r2.font.color.rgb = CLR_TEXT
    return p

# ==========================================
# COVER PAGE
# ==========================================
p_cov_top = doc.add_paragraph()
p_cov_top.paragraph_format.space_before = Pt(36)
p_cov_top.alignment = WD_ALIGN_PARAGRAPH.CENTER

r_tag = p_cov_top.add_run("DOKUMENTASI KODE SUMBER & MANUAL ARSITEKTUR\n")
r_tag.font.name = "Arial"
r_tag.font.size = Pt(12)
r_tag.bold = True
r_tag.font.color.rgb = CLR_ACCENT

p_title = doc.add_paragraph()
p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
p_title.paragraph_format.space_before = Pt(12)
p_title.paragraph_format.space_after = Pt(12)
r_title = p_title.add_run("SISTEM INFORMASI POINT OF SALE (POS)\nRPK HUMAIRA")
r_title.font.name = "Arial"
r_title.font.size = Pt(24)
r_title.bold = True
r_title.font.color.rgb = CLR_PRIMARY

p_sub = doc.add_paragraph()
p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
p_sub.paragraph_format.space_before = Pt(6)
p_sub.paragraph_format.space_after = Pt(36)
r_sub = p_sub.add_run("Sahabat Rumah Pangan Kita (Perum BULOG) & Penggilingan Beras Mandiri\n"
                      "Panduan Komprehensif Penjelasan Logika Kode, Alur Data, Database & Antarmuka Tiap File")
r_sub.font.name = "Arial"
r_sub.font.size = Pt(11)
r_sub.font.color.rgb = CLR_MUTED

# Garis Pembatas Cantik
p_line = doc.add_paragraph()
p_line.alignment = WD_ALIGN_PARAGRAPH.CENTER
r_line = p_line.add_run("―" * 45)
r_line.font.color.rgb = RGBColor(0x3B, 0x82, 0xF6)
r_line.bold = True

p_meta = doc.add_paragraph()
p_meta.alignment = WD_ALIGN_PARAGRAPH.CENTER
p_meta.paragraph_format.space_before = Pt(72)
p_meta.paragraph_format.space_after = Pt(12)
r_meta = p_meta.add_run("Disusun Oleh: Tim Pengembang Aplikasi POS RPK Humaira\n"
                        "Platform: PHP Native 8.x | MariaDB/MySQL PDO | Bootstrap 5 | Vanilla CSS & JS\n"
                        "Lokasi Proyek: c:\\xampp\\htdocs\\pos\n"
                        "Tahun Terbit: September 2026")
r_meta.font.name = "Arial"
r_meta.font.size = Pt(9.5)
r_meta.font.color.rgb = CLR_MUTED

doc.add_page_break()

# ==========================================
# BAB I: PENDAHULUAN & ARSITEKTUR SISTEM
# ==========================================
h1("BAB I: PENDAHULUAN & ARSITEKTUR SISTEM")
para("Aplikasi POS RPK Humaira adalah sistem kasir, manajemen inventori, pengadaan, dan audit produksi beras yang dirancang khusus untuk Sahabat Rumah Pangan Kita (RPK) yang bermitra dengan Perum BULOG sekaligus mengoperasikan fasilitas penggilingan beras mandiri (padi/gabah menjadi beras medium dan premium).")

h2("1.1 Latar Belakang & Kebutuhan Bisnis")
bullet("Kemitraan BULOG (SPHP)", "RPK memiliki kuota distribusi Beras SPHP (Stabilisasi Pasokan dan Harga Pangan) maksimal 2.000 Kg (400 sak kemasan 5 Kg) per minggu dengan pembatasan pembelian maksimal 2 sak (10 Kg) per NIK/KTP pelanggan dalam 1 minggu.")
bullet("Produksi Giling Mandiri", "RPK juga memproduksi beras lokal sendiri (Medium & Premium) dari gabah petani, di mana setiap sesi penggilingan menghasilkan kemasan sak (5kg, 10kg, 25kg) dan literan eceran yang langsung menambah stok dagang.")
bullet("Kasir Cepat & Cetak Struk", "Layanan transaksi retail kasir cepat yang mendukung penjualan kemasan sak maupun literan, validasi kuota NIK otomatis, dan cetak struk thermal 58mm/80mm.")
bullet("Audit Stok & Akurasi Mutasi", "Pencatatan riwayat kartu stok secara realtime (log transaksi masuk, keluar, dan penyesuaian/koreksi opname) guna mencegah kehilangan dan kebocoran modal.")

h2("1.2 Arsitektur Kode (Modular Native Architecture)")
para("Aplikasi dibangun menggunakan pola arsitektur Modular Berbasis File PHP Native yang ringan, terstruktur, tanpa ketergantungan framework berat. Setiap modul memiliki folder mandiri yang memisahkan tanggung jawab logika:")
bullet("config/", "Pusat konfigurasi aplikasi, koneksi database PDO, helper function global, dan autentikasi.")
bullet("includes/", "Komponen antarmuka yang digunakan berulang kali (header, footer, sidebar, alert flash).")
bullet("assets/", "Aset statis antarmuka: style CSS modern responsif dan script JavaScript interaktif.")
bullet("modules/", "Direktori fungsionalitas bisnis: dashboard, kasir, pengadaan_sphp, produksi, produk, stok, laporan, dan users.")
bullet("database/", "Skema SQL database terstruktur dengan foreign keys dan trigger integritas.")

doc.add_page_break()

# ==========================================
# BAB II: STRUKTUR DATABASE
# ==========================================
h1("BAB II: STRUKTUR DATABASE (db_rpk_humaira.sql)")
para("Database dirancang secara relasional dengan engine InnoDB pada MySQL/MariaDB. Integritas data dijaga melalui constraint foreign key, tipe data yang presisi, dan pencatatan audit trail stok.")

h2("2.1 Daftar Tabel & Fungsinya")
add_file_table([
    ("pengaturan", "Menyimpan profil toko RPK: nama toko, nama pemilik, nomor telepon, alamat, kuota mingguan BULOG, batas pembelian NIK."),
    ("users", "Menyimpan data otentikasi akun pengguna (kasir dan pemilik), password hash bcrypt, status aktif, dan data sesi."),
    ("kategori", "Kategori beras yang diperjualbelikan: SPHP (BULOG), MEDIUM (Giling Sendiri), dan PREMIUM (Giling Sendiri)."),
    ("produk", "Master data produk beras: kode produk, nama, kategori, kemasan/satuan, harga modal (HPP), harga jual, dan stok realtime."),
    ("pengadaan_sphp", "Pencatatan pengajuan order beras SPHP ke Perum BULOG (nomor DO, tanggal order, kuantiti sak/kg, status pengiriman)."),
    ("produksi_giling", "Pencatatan proses penggilingan gabah menjadi beras: tanggal giling, berat gabah (kg), varietas padi, hasil sak & liter."),
    ("penjualan", "Header transaksi kasir: nomor faktur, tanggal waktu transaksi, identitas pembeli (NIK, nama, HP), total bayar, kasir bertugas."),
    ("penjualan_detail", "Rincian item belanja pada faktur: produk ID, kuantiti, harga satuan, HPP saat transaksi, dan subtotal."),
    ("stok_riwayat", "Tabel ledger/audit mutasi stok: mencatat setiap pergerakan MASUK (+), KELUAR (-), stok sebelum, stok sesudah, dan referensi transaksi."),
    ("kuota_mingguan_bulog", "Rekap mingguan realisasi penyerapan kuota 2 ton dari BULOG guna monitoring kepatuhan distribusi.")
])

add_callout(
    "Setiap pergerakan stok baik dari Pengadaan BULOG, Produksi Giling, Penjualan Kasir, maupun Stock Opname WAJIB memasukkan rekaman ke tabel `stok_riwayat`. Hal ini menjamin angka stok pada tabel `produk` dapat diaudit dan ditelusuri kapan saja.",
    title="INTEGRITAS AUDIT TRAIL"
)

doc.add_page_break()

# ==========================================
# BAB III: MODUL KONFIGURASI SISTEM (config/)
# ==========================================
h1("BAB III: MODUL KONFIGURASI SISTEM (config/)")
para("Direktori `config/` memuat fondasi operasional seluruh aplikasi. Ketiga file di dalamnya dieksekusi pada hampir setiap request.")

h2("3.1 config/app.php - Pengaturan Global & Helper Functions")
add_file_table([
    ("Lokasi File", "c:\\xampp\\htdocs\\pos\\config\\app.php"),
    ("Fungsi Utama", "Inisialisasi konstanta dasar aplikasi, session bootstrap, timezone, serta koleksi fungsi helper pembantu format tampilan."),
    ("Hak Akses", "Dimuat otomatis oleh seluruh file aplikasi (kasir & pemilik).")
])
para("Penjelasan Logika & Baris Kunci:")
bullet("BASE_URL", "Mendefinisikan alamat dasar URL web (`http://localhost/pos` atau alamat domain hosting). Memastikan seluruh aset CSS, JS, dan link navigasi tidak rusak saat dipindahkan antar folder/server.")
bullet("date_default_timezone_set('Asia/Jakarta')", "Menetapkan zona waktu resmi Waktu Indonesia Barat (WIB) sehingga seluruh transaksi, audit log, dan laporan tercatat tepat waktu.")
bullet("Fungsi rupiah($angka)", "Memformat integer/float menjadi format mata uang Rupiah standar Indonesia, misal: `Rp 150.000`.")
bullet("Fungsi tgl_indo($tanggal)", "Mengubah format tanggal SQL `YYYY-MM-DD` menjadi format tanggal formal Indonesia, misal: `29 September 2026`.")
bullet("Fungsi set_flash_message() & get_flash_message()", "Mekanisme pengiriman pesan notifikasi satu kali (flash notification) antar halaman menggunakan session PHP (`$_SESSION['flash']`).")

add_code_block("""// Contoh implementasi helper rupiah() dan tgl_indo()
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function tgl_indo($tanggal) {
    if (!$tanggal || $tanggal == '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}""", filename="config/app.php")

h2("3.2 config/database.php - Koneksi Database PDO")
add_file_table([
    ("Lokasi File", "c:\\xampp\\htdocs\\pos\\config\\database.php"),
    ("Fungsi Utama", "Membuka koneksi ke MySQL/MariaDB menggunakan PHP Data Objects (PDO)."),
    ("Keunggulan", "Mencegah serangan SQL Injection secara native menggunakan Prepared Statements, mendukung transaksi atomik (beginTransaction/commit/rollBack).")
])
para("Penjelasan Logika & Baris Kunci:")
bullet("Kredensial Koneksi", "Host: `localhost`, Database: `pos_rpk`, User: `root`, Password: `` (kosong default XAMPP).")
bullet("PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION", "Memaksa PDO melempar pengecualian (Exception) saat query mengalami error sehingga error tidak terselubung dan proses dapat ditangkap oleh blok `try-catch`.")
bullet("PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC", "Mengembalikan hasil query langsung dalam bentuk array asosiatif (nama_kolom => nilai) yang mudah diolah.")

add_code_block("""try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}""", filename="config/database.php")

h2("3.3 config/auth.php - Manajemen Autentikasi & Hak Akses")
add_file_table([
    ("Lokasi File", "c:\\xampp\\htdocs\\pos\\config\\auth.php"),
    ("Fungsi Utama", "Pemeriksaan sesi login, otentikasi role (pemilik vs kasir), proteksi halaman rahasia, serta sinkronisasi data user aktif."),
    ("Hak Akses", "Dimuat di setiap awal modul untuk verifikasi hak akses.")
])
para("Penjelasan Logika & Baris Kunci:")
bullet("Fungsi is_logged_in()", "Memeriksa apakah `$_SESSION['user_id']` tersedia dan valid.")
bullet("Fungsi current_user()", "Mengambil data pengguna aktif. Telah disempurnakan untuk mengambil data segar langsung dari database `users` setiap request sehingga apabila nama atau profil diperbarui, seluruh antarmuka langsung mencerminkan data terbaru.")
bullet("Fungsi require_login()", "Jika pengguna belum login, otomatis dialihkan (*redirect*) ke `login.php` dengan pesan peringatan.")
bullet("Fungsi require_pemilik()", "Melindungi halaman khusus pemilik (seperti Laporan Laba/Omzet, Master User, dan Pengaturan Profil Toko). Kasir yang mencoba mengakses akan dicegat dan dilempar kembali dengan status akses ditolak.")

doc.add_page_break()

# ==========================================
# BAB IV: TEMPLATE & TAMPILAN GLOBAL (includes/ & assets/)
# ==========================================
h1("BAB IV: TEMPLATE & TAMPILAN GLOBAL (includes/ & assets/)")
para("Aplikasi menggunakan struktur template modular untuk menjaga konsistensi tampilan dan efisiensi pemeliharaan kode antarmuka.")

h2("4.1 includes/header.php & includes/sidebar.php")
para("Header memuat deklarasi DOCTYPE, pemanggilan CSS Bootstrap 5, Font Plus Jakarta Sans, dan Bootstrap Icons. Di bagian atas navbar terdapat tombol toggle sidebar untuk layar mobile, jam digital realtime, dan informasi akun pengguna yang sedang bertugas.")
para("Sidebar (`includes/sidebar.php`) menyajikan navigasi vertikal cerdas yang terorganisir per kelompok menu:")
bullet("Menu Kasir & Penjualan", "Bisa diakses oleh Kasir dan Pemilik.")
bullet("Menu Operasional", "Pengadaan SPHP BULOG, Produksi Giling Padi, Master Produk, dan Inventori Stok.")
bullet("Menu Laporan & Pengaturan", "Khusus Pemilik: Laporan Omzet & Margin Laba, Manajemen User, dan Pengaturan Profil RPK.")
bullet("Responsivitas Mobile Drawer", "Sidebar disembunyikan secara otomatis di layar kecil (< 992px) dan dapat dibuka halus sebagai drawer menggunakan tombol toggle di navbar.")

h2("4.2 includes/alerts.php & includes/footer.php")
para("`includes/alerts.php` mendeteksi apakah ada pesan flash di `$_SESSION['flash']` dan merendernya sebagai Bootstrap Alert yang dapat ditutup otomatis (*dismissible*).")
para("`includes/footer.php` menutup tag wrapper layout, memuat Bootstrap Bundle JS, script custom `assets/js/app.js`, dan script penanganan auto-hide notifikasi.")

h2("4.3 assets/css/style.css - Desain Visual, Kontras Tinggi & Aturan Cetak")
para("File stylesheet ini mengimplementasikan aturan desain modern:")
bullet("Sistem Tipografi & Warna", "Menggunakan font Plus Jakarta Sans dengan warna teks utama `#0f172a` (gelap/kontras tinggi) untuk menjamin keterbacaan di monitor toko.")
bullet("Aturan Kontras Global", "Kelas `.text-muted` dan `.text-secondary` di-override menjadi warna hitam jelas `#0f172a !important` agar seluruh informasi satuan, tanggal, dan subjudul terbaca terang tanpa buram abu-abu.")
bullet("Aturan Khusus Cetak (@media print)", "Menyembunyikan sidebar, navbar, tombol aksi, dan kartu filter (`.no-print`). Menampilkan Kop Surat Resmi RPK Humaira terpusat dengan garis ganda, mengatur tabel hemat ruang dengan border tegas hitam 1px, serta memunculkan kolom tanda tangan petugas dan pemilik toko.")

doc.add_page_break()

# ==========================================
# BAB V: AUTENTIKASI, PROFIL & BUKU PANDUAN
# ==========================================
h1("BAB V: AUTENTIKASI, PROFIL & BUKU PANDUAN")

h2("5.1 index.php, login.php & logout.php")
add_file_table([
    ("index.php", "Halaman pintu masuk utama. Memeriksa status login; jika sudah login dialihkan ke dashboard, jika belum dialihkan ke login.php."),
    ("login.php", "Formulir autentikasi. Menerima username & password, memvalidasi hash menggunakan `password_verify()`, dan merekam sesi login."),
    ("logout.php", "Menghancurkan seluruh variabel sesi (`session_destroy()`), menghapus cookie sesi, dan mengalihkan user ke halaman login.")
])
add_callout(
    "Password dienkripsi menggunakan algoritma `PASSWORD_BCRYPT` standar industri. Password asli tidak pernah disimpan dalam bentuk teks polos (plain-text) di database.",
    title="KEAMANAN PASSWORD"
)

h2("5.2 profil.php - Pengaturan Toko RPK & Sinkronisasi Nama Pemilik")
add_file_table([
    ("Lokasi File", "c:\\xampp\\htdocs\\pos\\profil.php"),
    ("Fungsi Utama", "Mengelola informasi legalitas RPK Humaira (Nama Toko, Mitra BULOG, Alamat, No Telepon, Kuota SPHP Mingguan) dan data akun pemilik."),
    ("Fitur Sinkronisasi", "Ketika pemilik memperbarui nama toko atau nama pemilik, sistem secara otomatis mensinkronisasikan data ke tabel `pengaturan`, tabel `users`, dan variabel sesi `$_SESSION['nama_lengkap']` sehingga badge profil di header langsung berubah saat itu juga.")
])

h2("5.3 manual_book.php - Panduan Pengguna Interaktif di Dalam Aplikasi")
para("File ini menyediakan buku panduan digital lengkap yang dapat dibaca kapan saja oleh kasir maupun pemilik langsung dari antarmuka web. Memuat tutorial bergambar dan petunjuk langkah demi langkah operasional toko.")

doc.add_page_break()

# ==========================================
# BAB VI: MODUL DASHBOARD (modules/dashboard/)
# ==========================================
h1("BAB VI: MODUL DASHBOARD (modules/dashboard/index.php)")
add_file_table([
    ("Lokasi File", "c:\\xampp\\htdocs\\pos\\modules\\dashboard\\index.php"),
    ("Fungsi Utama", "Menyajikan ringkasan eksekutif performa bisnis toko secara realtime."),
    ("Akses Pengguna", "Kasir (tampilan terbatas omzet penjualan) dan Pemilik (akses penuh seluruh metrik).")
])
para("Komponen & Query Utama pada Dashboard:")
bullet("Kartu Omzet Hari Ini", "Menghitung total penjualan tanggal berjalan menggunakan query `SUM(total_harga)` dari tabel `penjualan`.")
bullet("Volume Beras Terjual Hari Ini", "Mengagregasikan jumlah sak beras kemasan dan literan eceran yang keluar hari ini.")
bullet("Monitoring Kuota SPHP Mingguan", "Menampilkan progress bar pencapaian penyerapan kuota SPHP BULOG minggu berjalan (terhadap batas 2.000 Kg).")
bullet("Peringatan Stok Kritis (Restock Alert)", "Menampilkan daftar beras yang sisa stok fisiknya sudah berada di bawah ambang batas minimal (`stok <= stok_minimum`).")
bullet("Tabel Transaksi Terakhir", "Daftar 5 faktur penjualan terbaru yang baru saja diproses di meja kasir.")

doc.add_page_break()

# ==========================================
# BAB VII: MODUL KASIR & PENJUALAN (modules/kasir/)
# ==========================================
h1("BAB VII: MODUL KASIR & PENJUALAN (modules/kasir/)")
para("Modul kasir adalah ujung tombak operasional toko RPK Humaira. Dirancang untuk kecepatan transaksi dan kepatuhan terhadap regulasi distribusi beras BULOG.")

h2("7.1 modules/kasir/index.php - Antarmuka Transaksi Kasir")
para("Antarmuka kasir dibagi menjadi dua kolom utama:")
bullet("Kolom Kiri (Katalog Produk)", "Menampilkan kartu produk beras per kategori (SPHP, Medium, Premium). Dilengkapi tab filter kategori, indikator stok realtime, dan tombol cepat tambah ke keranjang.")
bullet("Kolom Kanan (Keranjang & Pembayaran)", "Menampilkan daftar belanjaan, kuantiti sak/liter, subtotal, input NIK dan nama pembeli (khusus SPHP), input nominal uang tunai dibayarkan, serta kalkulasi otomatis uang kembalian.")

h2("7.2 modules/kasir/cek_kuota_sphp.php - Endpoint Validasi NIK Pelanggan")
add_file_table([
    ("Lokasi File", "c:\\xampp\\htdocs\\pos\\modules\\kasir\\cek_kuota_sphp.php"),
    ("Fungsi Utama", "Menghitung riwayat pembelian Beras SPHP BULOG seorang pelanggan berdasarkan nomor NIK dalam 7 hari terakhir secara asinkron (AJAX)."),
    ("Aturan BULOG", "Maksimal 2 sak (10 Kg) per NIK per minggu. Jika kuota sudah habis, kasir dicegah melanjutkan transaksi untuk NIK tersebut.")
])

add_code_block("""// Logika pengecekan kuota SPHP per NIK dalam 7 hari terakhir
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(pd.jumlah), 0) AS total_sak_minggu_ini
    FROM penjualan_detail pd
    JOIN penjualan p ON pd.penjualan_id = p.id
    JOIN produk pr ON pd.produk_id = pr.id
    JOIN kategori k ON pr.kategori_id = k.id
    WHERE p.nik_pembeli = ? 
      AND k.kode = 'SPHP'
      AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmt->execute([$nik]);
$terpakai = (int)$stmt->fetchColumn();
$sisa_kuota = max(0, 2 - $terpakai);""", filename="modules/kasir/cek_kuota_sphp.php")

h2("7.3 modules/kasir/proses.php - Eksekusi Transaksi Atomik (Checkout Engine)")
para("File ini mengeksekusi proses checkout paling krusial dalam sistem. Menggunakan Database Transaction (`$pdo->beginTransaction()`) untuk menjamin konsistensi data:")
bullet("Langkah 1: Generate Nomor Faktur Unik", "Format `TRX-YYYYMMDD-XXX` (contoh: `TRX-20260928-001`).")
bullet("Langkah 2: Validasi Stok Gudang", "Memeriksa apakah stok saat ini mencukupi kuantiti yang diminta kasir.")
bullet("Langkah 3: Insert Header Penjualan", "Menyimpan faktur ke tabel `penjualan`.")
bullet("Langkah 4: Loop Item Belanja & Potong Stok", "Memotong stok di tabel `produk` (`stok = stok - ?`) dan mencatat rincian ke `penjualan_detail`.")
bullet("Langkah 5: Catat Log Audit Stok", "Menulis riwayat mutasi KELUAR (-) ke tabel `stok_riwayat` dengan mencatat stok_sebelum dan stok_sesudah.")
bullet("Langkah 6: Commit Transaksi", "Menyimpan seluruh perubahan secara permanen. Jika terjadi error di tengah jalan, seluruh perubahan di-`rollBack()` sehingga database tidak pernah mengalami inkonsistensi.")

h2("7.4 modules/kasir/riwayat.php & modules/kasir/struk.php")
para("`riwayat.php` menampilkan arsip seluruh faktur transaksi dengan filter tanggal dan pencarian. Dari sini kasir dapat melihat rincian barang belanjaan atau mencetak ulang struk.")
para("`struk.php` adalah tampilan cetak nota belanja thermal (58mm/80mm). Menggunakan font monospace `Courier New` dengan garis pembatas putus-putus (`dashed`), rincian item, uang tunai, kembalian, dan ucapan terima kasih.")

doc.add_page_break()

# ==========================================
# BAB VIII: MODUL PENGADAAN SPHP BULOG (modules/pengadaan_sphp/)
# ==========================================
h1("BAB VIII: MODUL PENGADAAN SPHP BULOG (modules/pengadaan_sphp/)")
para("Modul ini mengelola siklus pemesanan beras SPHP dari Perum BULOG mulai dari surat jalan, order pembelian, hingga penerimaan fisik barang di gudang.")

h2("8.1 modules/pengadaan_sphp/index.php & tambah.php")
para("`index.php` menampilkan daftar seluruh pesanan pengadaan beserta statusnya: `PENGAJUAN`, `DISETUJUI`, `DIKIRIM`, atau `SELESAI`. Dilengkapi kartu rekap total pengadaan bulan berjalan.")
para("`tambah.php` adalah formulir pembuatan surat pesanan pengadaan baru: memilih produk Beras SPHP 5 Kg, jumlah sak dipesan, harga tebus BULOG, dan nomor DO.")

h2("8.2 modules/pengadaan_sphp/update_status.php - Otomasi Penambahan Stok")
para("Ketika pesanan fisik beras dari BULOG telah tiba di toko, pengguna mengubah status pesanan menjadi `SELESAI`. File ini secara otomatis:")
bullet("Menambah Stok Produk Beras SPHP", "Menambahkan jumlah sak yang diterima ke tabel `produk`.")
bullet("Mencatat Kartu Stok Riwayat", "Merekam mutasi MASUK (+) dengan sumber `PENGADAAN_BULOG` ke tabel `stok_riwayat`.")

add_code_block("""// Cuplikan update_status.php saat status berubah ke 'SELESAI'
if ($status_baru === 'SELESAI' && $status_lama !== 'SELESAI') {
    // 1. Ambil data produk
    $stok_sebelum = $produk['stok'];
    $stok_sesudah = $stok_sebelum + $order['jumlah_sak'];
    
    // 2. Tambah stok
    $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?")->execute([$order['jumlah_sak'], $produk['id']]);
    
    // 3. Catat kartu stok riwayat MASUK
    $pdo->prepare("
        INSERT INTO stok_riwayat (produk_id, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, referensi_kode, sumber, keterangan)
        VALUES (?, 'MASUK', ?, ?, ?, ?, 'PENGADAAN_BULOG', ?)
    ")->execute([$produk['id'], $order['jumlah_sak'], $stok_sebelum, $stok_sesudah, $order['nomor_order'], 'Penerimaan Pengadaan SPHP BULOG']);
}""", filename="modules/pengadaan_sphp/update_status.php")

doc.add_page_break()

# ==========================================
# BAB IX: MODUL PRODUKSI & GILING PADI (modules/produksi/)
# ==========================================
h1("BAB IX: MODUL PRODUKSI & GILING PADI (modules/produksi/)")
para("Mengakomodasi lini bisnis penggilingan padi mandiri RPK Humaira. Memproses gabah mentah menjadi beras kemasan Medium atau Premium siap jual.")

h2("9.1 modules/produksi/index.php & tambah.php")
para("`index.php` menampilkan riwayat seluruh batch penggilingan padi: nomor referensi (misal `PRD-20260928-001`), tanggal giling, berat gabah (kg), varietas padi, produk beras tujuan, hasil sak, hasil eceran liter, serta tombol aksi **Detail**, **Edit**, dan **Hapus**.")
para("`tambah.php` mencatat proses penggilingan baru. Begitu formulir disimpan:")
bullet("Stok Beras Bertambah", "Stok produk beras terkait bertambah sesuai jumlah sak/liter yang dihasilkan.")
bullet("Log Mutasi Otomatis", "Tercatat di `stok_riwayat` sebagai mutasi `MASUK (+)` bersumber `PRODUKSI_GILING`.")

h2("9.2 modules/produksi/edit.php - Koreksi Hasil Giling & Penyesuaian Delta Stok")
add_file_table([
    ("Lokasi File", "c:\\xampp\\htdocs\\pos\\modules\\produksi\\edit.php"),
    ("Fungsi Utama", "Memperbaiki catatan produksi jika petugas salah menginput kuantiti sak atau salah memilih produk beras."),
    ("Logika Cerdas Delta", "Menghitung selisih (delta = jumlah_baru - jumlah_lama). Jika jumlah bertambah, stok ditambah. Jika jumlah berkurang, stok dikurangi. Jika produk beras diubah, stok produk lama ditarik kembali dan stok produk baru ditambahkan secara akurat.")
])

h2("9.3 modules/produksi/hapus.php - Pembatalan Batch Giling & Rollback Stok")
para("Jika sebuah batch produksi dibatalkan atau dihapus karena duplikasi input, `hapus.php` membatalkan penambahan stok dengan cara memotong kembali stok yang sempat ditambahkan (`stok = stok - jumlah_produksi`) serta merekam log koreksi mutasi keluar.")

h2("9.4 modules/produksi/detail.php - Lembar Bukti Hasil Penggilingan")
para("Menampilkan lembar kerja hasil giling padi lengkap dengan perhitungan rendemen persentase beras terhadap gabah mentah, tombol cetak surat bukti giling (`window.print()`), tombol edit, dan tombol hapus.")

doc.add_page_break()

# ==========================================
# BAB X: MASTER PRODUK & INVENTORI STOK
# ==========================================
h1("BAB X: MASTER PRODUK & INVENTORI STOK")

h2("10.1 modules/produk/ (index.php, tambah.php, edit.php, hapus.php)")
para("Mengelola data katalog beras toko:")
bullet("Kategori Beras", "Menentukan jenis beras (SPHP BULOG, Medium Giling, Premium Giling).")
bullet("Karakteristik Kemasan", "Membedakan kemasan sak utuh (5 Kg, 10 Kg, 25 Kg) dengan beras literan eceran (`is_eceran = 1`).")
bullet("HPP & Harga Jual", "Harga Pokok Pembelian (modal) dan Harga Eceran Tertinggi (HET) / harga jual kasir.")
bullet("Stok Minimum", "Ambang batas peringatan stok hampir habis (*reorder point*).")

h2("10.2 modules/stok/ (index.php, penyesuaian.php, mutasi.php)")
add_file_table([
    ("modules/stok/index.php", "Kartu stok gudang realtime. Menampilkan status stok tiap produk (Aman, Menipis, atau Habis)."),
    ("modules/stok/penyesuaian.php", "Formulir Stock Opname fisik. Digunakan saat penghitungan fisik di gudang berbeda dengan angka di komputer akibat susut/tumpah/koreksi. Menyesuaikan stok dan merekam alasan koreksi."),
    ("modules/stok/mutasi.php", "Buku besar mutasi stok lengkap. Menelusuri seluruh riwayat masuk dan keluar per produk beras.")
])

doc.add_page_break()

# ==========================================
# BAB XI: MODUL LAPORAN LENGKAP (modules/laporan/)
# ==========================================
h1("BAB XI: MODUL LAPORAN LENGKAP (modules/laporan/)")
para("Modul laporan menyajikan data analitik dan pertanggungjawaban usaha yang telah disempurnakan dengan antarmuka cetak resmi.")

h2("11.1 modules/laporan/penjualan.php - Laporan Transaksi Penjualan")
para("Menampilkan rekapitulasi penjualan berkala (harian, rentang tanggal, bulanan, tahunan):")
bullet("Filter Fleksibel", "Filter rentang tanggal dan kategori beras (SPHP, Medium, Premium).")
bullet("Metrik Ringkasan", "Total nilai penjualan (omzet), volume sak terjual, dan volume liter eceran.")
bullet("Kolom Margin Laba", "Khusus akun pemilik, menampilkan perhitungan laba per faktur (`Subtotal - (HPP * Qty)`).")

h2("11.2 modules/laporan/stok.php - Laporan Pergerakan & Mutasi Stok Beras")
para("File ini baru saja disempurnakan total untuk menyajikan laporan audit mutasi stok yang terstruktur dan rapi:")
bullet("Garis Kolom Tegas (table-bordered)", "Seluruh sel dibatasi border rapi baik di layar maupun saat dicetak.")
bullet("Pencegahan Teks Terpotong (text-nowrap)", "Kode referensi (`PRD-20260928-002`, `SPHP-ORD-20260928-01`, `TRX-20260928-001`) dan transisi stok (`347 → 547`) tidak lagi terpotong atau patah baris.")
bullet("Kop Surat Resmi Cetak", "Menampilkan Kop Surat RPK Humaira terpusat dengan garis ganda di atas kertas cetak.")
bullet("Tabel Ringkasan Cetak", "Menggantikan kartu widget layar dengan tabel ringkasan mutasi masuk dan keluar yang hemat ruang dan tinta cetak.")
bullet("Baris Total Footer (tfoot)", "Menghitung total keseluruhan sak masuk dan keluar pada periode yang dipilih.")

h2("11.3 modules/laporan/kuota_sphp.php - Laporan Kepatuhan Kuota BULOG")
para("Menyajikan evaluasi mingguan terhadap penyerapan kuota maksimal 2.000 Kg (400 sak) Beras SPHP dari BULOG. Dilengkapi indikator persentase realisasi dan status kepatuhan distribusi.")

h2("11.4 modules/laporan/omzet.php - Laporan Analisis Omzet & Profitabilitas")
para("Menganalisis performa bisnis per kategori beras (SPHP vs Medium vs Premium):")
bullet("Kontribusi Omzet (%)", "Berapa persen kontribusi penjualan beras Medium giling dibanding SPHP BULOG.")
bullet("Total HPP & Laba Bersih", "Membedah struktur modal dan laba bersih riil yang dihasilkan toko.")

doc.add_page_break()

# ==========================================
# BAB XII: MANAJEMEN PENGGUNA & STANDAR PEMELIHARAAN
# ==========================================
h1("BAB XII: MANAJEMEN PENGGUNA & STANDAR PEMELIHARAAN")

h2("12.1 modules/users/ (index.php, tambah.php, edit.php)")
para("Mengatur aksesibilitas staf toko RPK Humaira:")
bullet("Role Pemilik (Owner)", "Akses tak terbatas ke seluruh modul sistem termasuk laporan keuangan dan manajemen user.")
bullet("Role Kasir (Cashier)", "Akses khusus meja kasir, transaksi penjualan, riwayat struk, dan panduan pengguna.")
bullet("Status Akun", "Akun kasir dapat dinonaktifkan sementara tanpa harus menghapus histori transaksi lamanya.")

h2("12.2 Prosedur Backup & Sinkronisasi Hosting (SFTP)")
para("Aplikasi mendukung alur kerja lokal (XAMPP di `c:\\xampp\\htdocs\\pos`) yang disinkronisasikan ke remote hosting (`rpk-humaira.inyourdreams.id`) menggunakan ekstensi VS Code SFTP (`uploadOnSave: true`).")
bullet("Backup Database", "Disarankan mengekspor database secara berkala melalui phpMyAdmin atau terminal mysqldump: `mysqldump -u root pos_rpk > backup.sql`.")
bullet("Keamanan Lingkungan Produksi", "Pada server publik, pastikan opsi `display_errors` pada file `config/app.php` dinonaktifkan untuk mencegah kebocoran informasi sistem.")

# Simpan Dokumen
output_path = os.path.join(os.path.dirname(__file__), "BUKU_PENJELASAN_KODE_POS_RPK_HUMAIRA.docx")
doc.save(output_path)
print(f"SUCCESS: Document successfully created at: {output_path}")
