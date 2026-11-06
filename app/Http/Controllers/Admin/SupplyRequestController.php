<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Supply;
use App\Models\SupplyRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SupplyRequestAwaitingApprovalNotification;
use App\Notifications\SupplyRequestDecisionNotification;
use App\Support\Notifier;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Staff ask for supplies. A request first goes to the employee's own approver — the head,
 * manager or junior manager an admin picked for them (falling back to approvers in the same
 * department and branch). When that person approves it, it becomes a normal ticket for IT Support, the
 * quantity is reserved, and it is taken out of stock automatically when IT marks that
 * ticket Resolved (see TicketSupplyObserver). A head with no approver of their own needs no approval.
 */
class SupplyRequestController extends Controller
{
    /** Everyone sees the same page; staff only ever see their own requests (approvers also get a tab with the requests waiting for them). */
    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = ! ($user->isAdmin() || $user->isItSupport());
        $canApprove = $user->canApproveSupplyRequests();

        $counts = ['approval' => $canApprove ? SupplyRequest::awaitingFor($user)->count() : 0];

        // Heads land on the approvals tab when something is waiting for them.
        $filter = (string) $request->query('show', $canApprove && $counts['approval'] > 0 ? 'approval' : 'open');
        if (! in_array($filter, ['open', 'all', 'approval'], true) || ($filter === 'approval' && ! $canApprove)) {
            $filter = 'open';
        }

        $query = SupplyRequest::query()
            ->with(['supply', 'ticket', 'requester:id,name,location', 'issuer:id,name', 'approver:id,name', 'decliner:id,name'])
            ->when($isStaff, fn ($q) => $q->where('user_id', $user->id));

        // IT Support only sees requests that already became tickets; staff also see the ones still waiting for approval.
        $openScope = $isStaff ? 'inProgress' : 'active';
        $counts['open'] = (clone $query)->{$openScope}()->count();
        $counts['all'] = (clone $query)->count();

        if ($filter === 'approval') {
            $listing = SupplyRequest::awaitingFor($user)
                ->with(['supply', 'requester:id,name,location'])
                ->oldest();
        } else {
            $listing = $query
                ->when($filter !== 'all', fn ($q) => $q->{$openScope}())
                ->latest();
        }

        $requests = $listing->paginate(15)->withQueryString();

        // A link from a notification points at one request. If someone already handled it, say so
        // instead of leaving the approver wondering where it went.
        $focus = (int) $request->query('focus');
        $focusNote = null;
        if ($filter === 'approval' && $focus && ! SupplyRequest::awaitingFor($user)->whereKey($focus)->exists()) {
            $focusNote = 'That request has already been handled (approved, declined or cancelled) — nothing more to do.';
        }

        return view('admin.supplies.requests', [
            'focus' => $focus,
            'focusNote' => $focusNote,
            'requests' => $requests,
            'counts' => $counts,
            'filter' => $filter,
            'isStaff' => $isStaff,
            'canApprove' => $canApprove,
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
            'purpose.required' => 'Tell your head what it is for, e.g. "Accounting printer".',
            'purpose.min' => 'Please give a little more detail about what it is for.',
            'urgency.required' => 'Choose how urgent it is.',
        ]);

        // Same routing rules as any ticket: it needs a department and branch from the profile.
        if (blank($user->department?->name) || blank($user->location)) {
            return $this->fail("Your account doesn't have a department and branch set yet. Please ask an administrator to update your profile before requesting supplies.");
        }

        if (! $supply->canBeRequestedBy($user)) {
            return $this->fail("This item is stocked at {$supply->branch_name}. You can request items from your own branch or from the main stock in ".Branch::nameForCode(config('supplies.main_branch')).'.');
        }

        try {
            $supplyRequest = DB::transaction(function () use ($user, $supply, $data) {
                // Lock the item so the stock check below can't race another request.
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

                $alreadyAsked = $locked->requests()->inProgress()->where('user_id', $user->id)->with('ticket')->first();
                if ($alreadyAsked) {
                    throw new DomainException($alreadyAsked->isAwaitingApproval()
                        ? 'You already have a request for this item waiting for your head to approve. You will be notified once it is decided.'
                        : "You already have a pending request for this item ({$alreadyAsked->ticket->ticket_number}). IT Support will get to it — you can follow it on that ticket.");
                }

                // No ticket and no reserved stock yet: the employee's approver has to approve it first.
                return $locked->requests()->create([
                    'user_id' => $user->id,
                    'quantity' => $data['quantity'],
                    'purpose' => $data['purpose'],
                    'urgency' => $data['urgency'],
                    'status' => 'for_approval',
                ]);
            });
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        // Admins, and heads with no approver of their own, don't need anyone's approval.
        if (! $user->requiresSupplyApproval()) {
            try {
                $ticket = $supplyRequest->approveBy($user);
            } catch (DomainException $e) {
                $supplyRequest->delete();

                return $this->fail($e->getMessage());
            }

            ActivityLog::record(
                'ticket_created',
                "Submitted ticket {$ticket->ticket_number}: supply request for {$data['quantity']} × {$supply->name}",
                ['ticket_id' => $ticket->id, 'supply_id' => $supply->id]
            );

            Notifier::ticketCreated($ticket);

            return redirect()->route('tickets.confirmation', $ticket);
        }

        ActivityLog::record(
            'supply_request_submitted',
            "Requested {$data['quantity']} × {$supply->name} — waiting for approval",
            ['supply_id' => $supply->id, 'supply_request_id' => $supplyRequest->id]
        );

        $supplyRequest->load(['supply', 'requester']);
        $approvers = User::approversFor($user);
        Notifier::send($approvers, new SupplyRequestAwaitingApprovalNotification($supplyRequest));

        $who = $approvers->pluck('name')->take(2)->implode(' or ');

        return redirect()->route('supplies.requests', ['show' => 'open'])
            ->with('status', "Request sent to ".($who ?: 'your approver')." for approval. You'll be notified as soon as it is decided — once approved it becomes a ticket for IT Support.");
    }

    public function approve(Request $request, SupplyRequest $supplyRequest)
    {
        $head = $request->user();
        abort_unless($supplyRequest->requester && $head->isApproverFor($supplyRequest->requester), 403);

        try {
            $ticket = $supplyRequest->approveBy($head);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $supplyRequest->load(['supply', 'requester', 'ticket']);

        ActivityLog::record(
            'ticket_created',
            "Approved supply request: {$supplyRequest->quantity} × {$supplyRequest->supply->name} for {$supplyRequest->requester?->name} (ticket {$ticket->ticket_number})",
            ['ticket_id' => $ticket->id, 'supply_id' => $supplyRequest->supply_id]
        );

        Notifier::send($supplyRequest->requester, new SupplyRequestDecisionNotification($supplyRequest, $head));
        Notifier::ticketCreated($ticket, false);   // IT hears about the new ticket; the requester gets the approval email above

        return back()->with('status', "Approved. Ticket {$ticket->ticket_number} was created for IT Support.");
    }

    public function decline(Request $request, SupplyRequest $supplyRequest)
    {
        $head = $request->user();
        abort_unless($supplyRequest->requester && $head->isApproverFor($supplyRequest->requester), 403);

        $data = $request->validateWithBag('decline', [
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'reason.required' => 'Tell the requester why it was declined.',
            'reason.min' => 'Please give a little more detail.',
        ]);

        try {
            $supplyRequest->declineBy($head, trim($data['reason']));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $supplyRequest->load(['supply', 'requester']);

        ActivityLog::record(
            'supply_request_declined',
            "Declined supply request: {$supplyRequest->quantity} × {$supplyRequest->supply->name} for {$supplyRequest->requester?->name} — {$supplyRequest->decline_reason}",
            ['supply_id' => $supplyRequest->supply_id, 'supply_request_id' => $supplyRequest->id]
        );

        Notifier::send($supplyRequest->requester, new SupplyRequestDecisionNotification($supplyRequest, $head));

        return back()->with('status', 'Request declined. The requester was told why.');
    }

    public function cancel(Request $request, SupplyRequest $supplyRequest)
    {
        abort_unless($supplyRequest->user_id === $request->user()->id, 403);

        try {
            $supplyRequest->cancelWhileWaiting();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Request cancelled.');
    }

    private function fail(string $message)
    {
        return back()->withErrors(['quantity' => $message], 'request')->withInput();
    }
}
