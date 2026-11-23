<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data transaksi contoh untuk mencoba Analisis Apriori.
 *
 * Jalankan: php artisan db:seed --class=AprioriDemoSeeder
 *
 * Transaksi dibuat langsung (tanpa memotong stok) dengan nomor invoice berawalan "DEMO-",
 * sehingga mudah dikenali / dihapus kembali.
 */
class AprioriDemoSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string, 2: string, 3: int}> */
    private const PRODUCTS = [
        ['8991001', 'Indomie Goreng', 'pcs', 3500],
        ['8991002', 'Beras Premium 5kg', 'karung', 68000],
        ['8991003', 'Minyak Goreng 1L', 'pcs', 17500],
        ['8991004', 'Gula Pasir 1kg', 'pcs', 16000],
        ['8991005', 'Susu UHT Coklat 250ml', 'pcs', 6500],
        ['8991006', 'Teh Botol 450ml', 'pcs', 5000],
        ['8991007', 'Telur Ayam 1kg', 'kg', 28000],
        ['8991008', 'Roti Tawar', 'pcs', 15000],
        ['8991009', 'Selai Coklat', 'pcs', 18000],
        ['8991010', 'Kopi Bubuk 100g', 'pcs', 12000],
        ['8991011', 'Kecap Manis 220ml', 'pcs', 9000],
        ['8991012', 'Sabun Mandi', 'pcs', 4500],
    ];

    /**
     * Pola belanja: [peluang pola muncul, [sku => peluang item ikut dibeli]].
     *
     * @var array<int, array{0: float, 1: array<string, float>}>
     */
    private const PATTERNS = [
        [0.35, ['8991001' => 1.0, '8991007' => 0.7, '8991011' => 0.4, '8991006' => 0.3]], // mie + telur + kecap
        [0.25, ['8991008' => 1.0, '8991009' => 0.75, '8991005' => 0.6]],                  // roti + selai + susu
        [0.20, ['8991010' => 1.0, '8991004' => 0.8]],                                      // kopi + gula
        [0.20, ['8991002' => 1.0, '8991003' => 0.7, '8991007' => 0.3]],                    // beras + minyak
    ];

    public function run(int $transactionCount = 300, int $days = 60): void
    {
        mt_srand(2026);

        $products = collect(self::PRODUCTS)->mapWithKeys(function (array $row): array {
            [$sku, $name, $unit, $price] = $row;

            $product = Product::firstOrCreate(
                ['sku' => $sku],
                ['name' => $name, 'unit' => $unit, 'price' => $price, 'min_stock' => 5],
            );

            if ($product->wasRecentlyCreated) {
                $product->batches()->create([
                    'qty' => 100,
                    'expired_at' => now()->addMonths(6)->toDateString(),
                    'received_at' => now()->toDateString(),
                ]);
            }

            return [$sku => $product];
        });

        $userId = User::query()->where('role', 'kasir')->value('id') ?? User::query()->value('id');

        for ($i = 1; $i <= $transactionCount; $i++) {
            $basket = $this->randomBasket();
            $createdAt = now()->subDays(mt_rand(0, $days - 1))->setTime(mt_rand(8, 20), mt_rand(0, 59));

            $lines = [];
            $subtotal = 0;
            foreach ($basket as $sku) {
                $product = $products[$sku];
                $qty = mt_rand(1, 3);
                $subtotal += $product->price * $qty;
                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'qty' => $qty,
                    'discount_amount' => 0,
                    'subtotal' => $product->price * $qty,
                ];
            }

            $paid = (int) (ceil($subtotal / 10000) * 10000);

            $transaction = new Transaction([
                'invoice_no' => sprintf('DEMO-%s-%04d', $createdAt->format('Ymd'), $i),
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'total' => $subtotal,
                'paid' => $paid,
                'change_amount' => $paid - $subtotal,
            ]);
            $transaction->created_at = $createdAt;
            $transaction->updated_at = $createdAt;
            $transaction->save();

            $transaction->items()->createMany($lines);
        }
    }

    /**
     * @return list<string> daftar SKU unik dalam satu keranjang
     */
    private function randomBasket(): array
    {
        $basket = [];

        foreach (self::PATTERNS as [$chance, $items]) {
            if ($this->chance($chance)) {
                foreach ($items as $sku => $itemChance) {
                    if ($this->chance($itemChance)) {
                        $basket[$sku] = true;
                    }
                }
            }
        }

        // Tambahkan 0-1 barang acak sebagai "noise"
        if ($this->chance(0.5) || ! $basket) {
            $basket[self::PRODUCTS[mt_rand(0, count(self::PRODUCTS) - 1)][0]] = true;
        }

        return array_keys($basket);
    }

    private function chance(float $probability): bool
    {
        return mt_rand() / mt_getrandmax() < $probability;
    }
}
