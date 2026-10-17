<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Asset extends Model
{
    use HasFactory;

    /**
     * The two legal entities an asset can be tagged under.
     */
    public const COMPANIES = [
        'CFI' => 'CFI',
        'C5' => 'C5',
    ];

    /**
     * Branches assets can be tagged to (…-CEB-…, …-MNL-…, etc.).
     *
     * Backed by the branches table (see Admin > Branches) rather than a
     * hardcoded list, so admins can add, rename, or retire a branch without
     * a code change. Kept as a method instead of a const since it now reads
     * from the database.
     */
    public static function locations(): array
    {
        return Branch::options();
    }

    /**
     * Device types supported by the tag format (…-DT-… / …-LT-…).
     */
    public const TYPES = [
        'DT' => 'Desktop',
        'LT' => 'Laptop',
        'MN' => 'Monitor',
        'PR' => 'Printer',
        'PH' => 'Mobile Phone',
        'TP' => 'Telephone',          // desk / IP phone
        'NW' => 'Network Equipment',
    ];

    /**
     * Equipment that normally belongs to the IT Department rather than to one person
     * (routers, switches, access points…). The asset forms suggest it for these types.
     */
    public const DEPARTMENT_TYPES = ['NW'];

    /** Asset-code of the department shared IT equipment is assigned to. */
    public const IT_DEPARTMENT_CODE = 'IT';

    public const ASSIGNMENTS = [
        'person' => 'A person',
        'department' => 'A department',
        'stock' => 'Not assigned',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'in_repair' => 'In Repair',
        'retired' => 'Retired',
    ];

    protected $fillable = [
        'user_id',
        'department_id',
        'department_assigned',
        'company',
        'location',
        'type',
        'sequence',
        'asset_tag',
        'device_name',
        'serial_number',
        'status',
        'assigned_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'department_assigned' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /** The IT Department's id, if one exists — used to pre-select it for network equipment. */
    public static function itDepartmentId(): ?int
    {
        // The standard "IT" code first; otherwise whichever IT department the admin already made.
        return Department::where('code', self::IT_DEPARTMENT_CODE)->value('id')
            ?? Department::whereIn('name', ['IT Department', 'IT', 'IT Support', 'Information Technology'])->orderBy('id')->value('id');
    }

    /**
     * Who has it: a person, a department (shared equipment) or nobody (in stock).
     */
    public function getAssignmentAttribute(): string
    {
        if ($this->user_id) {
            return 'person';
        }

        return $this->department_assigned ? 'department' : 'stock';
    }

    /** Text for "Assigned to" columns: the person's name, "IT Department", or "Unassigned". */
    public function getAssigneeLabelAttribute(): string
    {
        return match ($this->assignment) {
            'person' => $this->user?->name ?? 'Unknown user',
            'department' => ($this->department?->name ?? 'a department').' (shared)',
            default => 'Unassigned',
        };
    }

    /**
     * Human-readable "how long has this person had it" string, e.g.
     * "8 months" or "3 days". Used in the inventory table and detail view
     * so IT/admin can see asset tenure at a glance without doing date math.
     */
    public function getAssignedDurationAttribute(): ?string
    {
        if (! $this->assigned_date) {
            return null;
        }

        return $this->assigned_date->diffForHumans(now(), true);
    }

    /**
     * Rebuild the tag string from its parts — used both when issuing a new
     * asset (see nextTag) and when an admin manually corrects the sequence
     * number on an existing one.
     */
    public static function formatTag(string $company, string $location, string $departmentCode, string $type, int $sequence): string
    {
        return sprintf('%s-%s-%s-%s-%03d', $company, $location, $departmentCode, $type, $sequence);
    }

    /**
     * Work out the next asset tag for a company + location + department +
     * device type, e.g. CFI-CEB-IT-LT-001. The running number is scoped to
     * that exact combination, so Cebu IT laptops and Manila IT laptops each
     * keep their own sequence starting at 001.
     *
     * Locks the matching rows for the life of the transaction so two admins
     * assigning assets at the same moment can't be handed the same number.
     *
     * @return array{0: int, 1: string} [sequence, tag]
     */
    public static function nextTag(string $company, string $location, int $departmentId, string $departmentCode, string $type): array
    {
        return DB::transaction(function () use ($company, $location, $departmentId, $departmentCode, $type) {
            $sequence = (int) static::where('company', $company)
                ->where('location', $location)
                ->where('department_id', $departmentId)
                ->where('type', $type)
                ->lockForUpdate()
                ->max('sequence') + 1;

            $tag = static::formatTag($company, $location, $departmentCode, $type, $sequence);

            return [$sequence, $tag];
        });
    }
}
