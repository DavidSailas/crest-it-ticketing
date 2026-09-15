<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'code'];

    protected static function booted()
    {
        static::saving(function (Department $department) {
            if (filled($department->code)) {
                $department->code = strtoupper(trim($department->code));
            }
        });
    }

    /**
     * tickets.department stores the department name as a string rather than
     * a foreign key, so the relationship is matched on name.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'department', 'name');
    }

    /**
     * Assets tagged under this department (used to build asset tags like
     * CFI-ACC-DT-001, where ACC is this department's code).
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
