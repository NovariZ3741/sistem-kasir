<?php

namespace App\Http\Controllers;

use App\Models\AprioriRun;
use App\Models\AssociationRule;
use App\Models\Discount;
use App\Models\Product;
use App\Services\CheckoutService;
use DomainException;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(CheckoutService $checkout)
    {
        $discounts = Discount::active()->get();

        // Untuk toko kecil, semua produk dimuat sekali lalu dicari di sisi browser (cepat)
        $products = Product::query()
            ->withSum(['batches as sellable_stock' => fn ($q) => $q->sellable()], 'qty')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'price' => $p->price,
                'discount' => $checkout->bestDiscountPerUnit($p, $discounts),
                'stock' => (int) $p->sellable_stock,
            ])
            ->values();

        $recommendationRules = $this->recommendationRules();

        return view('pos.index', compact('products', 'recommendationRules'));
    }

    /**
     * Aturan asosiasi dari perhitungan Apriori terbaru (hanya yang berhubungan positif / lift > 1).
     *
     * @return list<array{a: list<int>, c: list<int>, conf: float, lift: float}>
     */
    private function recommendationRules(): array
    {
        $run = AprioriRun::latestRun();

        if (! $run) {
            return [];
        }

        return $run->rules()
            ->where('lift', '>', 1)
            ->orderByDesc('confidence')
            ->get()
            ->map(fn (AssociationRule $rule) => [
                'a' => $rule->antecedent,
                'c' => $rule->consequent,
                'conf' => round($rule->confidence, 4),
                'lift' => round($rule->lift, 2),
            ])
            ->all();
    }

    public function checkout(Request $request, CheckoutService $service)
    {
        $data = $request->validate([
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.product_id' => ['required', 'integer'],
            'cart.*.qty' => ['required', 'integer', 'min:1'],
            'paid' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $transaction = $service->checkout($request->user(), $data['cart'], $data['paid']);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'receipt_url' => route('transactions.receipt', $transaction),
        ]);
    }
}
