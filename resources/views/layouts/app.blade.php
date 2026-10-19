<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistem Kasir') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --brand: #4f46e5; --brand-dark: #4338ca; --sidebar-bg: #1e293b; --sidebar-w: 250px; }
        body { background: #f1f5f9; }

        /* Kartu & tombol */
        .card { border: 0; border-radius: .75rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
        .card-header { border-bottom: 1px solid #e2e8f0; }
        .btn-primary { background: var(--brand); border-color: var(--brand); }
        .btn-primary:hover, .btn-primary:active { background: var(--brand-dark) !important; border-color: var(--brand-dark) !important; }
        .table > :not(caption) > * > * { padding-top: .7rem; padding-bottom: .7rem; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; bottom: 0; left: 0; width: var(--sidebar-w);
            background: var(--sidebar-bg); color: #cbd5e1; z-index: 1040;
            display: flex; flex-direction: column;
            transform: translateX(-100%); transition: transform .2s ease;
        }
        .sidebar.show { transform: none; }
        .sidebar-brand { padding: 1.25rem 1.25rem 1rem; color: #fff; font-weight: 700; font-size: 1.15rem; }
        .sidebar-brand i { color: #818cf8; }
        .sidebar-label { padding: 1rem 1.25rem .35rem; font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
        .sidebar .nav-link {
            color: #cbd5e1; padding: .6rem 1.25rem; margin: 2px .75rem;
            border-radius: .5rem; display: flex; align-items: center; gap: .75rem;
        }
        .sidebar .nav-link:hover { background: rgba(255, 255, 255, .08); color: #fff; }
        .sidebar .nav-link.active { background: var(--brand); color: #fff; }
        .sidebar-user { margin-top: auto; padding: 1rem 1.25rem; border-top: 1px solid rgba(255, 255, 255, .1); }
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, .5); z-index: 1039; }
        .sidebar-backdrop.show { display: block; }

        .topbar-mobile { background: var(--sidebar-bg); color: #fff; }

        @media (min-width: 992px) {
            .sidebar { transform: none; }
            .main-wrap { margin-left: var(--sidebar-w); }
            .topbar-mobile, .sidebar-backdrop { display: none !important; }
        }

        /* Halaman login (tanpa sidebar) */
        .guest-bg { min-height: 100vh; display: flex; align-items: center;
            background: linear-gradient(135deg, #1e293b 0%, #4f46e5 100%); }
    </style>
</head>
<body>
@auth
    <div class="sidebar-backdrop" id="backdrop"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand"><i class="bi bi-shop me-2"></i>{{ config('app.name') }}</div>

        <nav class="nav flex-column">
            <div class="sidebar-label">Transaksi</div>
            <a class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}">
                <i class="bi bi-cart3"></i> Kasir
            </a>
            <a class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}" href="{{ route('transactions.index') }}">
                <i class="bi bi-receipt"></i> Riwayat
            </a>

            @if(auth()->user()->isAdmin())
                <div class="sidebar-label">Manajemen</div>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('admin.products.*', 'admin.batches.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <i class="bi bi-box-seam"></i> Produk &amp; Stok
                </a>
                <a class="nav-link {{ request()->routeIs('admin.discounts.*') ? 'active' : '' }}" href="{{ route('admin.discounts.index') }}">
                    <i class="bi bi-tags"></i> Diskon
                </a>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
    <i class="bi bi-people"></i> Kelola Akun
</a>
            @endif
        </nav>

        <div class="sidebar-user">
            <div class="text-white fw-semibold">{{ auth()->user()->name }}</div>
            <span class="badge bg-secondary mb-2">{{ ucfirst(auth()->user()->role) }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-left me-1"></i> Keluar</button>
            </form>
        </div>
    </aside>

    <div class="main-wrap">
        <div class="topbar-mobile d-flex align-items-center px-3 py-2">
            <button class="btn btn-sm btn-outline-light me-3" id="menu-btn" type="button"><i class="bi bi-list fs-5"></i></button>
            <span class="fw-semibold">{{ config('app.name') }}</span>
        </div>

        <main class="p-3 p-lg-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
            @if($errors->any())
                <div class="alert alert-danger">Ada data yang belum benar, periksa form di bawah.</div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        const sb = document.getElementById('sidebar'), bd = document.getElementById('backdrop');
        const toggle = () => { sb.classList.toggle('show'); bd.classList.toggle('show'); };
        document.getElementById('menu-btn').addEventListener('click', toggle);
        bd.addEventListener('click', toggle);
    </script>
@else
    <div class="guest-bg">
        <div class="container">@yield('content')</div>
    </div>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>