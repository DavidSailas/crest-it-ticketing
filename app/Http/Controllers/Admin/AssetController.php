<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * Inventory list — every asset across all users. Admins get full
     * create/edit/delete controls on this page; IT Support sees the same
     * table read-only (enforced in the view, not here, since both roles
     * are allowed to view this route).
     */
    public function index(Request $request)
    {
        $assets = Asset::with(['user', 'department'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('asset_tag', 'like', "%{$search}%")
                        ->orWhere('device_name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('department_id'), function ($query) use ($request) {
                $query->where('department_id', $request->integer('department_id'));
            })
            ->orderBy('asset_tag')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();
        // For the "Assign To" picker in each row's edit modal.
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.assets.index', compact('assets', 'departments', 'users'));
    }

    /**
     * Full-page "Assign New Asset" form — mirrors Admin > Add User rather
     * than a modal, so it gets its own URL, works better on mobile, and
     * doesn't need to carry the whole inventory table's Alpine state.
     */
    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $departments = Department::orderBy('name')->get();

        return view('admin.assets.create', compact('users', 'departments'));
    }

    /**
     * Assign a new asset to a user, or leave it unassigned in inventory.
     * The asset tag (e.g. CFI-CEB-IT-LT-001) is normally generated
     * automatically from the company, location, department code, device
     * type, and the next number in that sequence — but the tag number can
     * also be typed in by hand (e.g. to match an existing physical label,
     * or to backfill assets logged elsewhere). When typed in, it's checked
     * against the same company/location/department/type group so it still
     * can't collide with another asset.
     *
     * $user is route-bound when this comes from a specific user's "Assign
     * Asset" form (admin.users.assets.store); it's null on the standalone
     * "New Asset" page, which posts an optional user_id in the body so the
     * asset can be left unassigned.
     */
    public function store(Request $request, ?User $user = null)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'company' => ['required', 'in:'.implode(',', array_keys(Asset::COMPANIES))],
            'location' => ['required', 'in:'.implode(',', array_keys(Asset::locations()))],
            'department_id' => ['required', 'exists:departments,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(Asset::TYPES))],
            'sequence' => ['nullable', 'integer', 'min:1', 'max:999'],
            'device_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'assigned_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'company.required' => 'Choose which company this asset belongs to.',
            'location.required' => 'Choose a location.',
            'department_id.required' => 'Choose a department.',
            'type.required' => 'Choose a device type.',
            'sequence.integer' => 'Tag number must be a number.',
            'sequence.min' => 'Tag number must be at least 1.',
            'sequence.max' => 'Tag number can\'t exceed 999 (three digits).',
            'assigned_date.before_or_equal' => 'Assigned date can\'t be in the future.',
        ]);

        // Route-bound user (from a user's own page) wins; otherwise fall
        // back to whatever was picked (or left blank) on the New Asset form.
        $ownerId = $user?->id ?? $validated['user_id'] ?? null;

        $department = Department::findOrFail($validated['department_id']);

        if (blank($department->code)) {
            return back()->with('error', "\"{$department->name}\" doesn't have an asset code yet. Set one on the Departments page first.")->withInput();
        }

        if (filled($validated['sequence'] ?? null)) {
            // A number was typed in by hand — use it, but make sure it
            // doesn't collide with another asset in the same group.
            $sequence = (int) $validated['sequence'];
            $tag = Asset::formatTag($validated['company'], $validated['location'], $department->code, $validated['type'], $sequence);

            if (Asset::where('asset_tag', $tag)->exists()) {
                return back()->with('error', "{$tag} is already in use — choose a different number.")->withInput();
            }
        } else {
            [$sequence, $tag] = Asset::nextTag($validated['company'], $validated['location'], $department->id, $department->code, $validated['type']);
        }

        Asset::create([
            'user_id' => $ownerId,
            'department_id' => $department->id,
            'company' => $validated['company'],
            'location' => $validated['location'],
            'type' => $validated['type'],
            'sequence' => $sequence,
            'asset_tag' => $tag,
            'device_name' => $validated['device_name'],
            'serial_number' => $validated['serial_number'] ?? null,
            // Defaults to today if left blank, since most assets are logged
            // the same day they're physically handed over.
            'assigned_date' => $validated['assigned_date'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $ownerName = $ownerId ? User::find($ownerId)?->name : null;
        $status = $ownerName
            ? "Asset {$tag} assigned to {$ownerName}."
            : "Asset {$tag} added to inventory as unassigned.";

        return redirect()->route('assets.index')->with('status', $status);
    }

    /**
     * Edit an asset's details. The sequence number (the 001/002 at the end
     * of the tag) can be corrected here too — useful if two assets were
     * numbered out of order, or a mistake needs fixing. Changing it
     * regenerates the tag and checks it doesn't collide with another asset
     * in the same company/location/department/type group.
     */
    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'device_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', array_keys(Asset::STATUSES))],
            'sequence' => ['required', 'integer', 'min:1', 'max:999'],
            'assigned_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'sequence.required' => 'Enter a sequence number.',
            'sequence.min' => 'Sequence number must be at least 1.',
            'sequence.max' => 'Sequence number can\'t exceed 999 (three digits).',
            'assigned_date.before_or_equal' => 'Assigned date can\'t be in the future.',
        ]);

        $department = $asset->department;
        $newTag = Asset::formatTag($asset->company, $asset->location, $department->code, $asset->type, (int) $validated['sequence']);

        $collision = Asset::where('asset_tag', $newTag)->where('id', '!=', $asset->id)->exists();
        if ($collision) {
            return back()->with('error', "{$newTag} is already used by another asset — choose a different number.")->withInput();
        }

        $asset->update([
            'user_id' => $validated['user_id'] ?? null,
            'device_name' => $validated['device_name'],
            'serial_number' => $validated['serial_number'] ?? null,
            'status' => $validated['status'],
            'assigned_date' => $validated['assigned_date'] ?? $asset->assigned_date,
            'notes' => $validated['notes'] ?? null,
            'sequence' => $validated['sequence'],
            'asset_tag' => $newTag,
        ]);

        return back()->with('status', "Updated asset {$newTag}.");
    }

    public function destroy(Asset $asset)
    {
        $tag = $asset->asset_tag;
        $asset->delete();

        return back()->with('status', "Removed asset {$tag}.");
    }
}
