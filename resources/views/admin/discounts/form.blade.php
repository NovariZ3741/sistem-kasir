@extends('layouts.app')
@php $isEdit = $discount->exists; @endphp
@section('title', $isEdit ? 'Edit Diskon' : 'Tambah Diskon')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-4">{{ $isEdit ? 'Edit Diskon' : 'Tambah Diskon' }}</h4>

                <form method="POST"
                      action="{{ $isEdit ? route('admin.discounts.update', $discount) : route('admin.discounts.store') }}">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label">Nama Diskon</label>
                        <input type="text" name="name" value="{{ old('name', $discount->name) }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Contoh: Promo Akhir Bulan" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Berlaku untuk</label>
                        <select name="product_id" class="form-select @error('product_id') is-invalid @enderror">
                            <option value="">Semua produk</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}"
                                    @selected(old('product_id', $discount->product_id) == $p->id)>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis Potongan</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror">
                                <option value="percent" @selected(old('type', $discount->type) === 'percent')>Persen (%)</option>
                                <option value="nominal" @selected(old('type', $discount->type) === 'nominal')>Nominal (Rp)</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" id="value-label">Nilai</label>
                            <input type="number" name="value" min="1" value="{{ old('value', $discount->value) }}"
                                   class="form-control @error('value') is-invalid @enderror" required>
                            @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date"
                                   value="{{ old('start_date', $discount->start_date?->format('Y-m-d')) }}"
                                   class="form-control @error('start_date') is-invalid @enderror" required>
                            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="end_date"
                                   value="{{ old('end_date', $discount->end_date?->format('Y-m-d')) }}"
                                   class="form-control @error('end_date') is-invalid @enderror" required>
                            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                               @checked(old('is_active', $discount->is_active ?? true))>
                        <label for="is_active" class="form-check-label">Aktif</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.discounts.index') }}" class="btn btn-light">Batal</a>
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const typeSelect = document.getElementById('type');
    const label = document.getElementById('value-label');
    function updateLabel() {
        label.textContent = typeSelect.value === 'percent'
            ? 'Nilai (1 - 100 %)'
            : 'Nilai (Rp per unit barang)';
    }
    typeSelect.addEventListener('change', updateLabel);
    updateLabel();
</script>
@endsection