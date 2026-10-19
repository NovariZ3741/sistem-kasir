@extends('layouts.app')
@php
    $isEdit = $user->exists;
    $isSelf = $isEdit && $user->id === auth()->id();
@endphp
@section('title', $isEdit ? 'Edit Akun' : 'Tambah Akun')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <h4 class="mb-4">{{ $isEdit ? 'Edit Akun' : 'Tambah Akun' }}</h4>

                <form method="POST"
                      action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="form-control @error('email') is-invalid @enderror" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror" {{ $isSelf ? 'disabled' : '' }}>
                            <option value="kasir" @selected(old('role', $user->role) === 'kasir')>Kasir</option>
                            <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                        </select>
                        @if($isSelf)
                            <input type="hidden" name="role" value="{{ $user->role }}">
                            <div class="form-text">Anda tidak bisa mengubah role akun sendiri.</div>
                        @endif
                        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ $isEdit ? 'Password Baru' : 'Password' }}</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   {{ $isEdit ? '' : 'required' }}>
                            @if($isEdit) <div class="form-text">Kosongkan jika tidak diganti.</div> @endif
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ulangi Password</label>
                            <input type="password" name="password_confirmation" class="form-control"
                                   {{ $isEdit ? '' : 'required' }}>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                               @checked(old('is_active', $user->is_active ?? true)) {{ $isSelf ? 'disabled' : '' }}>
                        <label for="is_active" class="form-check-label">Akun aktif (bisa login)</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">Batal</a>
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection