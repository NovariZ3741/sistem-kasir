# Sistem Kasir Toko Grosir

Aplikasi kasir berbasis Laravel (Blade + Bootstrap + SQLite) dengan inventori
per batch (FEFO), diskon terjadwal, struk belanja, dan dua role (admin, kasir).

## Instalasi

```bash
git clone https://github.com/USERNAME/sistem-kasir.git
cd sistem-kasir
composer install
cp .env.example .env
php artisan key:generate
```

Di `.env`, pastikan:

```
DB_CONNECTION=sqlite
APP_TIMEZONE=Asia/Jakarta
APP_NAME="Nama Toko"
```

Lalu:

```bash
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Di Windows PowerShell, ganti `cp` dengan `copy` dan `touch` dengan `New-Item database/database.sqlite`.

## Akun contoh

| Role  | Email            | Password |
|-------|------------------|----------|
| Admin | admin@toko.test  | password |
| Kasir | kasir@toko.test  | password |

## Fitur

- Login dengan 2 role (admin, kasir)
- POS: pencarian produk/barcode, keranjang, diskon otomatis, struk siap cetak
- Inventori per batch dengan tanggal kadaluarsa, stok keluar otomatis FEFO
- Diskon per produk atau semua produk, persen atau nominal, dengan periode
- Dashboard: penjualan hari ini, stok menipis, barang hampir kadaluarsa
- Riwayat transaksi dengan filter tanggal
- Kelola akun kasir
