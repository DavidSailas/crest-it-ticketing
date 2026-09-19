<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'code'];

    protected static function booted()
    {
        static::saving(function (Branch $branch) {
            if (filled($branch->code)) {
                $branch->code = strtoupper(trim($branch->code));
            }
        });
    }

    /**
     * users.location and assets.location store the branch's short code
     * (e.g. "CEB") rather than a foreign key, so both relationships are
     * matched on code — same pattern Department uses for tickets.department.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'location', 'code');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'location', 'code');
    }

    /**
     * [code => name] map of every branch, e.g. ['CEB' => 'Cebu', ...].
     * The single source of truth for every dropdown, validation rule, and
     * display lookup that used to read the hardcoded Asset::LOCATIONS
     * constant — now admins can add/rename/remove branches from
     * Admin > Branches without touching code.
     */
    public static function options(): array
    {
        return static::orderBy('name')->pluck('name', 'code')->all();
    }

    public static function nameForCode(?string $code): ?string
    {
        if (blank($code)) {
            return null;
        }

        return static::options()[$code] ?? $code;
    }
}
