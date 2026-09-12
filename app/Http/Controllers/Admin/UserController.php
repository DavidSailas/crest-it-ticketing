<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:staff,it_support,admin']);

        $user->update(['role' => $request->role]);

        return back()->with('status', "Updated {$user->name}'s role to {$request->role}.");
    }

    public function updateVip(Request $request, User $user)
    {
        // forceFill() bypasses the model's $fillable guard, so this works reliably
        // even if `is_vip` hasn't been added to User::$fillable.
        $user->forceFill(['is_vip' => ! $user->is_vip])->save();

        $status = $user->is_vip
            ? "{$user->name} is now marked as VIP — their tickets will auto-escalate to Critical."
            : "Removed VIP status from {$user->name}.";

        return back()->with('status', $status);
    }
}
