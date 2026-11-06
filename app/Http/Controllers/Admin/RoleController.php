<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Support\Permissions;
use App\Support\RoleRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin → Roles & Permissions: create roles (Head, Supervisor, …) and tick what each may do.
 * The built-in Administrator is fixed (full access); Staff and IT Support can be adjusted.
 */
class RoleController extends Controller
{
    public function index()
    {
        $roles = app(RoleRegistry::class)->all();

        $people = $roles->mapWithKeys(fn (Role $r) => [$r->key => $r->userCount()]);

        return view('admin.roles.index', [
            'roles' => $roles,
            'people' => $people,
            'groups' => Permissions::GROUPS,
            'locked' => Permissions::LOCKED,
            'notForAdmin' => Permissions::NOT_FOR_ADMIN,
            'totalPermissions' => count(Permissions::grantable()),
        ]);
    }

    public function create(Request $request)
    {
        $registry = app(RoleRegistry::class);
        $from = $registry->find((string) $request->query('from')) ?? $registry->find('staff');

        return view('admin.roles.form', $this->formData(new Role(['color' => 'sky']), $from?->permissionKeys() ?? []));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $role = Role::create([
            'key' => Role::makeKey($data['name']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'],
            'is_system' => false,
        ]);
        $role->syncPermissions($data['permissions'] ?? []);

        ActivityLog::record('role_created', "Created the role \"{$role->name}\"", ['role' => $role->key]);

        return redirect()->route('admin.roles.index')
            ->with('status', "The role \"{$role->name}\" was created. Give it to people from Manage Users → Edit.");
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', $this->formData($role, $role->permissionKeys()));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->isAdmin()) {
            return redirect()->route('admin.roles.index')->with('error', 'The Administrator role always has full access and cannot be changed.');
        }

        $data = $this->validated($request, $role);

        $role->update([
            // built-in roles keep their name (it is used in many labels); everything else can be renamed
            'name' => $role->is_system ? $role->name : $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'],
        ]);
        $role->syncPermissions($data['permissions'] ?? []);

        ActivityLog::record('role_updated', "Changed what the role \"{$role->name}\" can do", ['role' => $role->key]);

        return redirect()->route('admin.roles.index')->with('status', "Saved \"{$role->name}\". The change applies straight away.");
    }

    /** Put a built-in role back to how it shipped. */
    public function reset(Role $role)
    {
        if (! $role->is_system || $role->isAdmin()) {
            return redirect()->route('admin.roles.index')->with('error', 'Only Staff and IT Support have defaults to go back to.');
        }

        $role->syncPermissions(Permissions::defaultsFor($role->key));

        ActivityLog::record('role_reset', "Reset the role \"{$role->name}\" to its defaults", ['role' => $role->key]);

        return redirect()->route('admin.roles.edit', $role)->with('status', "\"{$role->name}\" is back to its default permissions.");
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', "\"{$role->name}\" is built in and cannot be deleted.");
        }

        if (($n = $role->userCount()) > 0) {
            return back()->with('error', "\"{$role->name}\" is still given to {$n} ".($n === 1 ? 'person' : 'people').'. Move them to another role first (Manage Users → Edit), then delete it.');
        }

        $name = $role->name;
        $role->delete();

        ActivityLog::record('role_deleted', "Deleted the role \"{$name}\"");

        return redirect()->route('admin.roles.index')->with('status', "The role \"{$name}\" was deleted.");
    }

    /* ------------------------------------------------------------------ helpers */

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                $role?->is_system ? 'nullable' : 'required', 'string', 'max:60',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['required', Rule::in(array_keys(Role::COLORS))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ], [
            'name.required' => 'Give the role a name, for example "Head".',
            'name.unique' => 'A role with that name already exists.',
            'color.in' => 'Pick one of the colours shown.',
        ]);
    }

    private function formData(Role $role, array $granted): array
    {
        $registry = app(RoleRegistry::class);

        // every role's permissions, so "Start from…" can copy them without another request
        $presets = $registry->all()->mapWithKeys(fn (Role $r) => [$r->key => $r->permissionKeys()]);

        return [
            'role' => $role,
            'editing' => $role->exists,
            'readonly' => $role->exists && $role->isAdmin(),
            'granted' => Permissions::clean((array) old('permissions', $granted)),
            'groups' => Permissions::GROUPS,
            'locked' => Permissions::LOCKED,
            'implies' => Permissions::IMPLIES,
            'presets' => $presets,
            'roles' => $registry->all(),
            'colors' => Role::COLORS,
            'people' => $role->exists ? $role->userCount() : 0,
            'defaults' => $role->exists && $role->is_system && ! $role->isAdmin() ? Permissions::defaultsFor($role->key) : null,
        ];
    }
}
