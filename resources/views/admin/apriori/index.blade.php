@extends('layouts.app')
@section('title', 'Analisis Apriori')

@php
    $pct = fn ($v, $d = 1) => number_format($v * 100, $d, ',', '.') . '%';
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Analisis Pola Belanja</h3>
        <div class="text-muted">Market Basket Analysis dengan Algoritma Apriori</div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Form parameter --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-sliders me-1"></i> Parameter Perhitungan</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.apriori.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Minimum Support (%)</label>
                            <input type="number" step="0.1" min="0.1" max="100" name="min_support"
                                   class="form-control @error('min_support') is-invalid @enderror"
                                   value="{{ old('min_support', $defaults['min_support'] + 0) }}" required>
                            @error('min_support')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Minimum Confidence (%)</label>
                            <input type="number" step="1" min="1" max="100" name="min_confidence"
                                   class="form-control @error('min_confidence') is-invalid @enderror"
                                   value="{{ old('min_confidence', $defaults['min_confidence'] + 0) }}" required>
                            @error('min_confidence')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Dari tanggal</label>
                            <input type="date" name="date_from" class="form-control @error('date_from') is-invalid @enderror"
                                   value="{{ old('date_from', $defaults['date_from']) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Sampai tanggal</label>
                            <input type="date" name="date_to" class="form-control @error('date_to') is-invalid @enderror"
                                   value="{{ old('date_to', $defaults['date_to']) }}">
                            @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-text mb-3">Kosongkan tanggal untuk memakai seluruh data transaksi.</div>
                    <button class="btn btn-primary w-100"><i class="bi bi-play-fill me-1"></i> Proses Apriori</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Penjelasan singkat --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-1"></i> Cara Membaca Hasil</div>
            <div class="card-body small">
                <p class="mb-2">Setiap transaksi dianggap satu <strong>keranjang belanja</strong>. Apriori mencari kombinasi produk
                    yang sering dibeli bersamaan, lalu membentuk aturan <strong>Jika membeli A &rarr; maka membeli B</strong>.</p>
                <ul class="mb-2 ps-3">
                    <li><strong>Support</strong> = jumlah transaksi yang memuat itemset &divide; total transaksi.</li>
                    <li><strong>Confidence (A&rarr;B)</strong> = support(A &cup; B) &divide; support(A). Dari pembeli A, berapa persen juga membeli B.</li>
                    <li><strong>Lift (A&rarr;B)</strong> = confidence &divide; support(B). <span class="badge bg-success">&gt; 1</span> hubungan positif,
                        <span class="badge bg-secondary">= 1</span> tidak berhubungan, <span class="badge bg-danger">&lt; 1</span> hubungan negatif.</li>
                </ul>
                <p class="mb-0 text-muted">Hasil terbaru otomatis dipakai sebagai <strong>rekomendasi produk di halaman Kasir</strong>
                    dan diperbarui setiap malam oleh scheduler (<code>php artisan apriori:run</code>).</p>
            </div>
        </div>
    </div>
</div>

@if($run)
    {{-- Ringkasan --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
        <h5 class="mb-0">Hasil Perhitungan #{{ $run->id }}
            <small class="text-muted fw-normal">&middot; {{ $run->created_at->format('d/m/Y H:i') }} &middot; {{ $run->periodLabel() }}</small>
        </h5>
        <span class="text-muted small">Support &ge; {{ $pct($run->min_support) }}, Confidence &ge; {{ $pct($run->min_confidence, 0) }}, waktu proses {{ $run->duration_ms }} ms</span>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Total transaksi', $run->transaction_count, 'bi-receipt'],
            ['Min. support count', $run->min_support_count . ' transaksi', 'bi-funnel'],
            ['Frequent itemset', $run->itemset_count, 'bi-collection'],
            ['Aturan asosiasi', $run->rule_count, 'bi-diagram-3'],
        ] as [$label, $value, $icon])
            <div class="col-md-3 col-6">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted small"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</div>
                    <div class="fs-4 fw-bold">{{ $value }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    @if($run->transaction_count === 0)
        <div class="alert alert-warning">Belum ada transaksi pada periode ini, sehingga tidak ada pola yang bisa dianalisis.</div>
    @endif

    {{-- Aturan asosiasi --}}
    <div class="card mb-4">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-diagram-3 me-1"></i> Aturan Asosiasi</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Jika membeli</th><th></th><th>Maka membeli</th>
                        <th class="text-end">Support</th><th class="text-end">Confidence</th><th class="text-end">Lift</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rules as $i => $rule)
                    @php [$liftText, $liftColor] = $rule->liftLabel(); @endphp
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><span class="fw-semibold">{{ $rule->antecedent_label }}</span></td>
                        <td class="text-muted"><i class="bi bi-arrow-right"></i></td>
                        <td>
                            <span class="fw-semibold">{{ $rule->consequent_label }}</span>
                            <div class="small text-muted">{{ $pct($rule->confidence, 0) }} pembeli {{ $rule->antecedent_label }} juga membeli {{ $rule->consequent_label }}</div>
                        </td>
                        <td class="text-end">{{ $pct($rule->support) }}<div class="small text-muted">{{ $rule->support_count }} trx</div></td>
                        <td class="text-end">
                            {{ $pct($rule->confidence) }}
                            <div class="progress mt-1" style="height:4px"><div class="progress-bar" style="width: {{ $rule->confidence * 100 }}%"></div></div>
                        </td>
                        <td class="text-end">{{ number_format($rule->lift, 2, ',', '.') }}<br><span class="badge bg-{{ $liftColor }}">{{ $liftText }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        Tidak ada aturan yang memenuhi minimum confidence. Coba turunkan minimum support atau confidence.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Frequent itemset per iterasi --}}
    <h5 class="mb-2">Frequent Itemset per Iterasi</h5>
    <div class="row g-4 mb-4">
        @forelse($itemsets as $size => $sets)
            <div class="col-lg-{{ $size === 1 ? 6 : 6 }}">
                <div class="card h-100">
                    <div class="card-header bg-white fw-semibold">
                        L{{ $size }} &middot; {{ $size }}-itemset <span class="badge bg-primary ms-1">{{ $sets->count() }}</span>
                    </div>
                    <div class="table-responsive" style="max-height: 360px">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Itemset</th><th class="text-end">Jumlah trx</th><th class="text-end">Support</th></tr></thead>
                            <tbody>
                            @foreach($sets as $set)
                                <tr>
                                    <td>{{ '{' . $set->label . '}' }}</td>
                                    <td class="text-end">{{ $set->support_count }}</td>
                                    <td class="text-end">{{ $pct($set->support) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-light border mb-0">Tidak ada produk yang memenuhi minimum support.</div></div>
        @endforelse
    </div>
@else
    <div class="alert alert-info"><i class="bi bi-lightbulb me-1"></i> Belum ada hasil perhitungan. Atur parameter lalu klik <strong>Proses Apriori</strong>.</div>
@endif

{{-- Riwayat --}}
@if($history->isNotEmpty())
    <div class="card">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i> Riwayat Perhitungan</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Waktu</th><th>Periode</th><th class="text-end">Support</th><th class="text-end">Confidence</th>
                        <th class="text-end">Transaksi</th><th class="text-end">Aturan</th><th>Oleh</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($history as $h)
                    <tr class="{{ $run && $run->id === $h->id ? 'table-primary' : '' }}">
                        <td>{{ $h->id }}</td>
                        <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $h->periodLabel() }}</td>
                        <td class="text-end">{{ $pct($h->min_support) }}</td>
                        <td class="text-end">{{ $pct($h->min_confidence, 0) }}</td>
                        <td class="text-end">{{ $h->transaction_count }}</td>
                        <td class="text-end">{{ $h->rule_count }}</td>
                        <td>{{ $h->user?->name ?? 'Otomatis' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.apriori.show', $h) }}" class="btn btn-sm btn-outline-primary">Lihat</a>
                            <form method="POST" action="{{ route('admin.apriori.destroy', $h) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus riwayat perhitungan ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
