<?php

namespace App\Models;

use App\Http\Requests\StoreTicketRequest;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A staff request for a supply item. It first waits for a department head:
 *   for_approval -> waiting for a department head; no ticket yet and no stock reserved
 *   declined     -> a department head said no (the reason is kept); no ticket is made
 * Once approved it becomes a ticket and lives there:
 *   pending   -> waiting for IT Support; the quantity is reserved
 *   issued    -> ticket was Resolved and the stock was taken out automatically
 *   short     -> ticket was Resolved but there wasn't enough stock to deduct (needs a manual fix)
 * A request whose ticket gets cancelled is shown as "cancelled" and stops reserving stock.
 */
class SupplyRequest extends Model
{
    public const STATUS_LABELS = [
        'for_approval' => 'Waiting for approval',
        'declined' => 'Declined',
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
        'urgency',
        'status',
        'issued_at',
        'issued_by',
        'approved_by',
        'approved_at',
        'declined_by',
        'declined_at',
        'decline_reason',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'issued_at' => 'datetime',
            'approved_at' => 'datetime',
            'declined_at' => 'datetime',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function decliner()
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

    /** Waiting for a department head — it has no ticket yet and reserves nothing. */
    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->where('supply_requests.status', 'for_approval');
    }

    /**
     * The requests waiting for this particular person: those routed to them (see User::approversFor),
     * or all of them for an admin. Never their own.
     */
    public static function awaitingFor(User $approver): Builder
    {
        $ids = static::query()->awaitingApproval()
            ->where('user_id', '!=', $approver->id)
            ->with('requester')
            ->get()
            ->filter(fn (self $r) => $r->requester && $approver->isApproverFor($r->requester))
            ->pluck('id');

        return static::query()->whereIn('supply_requests.id', $ids);
    }

    /** Anything not finished yet from the requester's point of view: awaiting approval, or approved and waiting for IT. */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('supply_requests.status', 'for_approval')
                ->orWhere(fn ($p) => $p->active());
        });
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

    public function isAwaitingApproval(): bool
    {
        return $this->status === 'for_approval';
    }

    /**
     * A department head approves: re-check the stock, then create the ticket for IT Support.
     * Stock is only reserved from this moment (the request becomes "pending"). Safe against
     * two heads clicking at once — the row is locked and only a request still awaiting approval is acted on.
     *
     * @throws DomainException when it was already handled or there isn't enough stock any more
     */
    public function approveBy(User $head): Ticket
    {
        return DB::transaction(function () use ($head) {
            $request = static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            if (! $request || $request->status !== 'for_approval') {
                throw new DomainException('This request was already handled by another department head.');
            }

            $supply = Supply::query()->whereKey($request->supply_id)->lockForUpdate()->firstOrFail();
            $requester = $request->requester;

            if (! $requester) {
                throw new DomainException('The person who made this request no longer has an account.');
            }

            if (blank($requester->department?->name) || blank($requester->location)) {
                throw new DomainException("{$requester->name}'s profile has no department or branch set, so a ticket can't be created. Ask an administrator to complete it.");
            }

            $available = $supply->available_quantity;
            if ($available < $request->quantity) {
                throw new DomainException($available > 0
                    ? "Only {$available} {$supply->unit} of {$supply->name} are available now — less than the {$request->quantity} requested. You can decline and ask the requester to send a smaller request."
                    : "{$supply->name} is out of stock or fully reserved right now. You can decline the request and let the requester know.");
            }

            $shipFrom = $supply->location !== $requester->location
                ? "\nStock is at {$supply->branch_name}; deliver to {$requester->branch_name}."
                : '';

            $ticket = Ticket::create([
                'user_id' => $requester->id,
                'department' => $requester->department->name,
                'location' => trim($requester->branch_name.' Office'),
                'category' => StoreTicketRequest::SUPPLY_CATEGORY,
                'subcategory' => $supply->name,
                'title' => StoreTicketRequest::SUPPLY_CATEGORY,
                'priority' => $request->urgency === 'urgent' ? 'high' : 'medium',
                'status' => 'open',
                'description' => "Supply request: {$request->quantity} {$supply->unit} × {$supply->name} ({$supply->code})\n"
                    ."Needed for: {$request->purpose}\n"
                    ."Approved by: {$head->name}".($head->position?->name ? " ({$head->position->name})" : '').' on '.now()->format('M j, Y g:i A').$shipFrom,
            ]);

            $request->update([
                'ticket_id' => $ticket->id,
                'status' => 'pending',
                'approved_by' => $head->id,
                'approved_at' => now(),
            ]);

            $this->setRawAttributes($request->getAttributes(), true);

            return $ticket;
        });
    }

    /** A department head says no. Nothing was reserved, so there is nothing to give back. */
    public function declineBy(User $head, string $reason): void
    {
        DB::transaction(function () use ($head, $reason) {
            $request = static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            if (! $request || $request->status !== 'for_approval') {
                throw new DomainException('This request was already handled by another department head.');
            }

            $request->update([
                'status' => 'declined',
                'declined_by' => $head->id,
                'declined_at' => now(),
                'decline_reason' => $reason,
            ]);

            $this->setRawAttributes($request->getAttributes(), true);
        });
    }

    /** The requester changes their mind while it is still waiting for approval. */
    public function cancelWhileWaiting(): void
    {
        DB::transaction(function () {
            $request = static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            if (! $request || $request->status !== 'for_approval') {
                throw new DomainException('This request can no longer be cancelled — it was already handled.');
            }

            $request->update(['status' => 'cancelled']);

            $this->setRawAttributes($request->getAttributes(), true);
        });
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
