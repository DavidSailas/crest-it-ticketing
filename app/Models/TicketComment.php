<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketComment extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'body'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
