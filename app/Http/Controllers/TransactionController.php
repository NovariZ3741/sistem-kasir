<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->input('from') ?: today()->toDateString();
        $to = $request->input('to') ?: today()->toDateString();

        $base = Transaction::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            // kasir hanya melihat transaksinya sendiri
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('user_id', $request->user()->id));

        $summary = [
            'count' => (clone $base)->count(),
            'total' => (int) (clone $base)->sum('total'),
        ];

        $transactions = $base->with('user')
            ->withCount('items')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('transactions.index', compact('transactions', 'summary', 'from', 'to'));
    }

    public function receipt(Request $request, Transaction $transaction)
    {
        // Kasir hanya boleh membuka struk miliknya sendiri
        abort_unless(
            $request->user()->isAdmin() || $transaction->user_id === $request->user()->id,
            403
        );

        $transaction->load(['items', 'user']);

        return view('transactions.receipt', compact('transaction'));
    }
}