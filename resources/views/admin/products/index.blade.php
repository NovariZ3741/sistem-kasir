@extends('layouts.app')
@section('title', 'Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Daftar Produk</h3>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">+ Tambah Produk</a>
</div>

<form class="mb-3" method="GET">
    <div class="input-group">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
               placeholder="Cari nama atau kode produk...">
        <button class="btn btn-outline-secondary">Cari</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th class="text-end">Harga</th>
                    <th class="text-end">Stok</th>
                    <th>Kadaluarsa Terdekat</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $p)
                @php
                    $stock = (int) $p->total_stock;
                    $exp = $p->nearest_expiry ? \Carbon\Carbon::parse($p->nearest_expiry) : null;
                    $days = $exp ? (int) today()->diffInDays($exp, false) : null;
                @endphp
                <tr>
                    <td>{{ $p->sku }}</td>
                    <td>{{ $p->name }}</td>
                    <td class="text-end">Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                    <td class="text-end">
                        {{ $stock }} {{ $p->unit }}
                        @if($stock <= $p->min_stock)
                            <span class="badge bg-danger">Menipis</span>
                        @endif
                    </td>
                    <td>
                        @if($exp)
                            {{ $exp->format('d/m/Y') }}
                            @if($days < 0)
                                <span class="badge bg-danger">Kadaluarsa</span>
                            @elseif($days <= 30)
                                <span class="badge bg-warning text-dark">{{ $days }} hari lagi</span>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.products.batches.index', $p) }}" class="btn btn-sm btn-outline-success">Stok</a>
                        <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('admin.products.destroy', $p) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Hapus produk {{ $p->name }}? Semua batch stoknya ikut terhapus.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada produk.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $products->links() }}</div>
@endsection