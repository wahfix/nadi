<?php

use App\Http\Controllers\CollateralController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
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

    // --- Modul Pinjaman ---
    Route::middleware('permission:loans.view')->prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');

        Route::middleware('permission:loans.create')->group(function () {
            Route::get('create', [LoanController::class, 'create'])->name('create');
            Route::post('/', [LoanController::class, 'store'])->name('store');
            Route::post('calculate', [LoanController::class, 'calculatePreview'])->name('calculate');
        });

        Route::get('{loan}', [LoanController::class, 'show'])->name('show');

        Route::middleware('permission:loans.edit')->group(function () {
            Route::get('{loan}/edit', [LoanController::class, 'edit'])->name('edit');
            Route::put('{loan}', [LoanController::class, 'update'])->name('update');
            Route::post('{loan}/submit', [LoanController::class, 'submit'])->name('submit');
            Route::post('{loan}/cancel', [LoanController::class, 'cancel'])->name('cancel');
        });

        Route::middleware('permission:loans.review')->group(function () {
            Route::post('{loan}/review', [LoanController::class, 'review'])->name('review');
        });

        Route::middleware('permission:loans.approve')->group(function () {
            Route::post('{loan}/approve', [LoanController::class, 'approve'])->name('approve');
            Route::post('{loan}/reject', [LoanController::class, 'reject'])->name('reject');
            Route::post('{loan}/ready', [LoanController::class, 'ready'])->name('ready');
        });

        Route::middleware('permission:loans.disburse')->group(function () {
            Route::post('{loan}/disburse', [LoanController::class, 'disburse'])->name('disburse');
        });
    });

    // --- Modul Operasional ---
    Route::middleware('permission:installments.view')
        ->get('installments', fn () => view('modules.installments.index'))
        ->name('installments.index');

    // --- Modul Pembayaran ---
    Route::middleware('permission:payments.view')->prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');

        Route::middleware('permission:payments.create')->group(function () {
            Route::get('create', [PaymentController::class, 'create'])->name('create');
            Route::post('/', [PaymentController::class, 'store'])->name('store');
        });

        Route::get('{payment}', [PaymentController::class, 'show'])->name('show');

        Route::middleware('permission:payments.receipt')->group(function () {
            Route::get('{payment}/receipt', [PaymentController::class, 'receipt'])->name('receipt');
        });

        Route::middleware('permission:payments.reverse')->group(function () {
            Route::post('{payment}/reverse', [PaymentController::class, 'reverse'])->name('reverse');
        });
    });

    // --- Modul Penagihan (Loan Collector) ---
    Route::middleware('permission:collections.view')->prefix('collections')->name('collections.')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');

        Route::middleware('permission:collections.create')->group(function () {
            Route::get('create', [CollectionController::class, 'create'])->name('create');
            Route::post('/', [CollectionController::class, 'store'])->name('store');
        });
    });

    // --- Modul Agunan (Jaminan) ---
    Route::middleware('permission:collaterals.view')->prefix('collaterals')->name('collaterals.')->group(function () {
        Route::get('/', [CollateralController::class, 'index'])->name('index');

        Route::middleware('permission:collaterals.receive')->group(function () {
            Route::get('create', [CollateralController::class, 'create'])->name('create');
            Route::post('/', [CollateralController::class, 'store'])->name('store');
        });

        Route::get('{collateral}', [CollateralController::class, 'show'])->name('show');

        Route::middleware('permission:collaterals.update_custody')->post('{collateral}/custody', [CollateralController::class, 'updateCustody'])->name('update-custody');
    });

    // --- Modul Verifikasi Identitas ---
    Route::middleware('permission:verifications.view')->prefix('verifications')->name('verifications.')->group(function () {
        Route::get('/', [VerificationController::class, 'index'])->name('index');

        Route::middleware('permission:verifications.create')->group(function () {
            Route::get('create', [VerificationController::class, 'create'])->name('create');
            Route::post('/', [VerificationController::class, 'store'])->name('store');
        });

        Route::get('{verification}', [VerificationController::class, 'show'])->name('show');
    });

    // --- Modul Pengambilan Jaminan ---
    Route::middleware('permission:releases.view')->prefix('releases')->name('releases.')->group(function () {
        Route::get('/', [ReleaseController::class, 'index'])->name('index');

        Route::middleware('permission:releases.execute')->group(function () {
            Route::get('create', [ReleaseController::class, 'create'])->name('create');
            Route::post('/', [ReleaseController::class, 'store'])->name('store');
        });

        Route::get('{release}', [ReleaseController::class, 'show'])->name('show');
        Route::get('{release}/receipt', [ReleaseController::class, 'receipt'])->name('receipt');
    });

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
