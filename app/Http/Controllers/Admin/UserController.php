<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('transactions')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', [
            'user' => new User(['role' => 'kasir', 'is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', 'in:admin,kasir'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:admin,kasir'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        // Admin tidak boleh menurunkan role atau menonaktifkan akunnya sendiri
        if ($user->id !== $request->user()->id) {
            $user->role = $data['role'];
            $user->is_active = $request->boolean('is_active');
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
        }

        if ($user->transactions()->exists()) {
            return back()->with('error', 'Akun ini sudah punya riwayat transaksi, jadi tidak bisa dihapus. Nonaktifkan saja lewat tombol Edit.');
        }

        $user->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}