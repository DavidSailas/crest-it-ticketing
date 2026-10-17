<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A staff request for a supply item. It lives on a ticket:
 *   pending   -> waiting for IT Support; the quantity is reserved
 *   issued    -> ticket was Resolved and the stock was taken out automatically
 *   short     -> ticket was Resolved but there wasn't enough stock to deduct (needs a manual fix)
 * A request whose ticket gets cancelled is shown as "cancelled" and stops reserving stock.
 */
class SupplyRequest extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'issued' => 'Issued',
        'short' => 'Needs attention',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'supply_id',
        'ticket_id',
        'user_id',
        'quantity',
        'purpose',
        'status',
        'issued_at',
        'issued_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** Still waiting for IT, and its ticket hasn't been cancelled — i.e. it reserves stock. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('supply_requests.status', 'pending')
            ->whereHas('ticket', fn ($t) => $t->where('status', '!=', 'cancelled'));
    }

    /** What to show people: a pending request on a cancelled ticket is simply cancelled. */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'pending' && $this->ticket?->status === 'cancelled') {
            return 'cancelled';
        }

        return $this->status;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->display_status] ?? ucfirst($this->display_status);
    }

    /**
     * Take the requested quantity out of stock. Called when the ticket is Resolved/Closed.
     * Safe to call twice — only a pending request is acted on, and under a lock.
     */
    public function fulfil(?int $userId = null): void
    {
        DB::transaction(function () use ($userId) {
            $request = static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            if (! $request || $request->status !== 'pending') {
                return;
            }

            $supply = $request->supply;
            $ticketNumber = $request->ticket?->ticket_number ?? "#{$request->ticket_id}";

            try {
                $movement = $supply->applyMovement(
                    'out',
                    $request->quantity,
                    "Request {$ticketNumber} · {$request->requester?->name}",
                    $userId,
                    usesReservation: true
                );

                $request->update([
                    'status' => 'issued',
                    'issued_at' => now(),
                    'issued_by' => $userId,
                    'note' => null,
                ]);

                $this->syncFrom($request);
                $this->afterFulfil($request, "Supply issued: {$request->quantity} {$supply->unit} of {$supply->name}. Stock updated ({$movement->balance_after} left).", $userId);
            } catch (DomainException $e) {
                // Not enough physically in stock any more (e.g. a stock-take lowered the count).
                $request->update(['status' => 'short', 'note' => $e->getMessage()]);

                $this->syncFrom($request);
                $this->afterFulfil($request, "Supply NOT deducted: {$e->getMessage()} Record it with Stock Out once the item is handed over.", $userId);
            }
        });
    }

    private function syncFrom(self $fresh): void
    {
        $this->setRawAttributes($fresh->getAttributes(), true);
    }

    private function afterFulfil(self $request, string $message, ?int $userId): void
    {
        ActivityLog::record(
            $request->status === 'issued' ? 'supply_issued' : 'supply_short',
            $message,
            ['supply_id' => $request->supply_id, 'ticket_id' => $request->ticket_id],
            $userId ?? $request->user_id
        );

        // Leave a note in the ticket thread so the history explains the stock change.
        $request->ticket?->comments()->create([
            'user_id' => $userId ?? $request->ticket->assigned_to ?? $request->user_id,
            'body' => $message,
        ]);
    }
}
