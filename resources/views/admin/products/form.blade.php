@extends('layouts.app')
@php $isEdit = $product->exists; @endphp
@section('title', $isEdit ? 'Edit Produk' : 'Tambah Produk')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-4">{{ $isEdit ? 'Edit Produk' : 'Tambah Produk' }}</h4>

                <form method="POST"
                      action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Kode / Barcode</label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                                   class="form-control @error('sku') is-invalid @enderror" required>
                            @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-7 mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="unit" value="{{ old('unit', $product->unit ?: 'pcs') }}"
                                   class="form-control @error('unit') is-invalid @enderror" required>
                            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Harga Jual (Rp)</label>
                            <input type="number" name="price" min="0" value="{{ old('price', $product->price) }}"
                                   class="form-control @error('price') is-invalid @enderror" required>
                            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Batas Stok Menipis</label>
                            <input type="number" name="min_stock" min="0" value="{{ old('min_stock', $product->min_stock ?? 5) }}"
                                   class="form-control @error('min_stock') is-invalid @enderror" required>
                            @error('min_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @unless($isEdit)
                        <hr>
                        <p class="text-muted mb-2">Stok awal (opsional, bisa ditambah nanti lewat tombol Stok)</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jumlah</label>
                                <input type="number" name="initial_qty" min="1" value="{{ old('initial_qty') }}"
                                       class="form-control @error('initial_qty') is-invalid @enderror">
                                @error('initial_qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Kadaluarsa</label>
                                <input type="date" name="expired_at" value="{{ old('expired_at') }}"
                                       class="form-control @error('expired_at') is-invalid @enderror">
                                @error('expired_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endunless

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-light">Batal</a>
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection