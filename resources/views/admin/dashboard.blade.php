@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<h3 class="mb-3">Dashboard</h3>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Penjualan hari ini</div>
            <div class="fs-4 fw-bold">Rp {{ number_format($stats['sales'], 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Jumlah transaksi</div>
            <div class="fs-4 fw-bold">{{ $stats['count'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Diskon diberikan</div>
            <div class="fs-4 fw-bold text-success">Rp {{ number_format($stats['discount'], 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Produk stok menipis</div>
            <div class="fs-4 fw-bold {{ $lowStock->count() ? 'text-danger' : '' }}">{{ $lowStock->count() }}</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    {{-- Stok menipis --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Stok Menipis</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Produk</th><th class="text-end">Sisa</th><th class="text-end">Batas</th><th></th></tr>
                    </thead>
                    <tbody>
                    @forelse($lowStock as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td class="text-end">
                                <span class="badge {{ (int) $p->sellable_stock === 0 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                    {{ (int) $p->sellable_stock }} {{ $p->unit }}
                                </span>
                            </td>
                            <td class="text-end text-muted">{{ $p->min_stock }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.products.batches.index', $p) }}" class="btn btn-sm btn-outline-success">Tambah</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Semua stok aman.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Kadaluarsa --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Kadaluarsa (sudah lewat atau &le; 30 hari)</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Produk</th><th>Tanggal</th><th class="text-end">Qty</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    @forelse($expiring as $b)
                        @php $days = $b->days_to_expiry; @endphp
                        <tr>
                            <td>{{ $b->product->name }}</td>
                            <td>{{ $b->expired_at->format('d/m/Y') }}</td>
                            <td class="text-end">{{ $b->qty }}</td>
                            <td>
                                @if($days < 0)
                                    <span class="badge bg-danger">Kadaluarsa, tarik dari rak</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ $days }} hari lagi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada barang yang mendekati kadaluarsa.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Terlaris --}}
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Produk Terlaris Hari Ini</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Produk</th><th class="text-end">Terjual</th><th class="text-end">Penjualan</th></tr>
                    </thead>
                    <tbody>
                    @forelse($topProducts as $t)
                        <tr>
                            <td>{{ $t->product_name }}</td>
                            <td class="text-end">{{ $t->total_qty }}</td>
                            <td class="text-end">Rp {{ number_format($t->total_sales, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada transaksi hari ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection