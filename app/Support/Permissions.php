<?php

namespace App\Support;

/**
 * The catalog of things a role can be allowed to do (Admin → Roles & Permissions).
 *
 * Every key here is enforced somewhere real — a route guard (`can:` middleware),
 * a menu entry, or a button — and each is checked with `$user->hasPermission($key)`,
 * `@can($key)` in Blade, or `->middleware('can:'.$key)` on routes.
 *
 * Two rules keep the system safe:
 *   - The built-in Administrator role always has everything and cannot be edited, so
 *     nobody can lock themselves out.
 *   - LOCKED permissions (user accounts, roles) belong to Administrators only and can
 *     never be granted — otherwise a role could promote itself to admin.
 */
class Permissions
{
    public const ADMIN_KEY = 'admin';

    /** group key => [label, description, items: key => [label, description]] */
    public const GROUPS = [
        'tickets' => [
            'label' => 'Tickets',
            'description' => 'Support requests raised by staff.',
            'items' => [
                'tickets.view_all' => ['See all tickets', 'Read every ticket in the company, not only their own. Read-only: they cannot change or reply to other people\'s tickets.'],
                'tickets.work' => ['Work on tickets', 'Accept tickets from the queue, update status, edit, assign, reply to requesters and use the Support Inbox. This gives the full IT Support workspace.'],
                'tickets.export' => ['Export tickets', 'Download the ticket list as PDF or Excel.'],
            ],
        ],
        'assets' => [
            'label' => 'Assets',
            'description' => 'Computers, devices and equipment inventory.',
            'items' => [
                'assets.view' => ['View the asset list', 'See the inventory (read-only).'],
                'assets.manage' => ['Add and edit assets', 'Register new assets, edit details and assign them to people.'],
                'assets.delete' => ['Delete assets', 'Permanently remove an asset from the inventory.'],
                'assets.export' => ['Export assets', 'Download the asset list as PDF or Excel.'],
            ],
        ],
        'supplies' => [
            'label' => 'IT Supplies',
            'description' => 'Consumables and stock such as cables, toner and peripherals.',
            'items' => [
                'supplies.view' => ['View supplies', 'See items, stock levels and requests.'],
                'supplies.request' => ['Request supplies', 'Ask for an item. Each request becomes a ticket for IT Support.'],
                'supplies.manage' => ['Add, edit and restock supplies', 'Create items, edit them and adjust stock.'],
                'supplies.delete' => ['Delete supplies', 'Permanently remove a supply item.'],
            ],
        ],
        'people' => [
            'label' => 'People & reports',
            'description' => 'Looking people up and seeing how the team is doing.',
            'items' => [
                'users.directory' => ['Staff directory', 'Look up a coworker\'s details and the equipment assigned to them (read-only).'],
                'reports.view' => ['View reports', 'Open the management report with its PDF and Excel downloads.'],
            ],
        ],
        'content' => [
            'label' => 'Announcements & policy',
            'description' => 'What the whole company reads.',
            'items' => [
                'announcements.manage' => ['Manage announcements', 'Post, schedule, edit and remove announcements.'],
                'policy.view' => ['Read the IT policy', 'Open and download the company IT policy.'],
                'policy.manage' => ['Manage the IT policy', 'Upload the company PDF or edit the default policy text.'],
            ],
        ],
        'organization' => [
            'label' => 'Organization & settings',
            'description' => 'Company-wide setup.',
            'items' => [
                'organization.manage' => ['Manage branches, departments & positions', 'Add, rename and remove the lists used across the system.'],
                'branding.manage' => ['Appearance & branding', 'Change colours, logos and the sign-in page.'],
            ],
        ],
    ];

    /**
     * Administrator-only. Listed on the role screen so it is clear they exist, but never grantable.
     * key => [label, description]
     */
    public const LOCKED = [
        'users.manage' => ['Manage user accounts', 'Create, edit, suspend and delete accounts, and choose each person\'s role.'],
        'roles.manage' => ['Manage roles & permissions', 'Create roles and decide what each role can do.'],
    ];

    /** Administrators have everything except this: it only makes sense for people who raise tickets. */
    public const NOT_FOR_ADMIN = ['supplies.request'];

    /** What the built-in roles start with (and what "Reset to defaults" restores). Administrator needs no list. */
    public const DEFAULTS = [
        'staff' => [
            'assets.view', 'supplies.view', 'supplies.request', 'users.directory', 'reports.view', 'policy.view',
        ],
        'it_support' => [
            'tickets.work', 'assets.view', 'assets.manage', 'supplies.view', 'supplies.manage',
            'users.directory', 'reports.view', 'policy.view',
        ],
    ];

    /**
     * A permission that only makes sense together with another: you cannot add assets you cannot see.
     * Ticking the first ticks the second; unticking the second unticks the first.
     */
    public const IMPLIES = [
        'assets.manage' => ['assets.view'],
        'assets.delete' => ['assets.view'],
        'assets.export' => ['assets.view'],
        'supplies.request' => ['supplies.view'],
        'supplies.manage' => ['supplies.view'],
        'supplies.delete' => ['supplies.view'],
        'policy.manage' => ['policy.view'],
    ];

    /** Add whatever the chosen permissions depend on. */
    public static function withImplied(array $keys): array
    {
        $keys = self::clean($keys);
        foreach ($keys as $key) {
            foreach (self::IMPLIES[$key] ?? [] as $needed) {
                $keys[] = $needed;
            }
        }

        return self::clean($keys);
    }

    /** Every grantable permission key. */
    public static function grantable(): array
    {
        $keys = [];
        foreach (self::GROUPS as $group) {
            $keys = array_merge($keys, array_keys($group['items']));
        }

        return $keys;
    }

    /** Every permission key, grantable or locked (used to register the gates). */
    public static function all(): array
    {
        return array_merge(self::grantable(), array_keys(self::LOCKED));
    }

    public static function isLocked(string $key): bool
    {
        return array_key_exists($key, self::LOCKED);
    }

    public static function exists(string $key): bool
    {
        return in_array($key, self::grantable(), true);
    }

    /** Human label for a permission key. */
    public static function label(string $key): string
    {
        foreach (self::GROUPS as $group) {
            if (isset($group['items'][$key])) {
                return $group['items'][$key][0];
            }
        }

        return self::LOCKED[$key][0] ?? $key;
    }

    /** Keep only real, grantable keys (drops anything tampered with or removed from the catalog). */
    public static function clean(array $keys): array
    {
        return array_values(array_intersect(self::grantable(), array_unique(array_map('strval', $keys))));
    }

    public static function defaultsFor(string $roleKey): array
    {
        return self::DEFAULTS[$roleKey] ?? [];
    }
}
