@extends('layouts.app')
@section('title', 'Stok ' . $product->name)

@section('content')
<a href="{{ route('admin.products.index') }}" class="text-decoration-none">&larr; Kembali ke produk</a>

<div class="d-flex justify-content-between align-items-center my-3">
    <div>
        <h3 class="mb-0">{{ $product->name }}</h3>
        <span class="text-muted">{{ $product->sku }} &middot; Total stok:
            <strong>{{ $batches->sum('qty') }} {{ $product->unit }}</strong></span>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Tambah Stok Masuk</h5>
                <form method="POST" action="{{ route('admin.products.batches.store', $product) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Jumlah ({{ $product->unit }})</label>
                        <input type="number" name="qty" min="1" value="{{ old('qty') }}"
                               class="form-control @error('qty') is-invalid @enderror" required>
                        @error('qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Kadaluarsa</label>
                        <input type="date" name="expired_at" value="{{ old('expired_at') }}"
                               class="form-control @error('expired_at') is-invalid @enderror">
                        <div class="form-text">Kosongkan jika barang tidak punya kadaluarsa.</div>
                        @error('expired_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Diterima</label>
                        <input type="date" name="received_at" value="{{ old('received_at', today()->toDateString()) }}"
                               class="form-control">
                    </div>
                    <button class="btn btn-success w-100">Tambah Stok</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">Daftar Batch (urut kadaluarsa terdekat)</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Diterima</th>
                            <th>Kadaluarsa</th>
                            <th class="text-end">Sisa Qty</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($batches as $b)
                        @php $days = $b->days_to_expiry; @endphp
                        <tr class="{{ $b->qty == 0 ? 'text-muted' : '' }}">
                            <td>{{ $b->received_at->format('d/m/Y') }}</td>
                            <td>
                                @if($b->expired_at)
                                    {{ $b->expired_at->format('d/m/Y') }}
                                    @if($days < 0)
                                        <span class="badge bg-danger">Kadaluarsa</span>
                                    @elseif($days <= 30)
                                        <span class="badge bg-warning text-dark">{{ $days }} hari lagi</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">{{ $b->qty }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.batches.destroy', $b) }}" method="POST"
                                      onsubmit="return confirm('Hapus batch ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada stok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection