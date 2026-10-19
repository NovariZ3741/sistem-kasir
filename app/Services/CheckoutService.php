<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Potongan terbesar per 1 unit dari semua diskon yang berlaku
     * (diskon khusus produk atau diskon "semua produk"). Tidak ditumpuk.
     */
    public function bestDiscountPerUnit(Product $product, Collection $discounts): int
    {
        return (int) $discounts
            ->filter(fn (Discount $d) => $d->product_id === null
                || (int) $d->product_id === (int) $product->id)
            ->map(fn (Discount $d) => $d->amountFor($product->price))
            ->max();
    }

    /**
     * @param array $cartInput [['product_id' => 1, 'qty' => 2], ...]
     * @throws DomainException kalau stok kurang / uang bayar kurang
     */
    public function checkout(User $user, array $cartInput, int $paid): Transaction
    {
        // Gabungkan jika produk yang sama muncul lebih dari sekali
        $cart = collect($cartInput)
            ->groupBy('product_id')
            ->map(fn ($rows) => (int) $rows->sum('qty'));

        return DB::transaction(function () use ($user, $cart, $paid) {
            $products = Product::whereIn('id', $cart->keys())->get()->keyBy('id');
            $discounts = Discount::active()->get();

            $lines = [];
            $subtotal = 0;
            $discountTotal = 0;

            foreach ($cart as $productId => $qty) {
                $product = $products->get((int) $productId);

                if (! $product) {
                    throw new DomainException('Ada produk di keranjang yang sudah tidak tersedia.');
                }

                // FEFO: kadaluarsa terdekat lebih dulu, tanpa kadaluarsa paling akhir
                $batches = $product->batches()
                    ->sellable()
                    ->orderByRaw('expired_at IS NULL')
                    ->orderBy('expired_at')
                    ->orderBy('id')
                    ->get();

                $available = (int) $batches->sum('qty');
                if ($available < $qty) {
                    throw new DomainException(
                        "Stok {$product->name} tidak cukup (tersisa {$available} {$product->unit})."
                    );
                }

                // Potong stok dari batch
                $need = $qty;
                foreach ($batches as $batch) {
                    if ($need <= 0) {
                        break;
                    }
                    $take = min($batch->qty, $need);
                    $batch->decrement('qty', $take);
                    $need -= $take;
                }

                $perUnit = $this->bestDiscountPerUnit($product, $discounts);
                $lineGross = $product->price * $qty;
                $lineDiscount = $perUnit * $qty;

                $subtotal += $lineGross;
                $discountTotal += $lineDiscount;

                // Snapshot nama & harga supaya struk lama tidak berubah
                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'qty' => $qty,
                    'discount_amount' => $lineDiscount,
                    'subtotal' => $lineGross - $lineDiscount,
                ];
            }

            $total = $subtotal - $discountTotal;

            if ($paid < $total) {
                throw new DomainException('Uang bayar kurang dari total belanja.');
            }

            $transaction = Transaction::create([
                'invoice_no' => $this->nextInvoiceNo(),
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => $total,
                'paid' => $paid,
                'change_amount' => $paid - $total,
            ]);

            foreach ($lines as $line) {
                $transaction->items()->create($line);
            }

            return $transaction;
        });
    }

    // Format: INV-20261020-0001 (urutan reset tiap hari)
    private function nextInvoiceNo(): string
    {
        $count = Transaction::whereDate('created_at', today()->toDateString())->count();

        return 'INV-' . now()->format('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}