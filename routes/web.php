<?php

use App\Http\Controllers\AlterController;
use App\Http\Controllers\AlterProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredSystemController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostInvitationController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\SearchController;
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
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('alters', [AlterController::class, 'index'])->name('alters.index');
    Route::get('alters/create', [AlterController::class, 'create'])->name('alters.create');
    Route::post('alters', [AlterController::class, 'store'])->name('alters.store');
    Route::get('alters/{alter}/edit', [AlterController::class, 'edit'])->name('alters.edit');
    Route::put('alters/{alter}', [AlterController::class, 'update'])->name('alters.update');
    Route::delete('alters/{alter}', [AlterController::class, 'destroy'])->name('alters.destroy');
    Route::post('alters/{uuid}/restore', [AlterController::class, 'restore'])->name('alters.restore');

    Route::put('front', [FrontController::class, 'update'])->name('front.update');

    // Tout ce qui écrit au nom d'un alter exige un front actif.
    Route::middleware('front')->group(function () {
        Route::get('feed', [FeedController::class, 'index'])->name('feed');

        Route::post('posts', [PostController::class, 'store'])->name('posts.store');
        Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

        Route::post('posts/{post}/reaction', [ReactionController::class, 'store'])->name('reactions.store');
        Route::delete('posts/{post}/reaction', [ReactionController::class, 'destroy'])->name('reactions.destroy');

        Route::post('posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

        Route::get('posts/invitations', [PostInvitationController::class, 'index'])->name('posts.invitations');
        Route::post('posts/{post}/invitation', [PostInvitationController::class, 'accept'])->name('posts.invitation.accept');
        Route::delete('posts/{post}/invitation', [PostInvitationController::class, 'decline'])->name('posts.invitation.decline');

        Route::post('alters/{alter}/follow', [FollowController::class, 'store'])->name('follows.store');
        Route::delete('alters/{alter}/follow', [FollowController::class, 'destroy'])->name('follows.destroy');

        Route::get('follows', [FollowController::class, 'connections'])->name('follows.index');
        Route::get('follows/requests', [FollowController::class, 'requests'])->name('follows.requests');
        Route::post('follows/requests/{alter}', [FollowController::class, 'approve'])->name('follows.approve');
        Route::delete('follows/requests/{alter}', [FollowController::class, 'reject'])->name('follows.reject');
    });
});

/*
 * Surfaces publiques : uniquement des alters, jamais de système.
 */
Route::get('search', [SearchController::class, 'index'])->name('search');
Route::get('api/alters/{handle}', [AlterProfileController::class, 'lookup'])->name('alters.lookup');
Route::get('@{handle}', [AlterProfileController::class, 'show'])->name('alters.show');
