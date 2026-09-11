<?php

use App\Http\Controllers\AlterController;
use App\Http\Controllers\AlterProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredSystemController;
use App\Http\Controllers\FrontController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

/*
 * Authentification : le système est l'unique porte d'entrée.
 */
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredSystemController::class, 'create'])->name('register');
    Route::post('register', [RegisteredSystemController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

/*
 * Espace système authentifié : gestion des alters.
 */
Route::middleware('auth')->group(function () {
    Route::get('alters', [AlterController::class, 'index'])->name('alters.index');
    Route::get('alters/create', [AlterController::class, 'create'])->name('alters.create');
    Route::post('alters', [AlterController::class, 'store'])->name('alters.store');
    Route::get('alters/{alter}/edit', [AlterController::class, 'edit'])->name('alters.edit');
    Route::put('alters/{alter}', [AlterController::class, 'update'])->name('alters.update');
    Route::delete('alters/{alter}', [AlterController::class, 'destroy'])->name('alters.destroy');

    Route::put('front', [FrontController::class, 'update'])->name('front.update');
});

/*
 * Surfaces publiques : uniquement des alters, jamais de système.
 */
Route::get('@{handle}', [AlterProfileController::class, 'show'])->name('alters.show');
