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
     * Assign a new asset to a user. The asset tag (e.g. CFI-CEB-IT-LT-001)
     * is generated automatically from the company, location, department
     * code, device type, and the next number in that sequence — it isn't
     * typed in by hand, so it can't be duplicated or miskeyed.
     */
    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'company' => ['required', 'in:'.implode(',', array_keys(Asset::COMPANIES))],
            'location' => ['required', 'in:'.implode(',', array_keys(Asset::LOCATIONS))],
            'department_id' => ['required', 'exists:departments,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(Asset::TYPES))],
            'device_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'company.required' => 'Choose which company this asset belongs to.',
            'location.required' => 'Choose a location.',
            'department_id.required' => 'Choose a department.',
            'type.required' => 'Choose a device type.',
        ]);

        $department = Department::findOrFail($validated['department_id']);

        if (blank($department->code)) {
            return back()->with('error', "\"{$department->name}\" doesn't have an asset code yet. Set one on the Departments page first.")->withInput();
        }

        [$sequence, $tag] = Asset::nextTag($validated['company'], $validated['location'], $department->id, $department->code, $validated['type']);

        Asset::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'company' => $validated['company'],
            'location' => $validated['location'],
            'type' => $validated['type'],
            'sequence' => $sequence,
            'asset_tag' => $tag,
            'device_name' => $validated['device_name'],
            'serial_number' => $validated['serial_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', "Asset {$tag} assigned to {$user->name}.");
    }

    /**
     * Edit an asset's descriptive details. The tag itself never changes
     * once issued — that's the whole point of an asset tag.
     */
    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', array_keys(Asset::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $asset->update($validated);

        return back()->with('status', "Updated asset {$asset->asset_tag}.");
    }

    public function destroy(Asset $asset)
    {
        $tag = $asset->asset_tag;
        $asset->delete();

        return back()->with('status', "Removed asset {$tag}.");
    }
}
