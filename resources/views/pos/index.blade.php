@extends('layouts.app')
@section('title', 'Kasir')

@section('content')
<div class="row g-3">
    {{-- KIRI: pencarian produk --}}
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <input type="text" id="search" class="form-control form-control-lg" autofocus autocomplete="off"
                       placeholder="Scan barcode atau ketik nama produk, lalu Enter">
                <div class="form-text">Enter menambahkan produk pertama dari hasil pencarian (kode yang persis sama diprioritaskan).</div>
            </div>
            <div class="list-group list-group-flush" id="results" style="max-height: 60vh; overflow-y: auto;"></div>
        </div>

        {{-- Rekomendasi dari Algoritma Apriori --}}
        <div class="card shadow-sm mt-3 d-none" id="reco-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-lightbulb text-warning me-1"></i> Sering Dibeli Bersama</span>
                <small class="text-muted">berdasarkan analisis Apriori</small>
            </div>
            <div class="list-group list-group-flush" id="reco"></div>
        </div>
    </div>

    {{-- KANAN: keranjang & pembayaran --}}
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Keranjang</span>
                <button type="button" class="btn btn-sm btn-outline-danger" id="clear-btn">Kosongkan</button>
            </div>

            <div id="cart-empty" class="text-center text-muted py-4">Keranjang kosong</div>
            <div class="list-group list-group-flush" id="cart" style="max-height: 40vh; overflow-y: auto;"></div>

            <div class="card-body border-top">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span id="subtotal">Rp 0</span></div>
                <div class="d-flex justify-content-between text-success"><span>Diskon</span><span id="discount">- Rp 0</span></div>
                <div class="d-flex justify-content-between fs-4 fw-bold mt-1"><span>Total</span><span id="total">Rp 0</span></div>

                <hr>

                <label class="form-label mb-1">Uang Bayar (Rp)</label>
                <input type="number" id="paid" min="0" class="form-control form-control-lg" placeholder="0">
                <div class="d-flex gap-2 mt-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-cash="exact">Uang pas</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-cash="20000">20.000</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-cash="50000">50.000</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-cash="100000">100.000</button>
                </div>

                <div class="d-flex justify-content-between fs-5 mt-3">
                    <span id="change-label">Kembalian</span><strong id="change">Rp 0</strong>
                </div>

                <div id="error" class="alert alert-danger py-2 mt-3 mb-0 d-none"></div>

                <button type="button" id="pay-btn" class="btn btn-success btn-lg w-100 mt-3" disabled>Bayar &amp; Cetak Struk</button>
            </div>
        </div>
    </div>
</div>

<script>
    const products = @json($products);
    const rules = @json($recommendationRules); // [{a: [id], c: [id], conf, lift}]
    const maxReco = {{ (int) config('apriori.pos_recommendations', 5) }};
    const byId = Object.fromEntries(products.map(p => [p.id, p]));
    let cart = []; // [{id, qty}]

    const $ = id => document.getElementById(id);
    const rp = n => 'Rp ' + Number(n).toLocaleString('id-ID');
    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const line = id => cart.find(i => i.id === id);

    function flash(msg) {
        const el = $('error');
        el.textContent = msg;
        el.classList.remove('d-none');
        clearTimeout(flash.t);
        flash.t = setTimeout(() => el.classList.add('d-none'), 4000);
    }

    /* ---------- Hasil pencarian ---------- */
    function renderResults() {
        const q = $('search').value.trim().toLowerCase();
        const list = products
            .filter(p => !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q))
            .slice(0, 30);

        $('results').innerHTML = list.length ? list.map(p => `
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                    data-id="${p.id}" ${p.stock < 1 ? 'disabled' : ''}>
                <div>
                    <div class="fw-semibold">${esc(p.name)}</div>
                    <small class="text-muted">${esc(p.sku)} &middot; stok ${p.stock} ${esc(p.unit)}</small>
                </div>
                <div class="text-end">
                    ${p.discount
                        ? `<small class="text-decoration-line-through text-muted">${rp(p.price)}</small><br>
                           <span class="text-success fw-semibold">${rp(p.price - p.discount)}</span>`
                        : `<span class="fw-semibold">${rp(p.price)}</span>`}
                    ${p.stock < 1 ? '<br><span class="badge bg-danger">Habis</span>' : ''}
                </div>
            </button>`).join('')
            : '<div class="p-3 text-muted text-center">Produk tidak ditemukan.</div>';
    }

    /* ---------- Keranjang ---------- */
    function addToCart(id) {
        const p = byId[id];
        if (!p) return;
        if (p.stock < 1) { flash(`${p.name} sedang habis atau semua batchnya kadaluarsa.`); return; }

        const it = line(id);
        if ((it ? it.qty : 0) + 1 > p.stock) {
            flash(`Stok ${p.name} hanya ${p.stock} ${p.unit}.`);
            return;
        }
        if (it) it.qty++; else cart.unshift({ id, qty: 1 });
        renderCart();
    }

    function totals() {
        let subtotal = 0, discount = 0;
        cart.forEach(i => {
            const p = byId[i.id];
            subtotal += p.price * i.qty;
            discount += p.discount * i.qty;
        });
        return { subtotal, discount, total: subtotal - discount };
    }

    function renderCart() {
        $('cart-empty').classList.toggle('d-none', cart.length > 0);
        $('cart').innerHTML = cart.map(i => {
            const p = byId[i.id], unit = p.price - p.discount;
            return `
            <div class="list-group-item">
                <div class="d-flex justify-content-between">
                    <div class="fw-semibold">${esc(p.name)}</div>
                    <button type="button" class="btn-close" data-remove="${i.id}"></button>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <div class="input-group input-group-sm" style="width:130px">
                        <button type="button" class="btn btn-outline-secondary" data-dec="${i.id}">&minus;</button>
                        <input type="number" class="form-control text-center" value="${i.qty}" min="1" max="${p.stock}" data-qty="${i.id}">
                        <button type="button" class="btn btn-outline-secondary" data-inc="${i.id}">+</button>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">${rp(unit)} / ${esc(p.unit)}${p.discount ? ' (diskon)' : ''}</small><br>
                        <strong>${rp(unit * i.qty)}</strong>
                    </div>
                </div>
            </div>`;
        }).join('');
        updateTotals();
        renderRecommendations();
    }

    /* ---------- Rekomendasi (Apriori) ---------- */
    function renderRecommendations() {
        const inCart = new Set(cart.map(i => i.id));
        const best = {}; // product_id => {conf, lift, because}

        rules.forEach(r => {
            if (!r.a.every(id => inCart.has(id))) return;       // semua produk "jika" harus ada di keranjang
            r.c.forEach(id => {
                const p = byId[id];
                if (!p || inCart.has(id) || p.stock < 1) return;
                if (!best[id] || r.conf > best[id].conf) best[id] = { conf: r.conf, lift: r.lift, because: r.a };
            });
        });

        const list = Object.entries(best)
            .sort((x, y) => y[1].conf - x[1].conf || y[1].lift - x[1].lift)
            .slice(0, maxReco);

        $('reco-card').classList.toggle('d-none', list.length === 0);
        $('reco').innerHTML = list.map(([id, r]) => {
            const p = byId[id];
            const because = r.because.map(b => byId[b] ? byId[b].name : '').filter(Boolean).join(', ');
            return `
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-reco="${p.id}">
                <div>
                    <div class="fw-semibold">${esc(p.name)}</div>
                    <small class="text-muted">${Math.round(r.conf * 100)}% pembeli ${esc(because)} juga membeli ini</small>
                </div>
                <div class="text-end">
                    <span class="fw-semibold">${rp(p.price - p.discount)}</span><br>
                    <span class="badge bg-primary"><i class="bi bi-plus"></i> Tambah</span>
                </div>
            </button>`;
        }).join('');
    }

    function updateTotals() {
        const t = totals();
        const paid = parseInt($('paid').value) || 0;
        const diff = paid - t.total;

        $('subtotal').textContent = rp(t.subtotal);
        $('discount').textContent = '- ' + rp(t.discount);
        $('total').textContent = rp(t.total);
        $('change-label').textContent = diff < 0 ? 'Kurang' : 'Kembalian';
        $('change').textContent = rp(Math.abs(diff));
        $('pay-btn').disabled = cart.length === 0 || paid < t.total;
    }

    /* ---------- Event ---------- */
    $('search').addEventListener('input', renderResults);

    $('search').addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const q = $('search').value.trim().toLowerCase();
        if (!q) return;

        const exact = products.find(p => p.sku.toLowerCase() === q);
        const match = exact || products.find(p =>
            p.stock > 0 && (p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)));

        if (match) {
            addToCart(match.id);
            $('search').value = '';
            renderResults();
        } else {
            flash('Produk tidak ditemukan.');
        }
    });

    $('results').addEventListener('click', e => {
        const btn = e.target.closest('[data-id]');
        if (!btn) return;
        addToCart(+btn.dataset.id);
        $('search').focus();
    });

    $('reco').addEventListener('click', e => {
        const btn = e.target.closest('[data-reco]');
        if (!btn) return;
        addToCart(+btn.dataset.reco);
        $('search').focus();
    });

    $('cart').addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const d = btn.dataset;

        if (d.inc) { addToCart(+d.inc); return; }
        if (d.remove) {
            cart = cart.filter(i => i.id !== +d.remove);
        } else if (d.dec) {
            const it = line(+d.dec);
            it.qty--;
            if (it.qty < 1) cart = cart.filter(i => i !== it);
        } else {
            return;
        }
        renderCart();
    });

    $('cart').addEventListener('change', e => {
        const id = +e.target.dataset.qty;
        if (!id) return;
        const p = byId[id];
        let q = parseInt(e.target.value) || 1;
        if (q > p.stock) { q = p.stock; flash(`Stok ${p.name} hanya ${p.stock} ${p.unit}.`); }
        line(id).qty = Math.max(1, q);
        renderCart();
    });

    $('paid').addEventListener('input', updateTotals);
    $('paid').addEventListener('keydown', e => {
        if (e.key === 'Enter' && !$('pay-btn').disabled) checkout();
    });

    document.querySelectorAll('[data-cash]').forEach(b => b.addEventListener('click', () => {
        $('paid').value = b.dataset.cash === 'exact' ? totals().total : b.dataset.cash;
        updateTotals();
    }));

    $('clear-btn').addEventListener('click', () => {
        cart = [];
        $('paid').value = '';
        renderCart();
        $('search').focus();
    });

    $('pay-btn').addEventListener('click', checkout);

    /* ---------- Checkout ---------- */
    async function checkout() {
        const btn = $('pay-btn');
        btn.disabled = true;
        btn.textContent = 'Memproses...';

        try {
            const res = await fetch("{{ route('pos.checkout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    cart: cart.map(i => ({ product_id: i.id, qty: i.qty })),
                    paid: parseInt($('paid').value) || 0,
                }),
            });
            const data = await res.json();

            if (!res.ok) {
                flash(data.message || 'Transaksi gagal, coba lagi.');
                btn.textContent = 'Bayar & Cetak Struk';
                updateTotals();
                return;
            }
            window.location.href = data.receipt_url;
        } catch (err) {
            flash('Tidak bisa terhubung ke server.');
            btn.textContent = 'Bayar & Cetak Struk';
            updateTotals();
        }
    }

    renderResults();
    renderCart();
</script>
@endsection