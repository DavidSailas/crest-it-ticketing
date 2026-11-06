<?php

use App\Http\Controllers\Admin\SupplyController;
use App\Http\Controllers\Admin\SupplyRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IT Supplies
|--------------------------------------------------------------------------
| Staff       — view, and request items (a department head approves it, then it becomes a ticket)
| Dept. heads — staff accounts flagged as head; approve or decline every request
| IT Support  — view, add, edit, adjust stock
| Admin       — everything, including delete
|
| Included from routes/web.php with: require __DIR__.'/supplies.php';
*/
Route::middleware(['auth', 'account.active', 'track.seen'])->group(function () {

    // Add / edit / adjust stock — IT Support + Admin.
    // Declared before the {supply} routes so "create" isn't read as an id.
    Route::middleware('role:it_support,admin')->group(function () {
        Route::get('/supplies/create', [SupplyController::class, 'create'])->name('supplies.create');
        Route::post('/supplies', [SupplyController::class, 'store'])->name('supplies.store');
        Route::get('/supplies/{supply}/edit', [SupplyController::class, 'edit'])->name('supplies.edit');
        Route::put('/supplies/{supply}', [SupplyController::class, 'update'])->name('supplies.update');
        Route::post('/supplies/{supply}/adjust', [SupplyController::class, 'adjust'])->name('supplies.adjust');
    });

    // Staff ask for an item. It becomes a ticket for IT Support, and the stock is
    // taken out automatically when that ticket is Resolved.
    Route::middleware('role:staff')->post('/supplies/{supply}/request', [SupplyRequestController::class, 'store'])
        ->name('supplies.request');

    // Department heads approve or decline requests; the requester can withdraw one that is still waiting.
    // (Heads are staff accounts; admins can approve too. The controller double-checks who may approve.)
    Route::middleware('role:staff,admin')->group(function () {
        Route::post('/supplies/requests/{supplyRequest}/approve', [SupplyRequestController::class, 'approve'])->name('supplies.requests.approve');
        Route::post('/supplies/requests/{supplyRequest}/decline', [SupplyRequestController::class, 'decline'])->name('supplies.requests.decline');
        Route::post('/supplies/requests/{supplyRequest}/cancel', [SupplyRequestController::class, 'cancel'])->name('supplies.requests.cancel');
    });

    // View — everyone who can sign in.
    Route::middleware('role:staff,it_support,admin')->group(function () {
        Route::get('/supplies', [SupplyController::class, 'index'])->name('supplies.index');
        // Before {supply}, so "requests" isn't read as an item id.
        Route::get('/supplies/requests', [SupplyRequestController::class, 'index'])->name('supplies.requests');
        Route::get('/supplies/{supply}', [SupplyController::class, 'show'])->name('supplies.show');
    });

    // Delete — Admin only.
    Route::middleware('role:admin')->delete('/admin/supplies/{supply}', [SupplyController::class, 'destroy'])
        ->name('admin.supplies.destroy');
});
