<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('tickets')->orderBy('name')->get();
        return view('admin.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:departments,name',
            'code' => 'required|string|max:10|alpha_num|unique:departments,code',
        ], [
            'name.unique' => 'That department already exists.',
            'code.unique' => 'That asset code is already used by another department.',
            'code.alpha_num' => 'Use letters and numbers only (e.g. ACC).',
        ]);

        Department::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return back()->with('status', "Department \"{$request->name}\" added.");
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:departments,name,'.$department->id,
            'code' => 'required|string|max:10|alpha_num|unique:departments,code,'.$department->id,
        ], [
            'name.unique' => 'That department already exists.',
            'code.unique' => 'That asset code is already used by another department.',
            'code.alpha_num' => 'Use letters and numbers only (e.g. ACC).',
        ]);

        $department->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return back()->with('status', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        if ($department->tickets()->exists()) {
            return back()->with('error', "Can't delete \"{$department->name}\" — it's used by existing tickets.");
        }

        $department->delete();

        return back()->with('status', 'Department deleted.');
    }
}
