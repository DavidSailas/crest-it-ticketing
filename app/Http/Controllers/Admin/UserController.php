<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Staff / IT Support / VIP are shown as separate tabs rather than one
     * long mixed list, since an admin usually only cares about one group
     * at a time.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'staff');

        $query = User::query();

        if ($tab === 'it_support') {
            $query->where('role', 'it_support');
        } elseif ($tab === 'vip') {
            $query->where('is_vip', true);
        } else {
            $tab = 'staff';
            $query->where('role', 'staff');
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        $counts = [
            'staff' => User::where('role', 'staff')->count(),
            'it_support' => User::where('role', 'it_support')->count(),
            'vip' => User::where('is_vip', true)->count(),
        ];

        return view('admin.users.index', compact('users', 'tab', 'counts'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:staff,it_support,admin'],
            'is_vip' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'That email is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_vip' => $request->boolean('is_vip'),
        ])->save();

        return redirect()->route('admin.users.index', ['tab' => $validated['role'] === 'it_support' ? 'it_support' : 'staff'])
            ->with('status', "Created account for {$validated['name']}.");
    }

    public function edit(User $user)
    {
        $assets = Asset::where('user_id', $user->id)->latest()->get();

        // Only departments with a code can be picked, since the code is
        // what makes up the middle segment of the asset tag.
        $assetDepartments = Department::whereNotNull('code')->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'assets', 'assetDepartments'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:staff,it_support,admin'],
            'is_vip' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'That email is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $fill = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_vip' => $request->boolean('is_vip'),
        ];

        if (!empty($validated['password'])) {
            $fill['password'] = Hash::make($validated['password']);
        }

        $user->forceFill($fill)->save();

        return redirect()->route('admin.users.index', ['tab' => $user->role === 'it_support' ? 'it_support' : 'staff'])
            ->with('status', "Updated {$user->name}.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', "You can't delete your own account while logged in.");
        }

        $name = $user->name;
        $user->delete();

        return back()->with('status', "Deleted {$name}.");
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:staff,it_support,admin']);

        $user->update(['role' => $request->role]);

        return back()->with('status', "Updated {$user->name}'s role to {$request->role}.");
    }

    public function updateVip(Request $request, User $user)
    {
        $user->forceFill(['is_vip' => ! $user->is_vip])->save();

        $status = $user->is_vip
            ? "{$user->name} is now marked as VIP — their tickets will auto-escalate to Critical."
            : "Removed VIP status from {$user->name}.";

        return back()->with('status', $status);
    }

    /**
     * Read-only staff directory for IT Support. Same underlying data as
     * Manage Users, but with no edit, delete, role, or VIP controls — IT
     * Support can look someone up while working a ticket, nothing more.
     */
    public function directory(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        // Only staff accounts show here — IT Support and Admin accounts
        // are excluded from this directory.
        $query = User::query()->where('role', 'staff');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        // Asset counts per user, fetched separately so this read-only view
        // doesn't depend on a relationship being defined on the User model.
        $assetCounts = Asset::whereIn('user_id', $users->pluck('id'))
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        return view('admin.users.directory', compact('users', 'search', 'assetCounts'));
    }

    /**
     * Read-only detail view for IT Support: everything they need while
     * working a ticket for this person — account info, every asset issued
     * to them (with tags), and their recent ticket history.
     */
    public function directoryShow(User $user)
    {
        abort_unless($user->role === 'staff', 404);

        $assets = Asset::with('department')
            ->where('user_id', $user->id)
            ->orderBy('type')
            ->orderBy('sequence')
            ->get();

        $tickets = Ticket::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $ticketCounts = [
            'total' => Ticket::where('user_id', $user->id)->count(),
            'open' => Ticket::where('user_id', $user->id)->where('status', 'open')->count(),
            'in_progress' => Ticket::where('user_id', $user->id)->where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('user_id', $user->id)->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('admin.users.directory-show', compact('user', 'assets', 'tickets', 'ticketCounts'));
    }

    /**
     * Download every user as a CSV file.
     */
    public function export(): StreamedResponse
    {
        $filename = 'users-'.now()->format('Y-m-d').'.csv';

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Role', 'VIP']);

            User::orderBy('name')->chunk(200, function ($users) use ($handle) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->name,
                        $user->email,
                        $user->role,
                        $user->is_vip ? 'Yes' : 'No',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Bulk-create users from an uploaded CSV. Expected columns (with header
     * row): Name, Email, Role, VIP (optional). New accounts get a random
     * temporary password — share it with each person, or have them use
     * "Forgot password" to set their own.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ], [
            'file.required' => 'Please choose a CSV file to import.',
            'file.mimes' => 'The file must be a CSV.',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_combine($header, $row);

            $name = trim($row['name'] ?? '');
            $email = trim($row['email'] ?? '');
            $role = strtolower(trim($row['role'] ?? 'staff'));
            $isVip = in_array(strtolower(trim($row['vip'] ?? 'no')), ['yes', 'true', '1']);

            if ($name === '' || $email === '' || User::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            if (!in_array($role, ['staff', 'it_support', 'admin'])) {
                $role = 'staff';
            }

            $user = new User();
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(str()->random(12)),
                'role' => $role,
                'is_vip' => $isVip,
            ])->save();

            $created++;
        }

        fclose($handle);

        return back()->with('status', "Imported {$created} user(s)." . ($skipped > 0 ? " Skipped {$skipped} (missing data or duplicate email)." : ''));
    }
}
