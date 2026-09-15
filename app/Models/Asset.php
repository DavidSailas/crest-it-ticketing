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
     */
    public const LOCATIONS = [
        'CEB' => 'Cebu',
        'MNL' => 'Manila',
        'CDO' => 'Cagayan de Oro',
        'DVO' => 'Davao',
    ];

    /**
     * Device types supported by the tag format (…-DT-… / …-LT-…).
     */
    public const TYPES = [
        'DT' => 'Desktop',
        'LT' => 'Laptop',
        'MN' => 'Monitor',
        'PR' => 'Printer',
        'PH' => 'Phone',
        'NW' => 'Network Equipment',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'in_repair' => 'In Repair',
        'retired' => 'Retired',
    ];

    protected $fillable = [
        'user_id',
        'department_id',
        'company',
        'location',
        'type',
        'sequence',
        'asset_tag',
        'device_name',
        'serial_number',
        'status',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
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
