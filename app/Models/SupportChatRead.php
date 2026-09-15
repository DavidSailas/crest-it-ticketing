<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportChatRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['thread_user_id', 'reader_id', 'last_read_at'];

    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    public static function markRead(int $threadUserId, int $readerId): void
    {
        static::updateOrCreate(
            ['thread_user_id' => $threadUserId, 'reader_id' => $readerId],
            ['last_read_at' => now()]
        );
    }
}
