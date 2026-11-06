<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Loads every role and its permissions once per request (two tiny queries) so checking
 * "can this user do X?" anywhere — middleware, menus, buttons — costs nothing extra.
 * Registered as a singleton; Role flushes it whenever a role or its permissions change.
 */
class RoleRegistry
{
    /** @var Collection<string, Role>|null */
    private ?Collection $roles = null;

    /** @var array<string, list<string>>|null role key => permission keys */
    private ?array $permissions = null;

    public function flush(): void
    {
        $this->roles = null;
        $this->permissions = null;
    }

    /** All roles: built-in first (Staff, IT Support, Administrator), then custom ones in creation order. */
    public function all(): Collection
    {
        $this->load();

        return $this->roles;
    }

    public function find(?string $key): ?Role
    {
        $this->load();

        return $key === null ? null : $this->roles->get($key);
    }

    /** @return list<string> */
    public function permissionsOf(?string $key): array
    {
        $this->load();

        return $this->permissions[$key] ?? [];
    }

    public function has(?string $roleKey, string $permission): bool
    {
        if ($roleKey === Permissions::ADMIN_KEY) {
            return ! in_array($permission, Permissions::NOT_FOR_ADMIN, true);
        }

        if (Permissions::isLocked($permission)) {
            return false;
        }

        return in_array($permission, $this->permissionsOf($roleKey), true);
    }

    /** Keys of the non-admin roles that hold a permission (e.g. everyone who works tickets). */
    public function keysWith(string $permission): array
    {
        $this->load();

        return $this->roles
            ->reject(fn (Role $r) => $r->isAdmin())
            ->filter(fn (Role $r) => in_array($permission, $this->permissions[$r->key] ?? [], true))
            ->keys()
            ->all();
    }

    private function load(): void
    {
        if ($this->roles !== null) {
            return;
        }

        try {
            $this->roles = Role::query()->orderByDesc('is_system')->orderBy('id')->get()->keyBy('key');

            $byId = DB::table('role_permissions')->get()->groupBy('role_id');
            $this->permissions = [];
            foreach ($this->roles as $role) {
                $this->permissions[$role->key] = ($byId[$role->id] ?? collect())->pluck('permission')->all();
            }
        } catch (Throwable $e) {
            // roles not migrated yet (fresh install / CI): behave as if there are none
            $this->roles = collect();
            $this->permissions = [];
        }
    }
}
