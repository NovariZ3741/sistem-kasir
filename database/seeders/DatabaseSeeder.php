<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun
        User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@toko.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir@toko.test',
            'password' => Hash::make('password'),
            'role' => 'kasir',
        ]);

        // Contoh produk + batch
        $items = [
            ['8991001', 'Indomie Goreng',        'pcs', 3500,  [[100, '+8 months'], [60, '+10 months']]],
            ['8991002', 'Beras Premium 5kg',     'karung', 68000, [[20, '+12 months']]],
            ['8991003', 'Minyak Goreng 1L',      'pcs', 17500, [[40, '+6 months']]],
            ['8991004', 'Gula Pasir 1kg',        'pcs', 16000, [[3, '+9 months']]],   // stok menipis
            ['8991005', 'Susu UHT Coklat 250ml', 'pcs', 6500,  [[24, '+5 days'], [48, '+4 months']]], // hampir kadaluarsa
            ['8991006', 'Teh Botol 450ml',       'pcs', 5000,  [[36, '+7 months']]],
        ];

        foreach ($items as [$sku, $name, $unit, $price, $batches]) {
            $product = Product::create([
                'sku' => $sku,
                'name' => $name,
                'unit' => $unit,
                'price' => $price,
                'min_stock' => 5,
            ]);

            foreach ($batches as [$qty, $expiry]) {
                $product->batches()->create([
                    'qty' => $qty,
                    'expired_at' => now()->modify($expiry)->toDateString(),
                    'received_at' => now()->toDateString(),
                ]);
            }
        }

        // Contoh diskon: 10% untuk Indomie Goreng, berlaku 7 hari
        Discount::create([
            'name' => 'Promo Indomie',
            'product_id' => Product::where('sku', '8991001')->value('id'),
            'type' => 'percent',
            'value' => 10,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'is_active' => true,
        ]);
    }
}