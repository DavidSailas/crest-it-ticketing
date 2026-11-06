<?php

namespace App\Models;

use App\Support\Permissions;
use App\Support\RoleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A role (Staff, IT Support, Administrator, or one an admin created, e.g. "Head").
 * What it may do is the set of permission keys in role_permissions
 * (see App\Support\Permissions). users.role stores this model's `key`.
 */
class Role extends Model
{
    /** Names for the colour swatches: [text, background] — used for badges. */
    public const COLORS = [
        'slate' => ['#475569', '#f1f5f9'],
        'indigo' => ['#4338ca', '#eef2ff'],
        'green' => ['#166534', '#dcfce7'],
        'amber' => ['#b45309', '#fef3c7'],
        'rose' => ['#be123c', '#ffe4e6'],
        'sky' => ['#0369a1', '#e0f2fe'],
        'violet' => ['#6d28d9', '#ede9fe'],
        'teal' => ['#0f766e', '#ccfbf1'],
    ];

    /** Keys a new role may not take (announcements use "all" for everyone). */
    private const RESERVED_KEYS = ['all'];

    protected $fillable = ['key', 'name', 'description', 'color', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    protected static function booted(): void
    {
        $flush = fn () => app(RoleRegistry::class)->flush();
        static::saved($flush);
        static::deleted($flush);
    }

    public function isAdmin(): bool
    {
        return $this->key === Permissions::ADMIN_KEY;
    }

    /** Permission keys this role has. The Administrator has every grantable one. */
    public function permissionKeys(): array
    {
        return $this->isAdmin()
            ? array_values(array_diff(Permissions::grantable(), Permissions::NOT_FOR_ADMIN))
            : app(RoleRegistry::class)->permissionsOf($this->key);
    }

    public function can(string $permission): bool
    {
        return app(RoleRegistry::class)->has($this->key, $permission);
    }

    /** Replace this role's permissions (Administrator is fixed and ignored). */
    public function syncPermissions(array $keys): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $keys = Permissions::withImplied($keys);

        DB::transaction(function () use ($keys) {
            DB::table('role_permissions')->where('role_id', $this->id)->delete();
            if ($keys) {
                DB::table('role_permissions')->insert(array_map(fn ($p) => ['role_id' => $this->id, 'permission' => $p], $keys));
            }
        });

        app(RoleRegistry::class)->flush();
    }

    /** People currently holding this role. */
    public function userCount(): int
    {
        return User::where('role', $this->key)->count();
    }

    public function colors(): array
    {
        return self::COLORS[$this->color] ?? self::COLORS['slate'];
    }


    /** A short, unique key made from the name: "Department Head" → "department_head". */
    public static function makeKey(string $name): string
    {
        $base = Str::of(Str::ascii($name))->slug('_')->limit(16, '')->toString();
        $base = $base !== '' ? $base : 'role';

        $key = $base;
        $i = 2;
        while (in_array($key, self::RESERVED_KEYS, true) || self::where('key', $key)->exists()) {
            $key = $base.'_'.$i++;
        }

        return $key;
    }
}
