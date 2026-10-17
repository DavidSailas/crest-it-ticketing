<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyMovement extends Model
{
    protected $fillable = [
        'supply_id',
        'user_id',
        'type',
        'change',
        'balance_after',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'change' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return Supply::MOVEMENT_LABELS[$this->type] ?? ucfirst($this->type);
    }
}
