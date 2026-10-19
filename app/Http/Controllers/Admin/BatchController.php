<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Product $product)
    {
        $batches = $product->batches()
            ->orderByRaw('expired_at IS NULL')   // yang tanpa kadaluarsa di bawah
            ->orderBy('expired_at')
            ->get();

        return view('admin.products.batches', compact('product', 'batches'));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
            'expired_at' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
        ]);

        $product->batches()->create([
            'qty' => $data['qty'],
            'expired_at' => $data['expired_at'] ?? null,
            'received_at' => $data['received_at'] ?? today(),
        ]);

        return back()->with('success', 'Stok batch baru berhasil ditambahkan.');
    }

    public function destroy(ProductBatch $batch)
    {
        $batch->delete();

        return back()->with('success', 'Batch dihapus.');
    }
}