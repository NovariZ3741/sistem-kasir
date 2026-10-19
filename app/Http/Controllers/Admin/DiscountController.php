<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = Discount::with('product')
            ->orderByDesc('end_date')
            ->paginate(15);

        return view('admin.discounts.index', compact('discounts'));
    }

    public function create()
    {
        $discount = new Discount([
            'type' => 'percent',
            'start_date' => today(),
            'end_date' => today()->addDays(7),
            'is_active' => true,
        ]);

        return view('admin.discounts.form', [
            'discount' => $discount,
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        Discount::create($this->validated($request));

        return redirect()->route('admin.discounts.index')
            ->with('success', 'Diskon berhasil ditambahkan.');
    }

    public function edit(Discount $discount)
    {
        return view('admin.discounts.form', [
            'discount' => $discount,
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Discount $discount)
    {
        $discount->update($this->validated($request));

        return redirect()->route('admin.discounts.index')
            ->with('success', 'Diskon berhasil diperbarui.');
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();

        return redirect()->route('admin.discounts.index')
            ->with('success', 'Diskon berhasil dihapus.');
    }

    public function toggle(Discount $discount)
    {
        $discount->update(['is_active' => ! $discount->is_active]);

        return back()->with('success', 'Status diskon diperbarui.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'product_id' => ['nullable', 'exists:products,id'],
            'type' => ['required', 'in:percent,nominal'],
            'value' => [
                'required', 'integer', 'min:1',
                $request->type === 'percent' ? 'max:100' : 'max:100000000',
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'value.max' => 'Nilai diskon persen maksimal 100.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $data['product_id'] = $data['product_id'] ?: null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}