<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AprioriRun;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Transaction;
use App\Models\TransactionItem;

class DashboardController extends Controller
{
    public function index()
    {
        $todayStr = today()->toDateString();

        // Ringkasan penjualan hari ini
        $todayQuery = Transaction::whereDate('created_at', $todayStr);
        $stats = [
            'sales' => (int) (clone $todayQuery)->sum('total'),
            'count' => (clone $todayQuery)->count(),
            'discount' => (int) (clone $todayQuery)->sum('discount_total'),
        ];

        // Stok menipis (hanya stok yang masih layak jual)
        $lowStock = Product::query()
            ->withSum(['batches as sellable_stock' => fn ($q) => $q->sellable()], 'qty')
            ->get()
            ->filter(fn ($p) => (int) $p->sellable_stock <= $p->min_stock)
            ->sortBy('sellable_stock')
            ->values();

        // Batch sudah kadaluarsa atau akan kadaluarsa dalam 30 hari (yang masih ada stoknya)
        $expiring = ProductBatch::with('product')
            ->where('qty', '>', 0)
            ->whereNotNull('expired_at')
            ->whereDate('expired_at', '<=', today()->addDays(30)->toDateString())
            ->orderBy('expired_at')
            ->get();

        // 5 produk terlaris hari ini
        $topProducts = TransactionItem::query()
            ->whereHas('transaction', fn ($q) => $q->whereDate('created_at', $todayStr))
            ->selectRaw('product_name, SUM(qty) as total_qty, SUM(subtotal) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Pola belanja terkuat dari perhitungan Apriori terbaru
        $aprioriRun = AprioriRun::latestRun();
        $topRules = $aprioriRun
            ? $aprioriRun->rules()->where('lift', '>', 1)->orderByDesc('lift')->orderByDesc('confidence')->limit(5)->get()
            : collect();

        return view('admin.dashboard', compact('stats', 'lowStock', 'expiring', 'topProducts', 'aprioriRun', 'topRules'));
    }
}
