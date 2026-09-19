<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['users', 'assets'])->orderBy('name')->get();

        return view('admin.branches.index', compact('branches'));
    }

    /**
     * The branch code is stored on users.location, a VARCHAR(3) column, so
     * it's capped at 3 characters here to avoid silent truncation.
     */
    private const CODE_RULES = 'required|string|max:3|alpha_num|unique:branches,code';

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:branches,name',
            'code' => self::CODE_RULES,
        ], [
            'name.unique' => 'That branch already exists.',
            'code.unique' => 'That branch code is already used by another branch.',
            'code.alpha_num' => 'Use letters and numbers only (e.g. CEB).',
            'code.max' => 'Branch codes can be at most 3 characters (e.g. CEB).',
        ]);

        Branch::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return back()->with('status', "Branch \"{$request->name}\" added.");
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:branches,name,'.$branch->id,
            'code' => 'required|string|max:3|alpha_num|unique:branches,code,'.$branch->id,
        ], [
            'name.unique' => 'That branch already exists.',
            'code.unique' => 'That branch code is already used by another branch.',
            'code.alpha_num' => 'Use letters and numbers only (e.g. CEB).',
            'code.max' => 'Branch codes can be at most 3 characters (e.g. CEB).',
        ]);

        $branch->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return back()->with('status', 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->exists() || $branch->assets()->exists()) {
            return back()->with('error', "Can't delete \"{$branch->name}\" — it's still assigned to users or assets.");
        }

        $branch->delete();

        return back()->with('status', 'Branch deleted.');
    }
}
