@extends('layouts.app')
@section('title', 'Riwayat Transaksi')

@section('content')
<h3 class="mb-3">Riwayat Transaksi</h3>

<form method="GET" class="card shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3 col-6">
            <label class="form-label mb-1">Dari tanggal</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label mb-1">Sampai tanggal</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="form-control @error('to') is-invalid @enderror">
            @error('to') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6 d-flex gap-2">
            <button class="btn btn-primary">Tampilkan</button>
            <a href="{{ route('transactions.index') }}" class="btn btn-light">Hari ini</a>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-6">
        <div class="card shadow-sm"><div class="card-body py-2">
            <div class="text-muted small">Jumlah transaksi</div>
            <div class="fs-5 fw-bold">{{ $summary['count'] }}</div>
        </div></div>
    </div>
    <div class="col-6">
        <div class="card shadow-sm"><div class="card-body py-2">
            <div class="text-muted small">Total penjualan</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total'], 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Struk</th>
                    <th>Waktu</th>
                    <th>Kasir</th>
                    <th class="text-end">Item</th>
                    <th class="text-end">Diskon</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td>{{ $t->invoice_no }}</td>
                    <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $t->user->name }}</td>
                    <td class="text-end">{{ $t->items_count }}</td>
                    <td class="text-end">{{ $t->discount_total > 0 ? 'Rp ' . number_format($t->discount_total, 0, ',', '.') : '-' }}</td>
                    <td class="text-end fw-semibold">Rp {{ number_format($t->total, 0, ',', '.') }}</td>
                    <td class="text-end">
                        <a href="{{ route('transactions.receipt', $t) }}" target="_blank"
                           class="btn btn-sm btn-outline-primary">Lihat Struk</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $transactions->links() }}</div>
@endsection