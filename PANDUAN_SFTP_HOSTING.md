# PANDUAN KONEKSI & SINKRONISASI REAL-TIME SFTP KE HOSTING

Panduan ini digunakan untuk menghubungkan project **POS RPK Humaira** di komputer lokal ke server hosting Anda, sehingga setiap kali Anda menekan `Ctrl + S` (Save), file otomatis ter-upload ke hosting secara real-time.

---

## 1. Persiapan Ekstensi di VS Code

1. Buka menu **Extensions** di VS Code (`Ctrl + Shift + X`).
2. Cari ekstensi: **SFTP** (Direkomendasikan pembuat: **Natizyskunk** atau **Anseki**).
3. Klik **Install**.

---

## 2. Pengaturan File `.vscode/sftp.json`

File konfigurasi sudah dibuatkan di:
[`.vscode/sftp.json`](file:///c:/xampp/htdocs/pos/.vscode/sftp.json)

Buka file tersebut dan sesuaikan data akun hosting Anda:

### Contoh A: Menggunakan SFTP (SSH Port 22) - Rekomendasi
```json
{
    "name": "RPK-Humaira-Hosting",
    "host": "103.xxx.xxx.xxx",          // IP Server atau domain: misal rpkhumaira.com
    "protocol": "sftp",
    "port": 22,                          // Port default SFTP adalah 22 (atau port SSH hosting Anda)
    "username": "u1234567",              // Username cPanel / SSH hosting
    "password": "PasswordHostingAnda",   // Password akun hosting Anda
    "remotePath": "/public_html",        // Folder tujuan di hosting (biasanya /public_html atau /public_html/pos)
    "uploadOnSave": true,                // AKTIF: Otomatis upload setiap kali file disimpan (Ctrl + S)
    "useTempFile": false,
    "openSsh": false,
    "ignore": [
        "**/.vscode/**",
        "**/.git/**",
        "**/.DS_Store",
        "**/database/*.sql"
    ]
}
```

### Contoh B: Menggunakan FTP Biasa (Jika hosting belum aktif fitur SSH/SFTP)
Jika hosting Anda hanya menyediakan akun FTP biasa (port 21):
```json
{
    "name": "RPK-Humaira-Hosting",
    "host": "ftp.domainanda.com",
    "protocol": "ftp",
    "port": 21,
    "username": "user_ftp_anda",
    "password": "password_ftp_anda",
    "remotePath": "/public_html",
    "uploadOnSave": true
}
```

---

## 3. Cara Sinkronisasi Awal (Upload Semua File Pertama Kali)

Setelah data `.vscode/sftp.json` terisi dengan benar:
1. Tekan tombol `Ctrl + Shift + P` di VS Code untuk membuka **Command Palette**.
2. Ketik dan pilih: **`SFTP: Sync Local -> Remote`**.
3. Tunggu hingga proses upload seluruh file selesai.
4. Setelah sinkronisasi awal selesai, fitur **real-time upload** akan aktif: setiap kali Anda mengedit dan menyimpan file (`Ctrl + S`), file tersebut langsung terkirim ke hosting dalam 1-2 detik.

---

## 4. Pengaturan Database Multi-Environment (`config/database.php`)

Sistem telah dilengkapi pendeteksi otomatis (Localhost vs Hosting) di [`config/database.php`](file:///c:/xampp/htdocs/pos/config/database.php):
- Saat diakses di komputer lokal (`localhost`), sistem otomatis memakai database lokal XAMPP (`root`, tanpa password, `db_rpk_humaira`).
- Saat diakses di hosting, sistem otomatis memakai pengaturan hosting.

Silakan buka [`config/database.php`](file:///c:/xampp/htdocs/pos/config/database.php) baris 25-28, lalu isi kredensial MySQL cPanel hosting Anda:
```php
$db_host = 'localhost';             // Tetap 'localhost' di cPanel
$db_user = 'USER_DATABASE_HOSTING'; // Contoh: u1234567_rpk
$db_pass = 'PASSWORD_DB_HOSTING';   // Password database MySQL di cPanel
$db_name = 'NAMA_DATABASE_HOSTING'; // Contoh: u1234567_db_rpk
```

---

## 5. Import Database ke Hosting (phpMyAdmin)

1. Buka **cPanel Hosting** &rarr; masuk ke menu **phpMyAdmin**.
2. Pilih database hosting yang telah Anda buat.
3. Klik tab **Import**.
4. Pilih file: [`database/db_rpk_humaira.sql`](file:///c:/xampp/htdocs/pos/database/db_rpk_humaira.sql).
5. Klik **Go / Kirim**.
