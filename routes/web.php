<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // --- Modul Nasabah ---
    Route::middleware('permission:customers.view')->prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');

        Route::middleware('permission:customers.create')->group(function () {
            Route::get('create', [CustomerController::class, 'create'])->name('create');
            Route::post('/', [CustomerController::class, 'store'])->name('store');
        });

        Route::get('{customer}', [CustomerController::class, 'show'])->name('show');

        Route::middleware('permission:customers.edit')->group(function () {
            Route::get('{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
            Route::put('{customer}', [CustomerController::class, 'update'])->name('update');
        });
    });

    // --- Modul Operasional ---
    Route::middleware('permission:loans.view')
        ->get('loans', fn () => view('modules.loans.index'))
        ->name('loans.index');

    Route::middleware('permission:installments.view')
        ->get('installments', fn () => view('modules.installments.index'))
        ->name('installments.index');

    Route::middleware('permission:payments.view')
        ->get('payments', fn () => view('modules.payments.index'))
        ->name('payments.index');

    Route::middleware('permission:collections.view')
        ->get('collections', fn () => view('modules.collections.index'))
        ->name('collections.index');

    // --- Modul Agunan ---
    Route::middleware('permission:collaterals.view')
        ->get('collaterals', fn () => view('modules.collaterals.index'))
        ->name('collaterals.index');

    Route::middleware('permission:verifications.view')
        ->get('verifications', fn () => view('modules.verifications.index'))
        ->name('verifications.index');

    Route::middleware('permission:releases.view')
        ->get('releases', fn () => view('modules.releases.index'))
        ->name('releases.index');

    // --- Modul Administrasi ---
    Route::middleware('permission:users.view')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');

        Route::middleware('permission:users.manage')->group(function () {
            Route::get('users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
        });
    });

    Route::middleware('permission:reports.view')
        ->get('reports', fn () => view('modules.reports.index'))
        ->name('reports.index');

    Route::middleware('permission:audit_logs.view')
        ->get('audit-log', fn () => view('modules.audit-log.index'))
        ->name('audit-log.index');
});

require __DIR__.'/settings.php';
