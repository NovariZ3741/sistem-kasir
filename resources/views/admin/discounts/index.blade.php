@extends('layouts.app')
@section('title', 'Diskon')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Daftar Diskon</h3>
    <a href="{{ route('admin.discounts.create') }}" class="btn btn-primary">+ Tambah Diskon</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>Berlaku untuk</th>
                    <th>Potongan</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($discounts as $d)
                @php
                    $badge = [
                        'berlaku'   => 'bg-success',
                        'terjadwal' => 'bg-info text-dark',
                        'berakhir'  => 'bg-secondary',
                        'nonaktif'  => 'bg-dark',
                    ][$d->status];
                @endphp
                <tr>
                    <td>{{ $d->name }}</td>
                    <td>{{ $d->product?->name ?? 'Semua produk' }}</td>
                    <td>
                        @if($d->type === 'percent')
                            {{ $d->value }}%
                        @else
                            Rp {{ number_format($d->value, 0, ',', '.') }}
                        @endif
                    </td>
                    <td>{{ $d->start_date->format('d/m/Y') }} - {{ $d->end_date->format('d/m/Y') }}</td>
                    <td><span class="badge {{ $badge }}">{{ ucfirst($d->status) }}</span></td>
                    <td class="text-end text-nowrap">
                        <form action="{{ route('admin.discounts.toggle', $d) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-secondary">
                                {{ $d->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <a href="{{ route('admin.discounts.edit', $d) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('admin.discounts.destroy', $d) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Hapus diskon {{ $d->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada diskon.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $discounts->links() }}</div>
@endsection