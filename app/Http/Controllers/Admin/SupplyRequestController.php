<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Models\ActivityLog;
use App\Models\Supply;
use App\Models\SupplyRequest;
use App\Models\Ticket;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Staff ask for supplies; each request becomes a normal ticket for IT Support.
 * The quantity is reserved straight away and taken out of stock automatically
 * when IT marks that ticket Resolved (see TicketSupplyObserver).
 */
class SupplyRequestController extends Controller
{
    /** Everyone sees the same page; staff only ever see their own requests. */
    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = ! ($user->isAdmin() || $user->isItSupport());
        $filter = (string) $request->query('show', 'open');

        $query = SupplyRequest::query()
            ->with(['supply', 'ticket', 'requester:id,name', 'issuer:id,name'])
            ->when($isStaff, fn ($q) => $q->where('user_id', $user->id));

        // "Open" = still waiting on IT (cancelled tickets don't count). "All" = everything.
        $counts = [
            'open' => (clone $query)->active()->count(),
            'all' => (clone $query)->count(),
        ];

        $requests = $query
            ->when($filter !== 'all', fn ($q) => $q->active())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.supplies.requests', [
            'requests' => $requests,
            'counts' => $counts,
            'filter' => $filter === 'all' ? 'all' : 'open',
            'isStaff' => $isStaff,
        ]);
    }

    public function store(Request $request, Supply $supply)
    {
        $user = $request->user();

        $data = $request->validateWithBag('request', [
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'purpose' => ['required', 'string', 'min:3', 'max:255'],
            'urgency' => ['required', Rule::in(['normal', 'urgent'])],
        ], [
            'quantity.required' => 'How many do you need?',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Request at least 1.',
            'quantity.max' => 'That is more than one request can ask for — please ask IT Support directly.',
            'purpose.required' => 'Tell IT Support what it is for, e.g. "Accounting printer".',
            'purpose.min' => 'Please give a little more detail about what it is for.',
            'urgency.required' => 'Choose how urgent it is.',
        ]);

        // Same routing rules as any ticket: it needs a department and branch from the profile.
        if (blank($user->department?->name) || blank($user->location)) {
            return $this->fail("Your account doesn't have a department and branch set yet. Please ask an administrator to update your profile before requesting supplies.");
        }

        if ($supply->location !== $user->location) {
            return $this->fail("This item is stocked at {$supply->branch_name}, not at your branch. Please request items kept at your own office.");
        }

        try {
            $ticket = DB::transaction(function () use ($user, $supply, $data) {
                // Lock the item so two people can't reserve the last units at the same moment.
                $locked = Supply::query()->whereKey($supply->id)->lockForUpdate()->firstOrFail();
                $available = $locked->available_quantity;

                if ($available <= 0) {
                    throw new DomainException($locked->quantity > 0
                        ? "All {$locked->quantity} {$locked->unit} are already reserved for other requests."
                        : 'This item is out of stock right now.');
                }

                if ($data['quantity'] > $available) {
                    throw new DomainException("Only {$available} {$locked->unit} available to request right now.");
                }

                $alreadyAsked = $locked->requests()->active()->where('user_id', $user->id)->with('ticket')->first();
                if ($alreadyAsked) {
                    throw new DomainException("You already have a pending request for this item ({$alreadyAsked->ticket->ticket_number}). IT Support will get to it — you can follow it on that ticket.");
                }

                $ticket = Ticket::create([
                    'user_id' => $user->id,
                    'department' => $user->department->name,
                    'location' => trim($user->branch_name.' Office'),
                    'category' => StoreTicketRequest::SUPPLY_CATEGORY,
                    'subcategory' => $locked->name,
                    'title' => StoreTicketRequest::SUPPLY_CATEGORY,
                    'priority' => $data['urgency'] === 'urgent' ? 'high' : 'medium',
                    'status' => 'open',
                    'description' => "Supply request: {$data['quantity']} {$locked->unit} × {$locked->name} ({$locked->code})\nNeeded for: {$data['purpose']}",
                ]);

                $locked->requests()->create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'quantity' => $data['quantity'],
                    'purpose' => $data['purpose'],
                ]);

                return $ticket;
            });
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        ActivityLog::record(
            'ticket_created',
            "Submitted ticket {$ticket->ticket_number}: supply request for {$data['quantity']} × {$supply->name}",
            ['ticket_id' => $ticket->id, 'supply_id' => $supply->id]
        );

        return redirect()->route('tickets.confirmation', $ticket);
    }

    private function fail(string $message)
    {
        return back()->withErrors(['quantity' => $message], 'request')->withInput();
    }
}
