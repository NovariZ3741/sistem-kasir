@extends('layouts.app')
@section('title', 'Kelola Akun')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Kelola Akun</h3>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Tambah Akun</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Transaksi</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td>
                        {{ $u->name }}
                        @if($u->id === auth()->id()) <span class="badge bg-light text-dark border">Anda</span> @endif
                    </td>
                    <td>{{ $u->email }}</td>
                    <td><span class="badge {{ $u->isAdmin() ? 'bg-primary' : 'bg-secondary' }}">{{ ucfirst($u->role) }}</span></td>
                    <td>
                        <span class="badge {{ $u->is_active ? 'bg-success' : 'bg-dark' }}">
                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="text-end">{{ $u->transactions_count }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        @if($u->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus akun {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection