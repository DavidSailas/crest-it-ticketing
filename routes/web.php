<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Activity log hooks — these fire on every login/logout regardless of which
// controller handles auth (works out of the box with Breeze's default auth).
Event::listen(function (Login $event) {
    ActivityLog::record('login', 'Logged in', [], $event->user->id);
});

Event::listen(function (Logout $event) {
    if ($event->user) {
        ActivityLog::record('logout', 'Logged out', [], $event->user->id);
    }
});

Event::listen(function (Failed $event) {
    if ($event->user) {
        ActivityLog::record('login_failed', 'Failed login attempt', [], $event->user->id);
    }
});

Route::middleware('auth')->group(function () {
    // Dashboard — different view per role, resolved inside the controller
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tickets — available to all logged-in roles, scoped inside the controller
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/comment', [TicketController::class, 'comment'])->name('tickets.comment');

    // IT Support + Admin only
    Route::middleware('role:it_support,admin')->group(function () {
        Route::post('/tickets/{ticket}/accept', [TicketController::class, 'accept'])->name('tickets.accept');
        Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
        Route::get('/tickets/queue/poll', [TicketController::class, 'pollQueue'])->name('tickets.queue.poll');
    });

    // Admin only
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::patch('/users/{user}/vip', [UserController::class, 'updateVip'])->name('users.vip');
    });
});

require __DIR__.'/auth.php';
