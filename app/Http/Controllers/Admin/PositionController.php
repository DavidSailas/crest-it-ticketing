<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::withCount('users')->orderBy('name')->get();

        return view('admin.positions.index', compact('positions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
        ], [
            'name.unique' => 'That position already exists.',
        ]);

        Position::create($validated);

        return back()->with('status', "Added position \"{$validated['name']}\".");
    }

    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name,'.$position->id],
        ], [
            'name.unique' => 'That position already exists.',
        ]);

        $position->update($validated);

        return back()->with('status', "Updated position \"{$position->name}\".");
    }

    public function destroy(Position $position)
    {
        if ($position->users()->exists()) {
            return back()->with('error', "\"{$position->name}\" is still assigned to {$position->users()->count()} user(s) — reassign them first.");
        }

        $name = $position->name;
        $position->delete();

        return back()->with('status', "Removed position \"{$name}\".");
    }
}
