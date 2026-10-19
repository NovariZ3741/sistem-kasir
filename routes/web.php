<?php

use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Arahkan sesuai role
    Route::get('/', fn () => redirect(AuthController::homeUrl()));

    // Kasir + admin
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])
        ->name('transactions.receipt');

    // Khusus admin
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('products', ProductController::class)->except('show');

        Route::get('products/{product}/batches', [BatchController::class, 'index'])->name('products.batches.index');
        Route::post('products/{product}/batches', [BatchController::class, 'store'])->name('products.batches.store');
        Route::delete('batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');

        Route::resource('discounts', DiscountController::class)->except('show');
        Route::patch('discounts/{discount}/toggle', [DiscountController::class, 'toggle'])->name('discounts.toggle');
    });
});