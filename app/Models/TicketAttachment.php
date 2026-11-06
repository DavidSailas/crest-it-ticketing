<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * One file attached to a ticket when it was submitted.
 */
class TicketAttachment extends Model
{
    protected $fillable = ['ticket_id', 'path', 'original_name', 'mime', 'size'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    /** Screenshots and photos are shown as thumbnails; everything else as a download chip. */
    public function isImage(): bool
    {
        if ($this->mime) {
            return str_starts_with($this->mime, 'image/');
        }

        // Files attached before this table existed have no stored type — go by the extension.
        return in_array(strtolower(pathinfo($this->original_name ?: $this->path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function getHumanSizeAttribute(): ?string
    {
        if (! $this->size) {
            return null;
        }

        return $this->size >= 1048576
            ? number_format($this->size / 1048576, 1).' MB'
            : max(1, (int) round($this->size / 1024)).' KB';
    }
}
