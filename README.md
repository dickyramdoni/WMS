# Warehouse Management System (WMS)

Sistem manajemen gudang berbasis PHP Native + **SQLite** + Bootstrap 5.
Mencakup: master data, multi-gudang, Purchase Order (inbound), Penerimaan Barang,
Shipping Order (outbound), Proses Pengiriman, Stok per lokasi/gudang,
Transfer/Penyesuaian Stok, Laporan, dan Manajemen User
(role: Admin, Supervisor, Staff Gudang).

Database menggunakan **SQLite** — cukup 1 file (`database/wms.sqlite`), tidak perlu
install/setup server database terpisah seperti MySQL. Database ini juga otomatis
dibuat oleh aplikasi saat pertama kali diakses.

## Cara Instalasi (XAMPP)

1. Copy folder `wms` ke `C:\xampp\htdocs\` (atau folder htdocs Anda).
2. Jalankan Apache dari XAMPP Control Panel (MySQL **tidak diperlukan** untuk versi ini).
3. Pastikan ekstensi `pdo_sqlite` aktif di PHP Anda — di XAMPP biasanya sudah aktif
   secara default. Bisa dicek lewat halaman `http://localhost/dashboard/phpinfo.php`,
   cari bagian "pdo_sqlite".
4. Pastikan folder `database/` memiliki izin tulis (write permission), karena
   file database SQLite akan dibuat otomatis di folder ini.
5. Buka `config/config.php` dan sesuaikan `APP_URL` dengan lokasi folder Anda,
   contoh: `http://localhost/wms`.
6. Buka `http://localhost/wms/install.php` di browser. Saat pertama diakses,
   aplikasi otomatis membuat file `database/wms.sqlite` beserta seluruh tabelnya
   (dari `database/wms_sqlite.sql`) — **tidak perlu import manual**. Lanjutkan
   mengisi form untuk membuat akun admin pertama.
7. **Setelah akun admin berhasil dibuat, HAPUS file `install.php`** dari server (penting untuk keamanan).
8. Login di `http://localhost/wms/login.php` menggunakan akun admin yang baru dibuat.

> **Catatan migrasi dari versi MySQL:** jika sebelumnya Anda memakai versi MySQL
> project ini, skema MySQL lama masih tersedia di `database/legacy_mysql/` untuk
> referensi, tapi versi default sekarang menggunakan SQLite. Data lama di MySQL
> tidak otomatis berpindah — ini adalah database baru yang kosong.

## Fitur Multi-Gudang

Sistem mendukung pengelolaan stok per **Gudang** (misal Gudang A, B, C),
di mana setiap gudang memiliki beberapa **Lokasi/Rak** di dalamnya. Fitur ini
sudah termasuk dalam skema default (`database/wms_sqlite.sql`) — data contoh
Gudang A/B/C otomatis dibuat saat instalasi pertama kali.

**Menu terkait:**
- **Gudang** (Master Data) — kelola daftar gudang (tambah/edit/nonaktifkan)
- **Lokasi/Rak** — setiap lokasi wajib dikaitkan ke satu gudang, dan bisa difilter per gudang
- **Stok per Gudang** — tabel pivot: setiap baris produk, setiap kolom gudang,
  menampilkan total stok produk tersebut di gudang itu (dijumlahkan dari semua
  lokasi/rak dalam gudang). Klik angkanya untuk lihat rincian per lokasi + histori
  pergerakan stok produk itu khusus di gudang tersebut.

## Cara Publish ke GitHub

Karena sekarang pakai SQLite, `config/database.php` **tidak lagi berisi kredensial**
(tidak ada username/password database) — jadi aman untuk ikut di-commit apa adanya.
Yang justru harus dijaga agar TIDAK ter-commit adalah **file database itu sendiri**
(`database/wms.sqlite`), karena itu berisi data transaksi asli (produk, stok, user,
dll). File tersebut sudah otomatis diabaikan lewat `.gitignore`.

**Langkah-langkah (pertama kali):**

```bash
cd wms
git init
git add .
git commit -m "Initial commit - WMS"
```

Lalu buat repository baru di GitHub (lewat github.com, klik "New repository",
JANGAN centang "Initialize with README" karena project sudah punya file).
Setelah repo dibuat, GitHub akan menampilkan URL-nya, contoh:
`https://github.com/username/wms.git`. Hubungkan dan push:

```bash
git remote add origin https://github.com/username/wms.git
git branch -M main
git push -u origin main
```

**Kalau nanti ada perubahan kode dan mau di-push lagi:**

```bash
git add .
git commit -m "Deskripsi perubahan"
git push
```

**Saat orang lain (atau Anda di server baru) clone project ini:**

```bash
git clone https://github.com/username/wms.git
cd wms
```

Tidak perlu setup database apapun — cukup pastikan folder `database/` writable,
lalu jalankan `install.php` seperti biasa (lihat langkah instalasi di atas).
Database SQLite baru akan otomatis dibuat kosong di server tersebut.

**Penting soal keamanan sebelum publish:**
- Pastikan `database/wms.sqlite` benar-benar tidak ter-commit. Cek dengan
  `git status` — file itu seharusnya tidak muncul di daftar perubahan setelah
  `.gitignore` aktif.
- Jika Anda TERLANJUR pernah commit `database/wms.sqlite` sebelum ada `.gitignore`
  ini (misal dari eksperimen lokal berisi data uji coba), hapus dari tracking:
  ```bash
  git rm --cached database/wms.sqlite
  git commit -m "Remove database file from tracking"
  ```
- `install.php` sebaiknya dihapus dari server **production** setelah akun admin
  dibuat (sesuai catatan di langkah instalasi), meskipun boleh tetap ada di repo GitHub.

## Cara Ubah URL/Alamat Setelah Deploy

Kalau lokasi akses berubah (dari `localhost` ke IP publik atau domain),
cukup ubah satu baris di `config/config.php`:
```php
define('APP_URL', 'http://alamat-server-anda.com/wms');
```

## Alur Kerja Utama

**Inbound (Barang Masuk):**
1. Buat Purchase Order (menu Purchase Order → Buat PO), isi supplier & item produk.
2. Saat barang tiba, buka PO tersebut → klik "Terima Barang" → isi qty diterima
   dan pilih lokasi penyimpanan. Stok otomatis bertambah dan tercatat di kartu stok.

**Outbound (Barang Keluar):**
1. Buat Shipping Order (menu Pengiriman Barang → Buat SO), isi customer & item produk.
2. Klik "Proses Pengiriman" → pilih lokasi ambil (sistem menampilkan stok tersedia
   per lokasi) dan qty yang dikirim. Stok otomatis berkurang.

**Stok:**
- Menu Stok Barang menampilkan stok real-time per lokasi.
- Kartu Stok menampilkan histori lengkap pergerakan (masuk/keluar/transfer/penyesuaian).
- Transfer/Penyesuaian Stok untuk memindahkan barang antar lokasi atau koreksi stok
  (misal hasil stock opname).

## Role & Hak Akses

| Fitur                        | Admin | Supervisor | Staff |
|-------------------------------|:-----:|:----------:|:-----:|
| Lihat semua data              | ✅    | ✅         | ✅    |
| Tambah/Edit master data        | ✅    | ✅         | ❌    |
| Hapus master data               | ✅    | ❌         | ❌    |
| Buat PO / SO                   | ✅    | ✅         | ❌    |
| Proses Terima/Kirim Barang      | ✅    | ✅         | ✅    |
| Transfer/Penyesuaian Stok       | ✅    | ✅         | ❌    |
| Manajemen User                 | ✅    | ❌         | ❌    |

## Keamanan

- Password di-hash menggunakan bcrypt (`password_hash`).
- Semua form dilindungi CSRF token.
- Akses halaman dibatasi berdasarkan role (RBAC) di setiap modul.
- Query database menggunakan PDO prepared statements (aman dari SQL Injection).

## Catatan

- Data contoh (kategori, gudang, lokasi) sudah disediakan di `database/wms_sqlite.sql`
  agar Anda bisa langsung uji coba setelah membuat produk.
- Semua nomor dokumen (PO, GR, SO) dibuat otomatis dengan format `PREFIX-YYYYMMDD-XXXX`.
- **Backup**: karena database berupa 1 file, backup cukup dengan copy file
  `database/wms.sqlite` (matikan dulu akses ke aplikasi sesaat saat copy, atau
  gunakan perintah `sqlite3 wms.sqlite ".backup backup.sqlite"` agar aman meski
  sedang diakses).
- **Batasan SQLite**: cocok untuk tim kecil-menengah dengan penggunaan bersamaan
  yang wajar. Jika suatu saat jumlah user aktif serentak sangat banyak dan sering
  menulis data di waktu yang sama, pertimbangkan migrasi ke MySQL/MariaDB
  (skema referensi lama tersedia di `database/legacy_mysql/`).
