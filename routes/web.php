<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupportChatController;
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

    // Profile picture
    Route::patch('/profile/avatar', [AvatarController::class, 'update'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [AvatarController::class, 'destroy'])->name('profile.avatar.destroy');

    // Tickets — available to all logged-in roles, scoped inside the controller
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}/confirmation', [TicketController::class, 'confirmation'])->name('tickets.confirmation');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/comment', [TicketController::class, 'comment'])->name('tickets.comment');
    Route::get('/tickets/{ticket}/chat/poll', [TicketController::class, 'pollChat'])->name('tickets.chat.poll');

    // Chat Support — staff's own thread with the whole IT Support team.
    Route::get('/support-chat', [SupportChatController::class, 'show'])->name('support-chat.show');
    Route::post('/support-chat', [SupportChatController::class, 'send'])->name('support-chat.send');
    Route::get('/support-chat/poll', [SupportChatController::class, 'poll'])->name('support-chat.poll');

    // Chat — one thread per ticket (the same comment thread shown on the ticket
    // page), listed here as an inbox scoped to the same tickets each role can
    // already see via /tickets.
    Route::get('/chat', [TicketController::class, 'chatIndex'])->name('chat.index');
    Route::get('/chat/poll', [TicketController::class, 'chatPoll'])->name('chat.poll');

    // Notifications bell — available to every role
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // IT Support + Admin only
    Route::middleware('role:it_support,admin')->group(function () {
        Route::post('/tickets/{ticket}/accept', [TicketController::class, 'accept'])->name('tickets.accept');
        Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
        Route::get('/tickets/queue/poll', [TicketController::class, 'pollQueue'])->name('tickets.queue.poll');

        // Asset inventory — viewable by both roles; IT Support can add new
        // assets here too. Edit/delete stay admin-only below.
        Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
        Route::post('/admin/users/{user}/assets', [AssetController::class, 'store'])->name('admin.users.assets.store');

        // Read-only staff directory — IT Support can look someone up
        // while working a ticket, but can't edit or delete accounts here.
        Route::get('/users-directory', [UserController::class, 'directory'])->name('users.directory');
        Route::get('/users-directory/{user}', [UserController::class, 'directoryShow'])->name('users.directory.show');

        // Chat Support inbox — every staff conversation, with unread counts.
        Route::get('/support-chat-inbox', [SupportChatController::class, 'inbox'])->name('support-chat.inbox');
        Route::get('/support-chat-inbox/poll', [SupportChatController::class, 'inboxPoll'])->name('support-chat.inbox.poll');
        Route::get('/support-chat/{user}', [SupportChatController::class, 'showFor'])->name('support-chat.show.user');
        Route::post('/support-chat/{user}', [SupportChatController::class, 'sendFor'])->name('support-chat.send.user');
        Route::get('/support-chat/{user}/poll', [SupportChatController::class, 'pollFor'])->name('support-chat.poll.user');
    });

    // Admin only
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
        Route::get('/users/export/pdf', [UserController::class, 'exportPdf'])->name('users.export.pdf');
        Route::get('/users/export/excel', [UserController::class, 'exportExcel'])->name('users.export.excel');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::patch('/users/{user}/vip', [UserController::class, 'updateVip'])->name('users.vip');

        Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('/positions', [PositionController::class, 'index'])->name('positions.index');
        Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
        Route::put('/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
    });
});

require __DIR__.'/auth.php';
