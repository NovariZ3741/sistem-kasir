@php $rp = fn ($n) => number_format($n, 0, ',', '.'); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->invoice_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f1f3f5; }
        .receipt {
            width: 320px; margin: 20px auto; background: #fff; padding: 16px;
            font-family: 'Courier New', monospace; font-size: 13px; color: #000;
        }
        .receipt hr { border-top: 1px dashed #000; opacity: 1; margin: 8px 0; }
        .between { display: flex; justify-content: space-between; gap: 8px; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .receipt { margin: 0; width: 100%; padding: 0; }
            @page { margin: 5mm; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="text-center">
            <strong style="font-size:16px">{{ strtoupper(config('app.name')) }}</strong><br>
            <small>Struk Belanja</small>
        </div>
        <hr>
        <div class="between"><span>No</span><span>{{ $transaction->invoice_no }}</span></div>
        <div class="between"><span>Tanggal</span><span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="between"><span>Kasir</span><span>{{ $transaction->user->name }}</span></div>
        <hr>

        @foreach($transaction->items as $item)
            <div>{{ $item->product_name }}</div>
            <div class="between">
                <span>{{ $item->qty }} x {{ $rp($item->price) }}</span>
                <span>{{ $rp($item->price * $item->qty) }}</span>
            </div>
            @if($item->discount_amount > 0)
                <div class="between"><span>&nbsp;&nbsp;Diskon</span><span>-{{ $rp($item->discount_amount) }}</span></div>
            @endif
        @endforeach

        <hr>
        <div class="between"><span>Subtotal</span><span>{{ $rp($transaction->subtotal) }}</span></div>
        @if($transaction->discount_total > 0)
            <div class="between"><span>Diskon</span><span>-{{ $rp($transaction->discount_total) }}</span></div>
        @endif
        <div class="between"><strong>TOTAL</strong><strong>{{ $rp($transaction->total) }}</strong></div>
        <div class="between"><span>Bayar</span><span>{{ $rp($transaction->paid) }}</span></div>
        <div class="between"><span>Kembali</span><span>{{ $rp($transaction->change_amount) }}</span></div>
        <hr>
        <div class="text-center">Terima kasih atas kunjungan Anda</div>
    </div>

    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Cetak Struk</button>
        <a href="{{ route('pos.index') }}" class="btn btn-success">Transaksi Baru</a>
    </div>
</body>
</html>