<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'sender_id', 'body'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($message) {
            if (empty($message->created_at)) {
                $message->created_at = now();
            }
        });
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function threadOwner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
