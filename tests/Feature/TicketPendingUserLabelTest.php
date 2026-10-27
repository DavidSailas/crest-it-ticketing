<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The ticket status that used to read "Pending" now reads "Pending User" everywhere people see it.
 * The stored value is still `pending`, so filters, reports and existing tickets keep working.
 */
class TicketPendingUserLabelTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'location' => 'CEB', 'department_id' => Department::first()->id]);
    }

    private function ticket(string $status = 'pending', ?User $creator = null, ?User $assignee = null): Ticket
    {
        return Ticket::create([
            'user_id' => ($creator ?? $this->user('staff'))->id, 'assigned_to' => $assignee?->id,
            'department' => 'Accounting', 'location' => 'Cebu Office', 'category' => 'Printer', 'title' => 'Printer',
            'priority' => 'medium', 'status' => $status, 'description' => 'Paper jam',
        ]);
    }

    public function test_the_label_comes_from_one_place_and_only_pending_changed(): void
    {
        $this->assertSame('Pending User', Ticket::statusLabel('pending'));
        $this->assertSame('Open', Ticket::statusLabel('open'));
        $this->assertSame('In Progress', Ticket::statusLabel('in_progress'));
        $this->assertSame('Resolved', Ticket::statusLabel('resolved'));
        $this->assertSame('Closed', Ticket::statusLabel('closed'));
        $this->assertSame('Cancelled', Ticket::statusLabel('cancelled'));
        $this->assertSame('Something else', Ticket::statusLabel('something_else'));
    }

    public function test_the_stored_value_is_unchanged(): void
    {
        $ticket = $this->ticket('pending');

        $this->assertSame('pending', $ticket->fresh()->status);
        $this->assertSame('pending', array_key_last(array_intersect_key(Ticket::STATUS_LABELS, ['pending' => 1])));
    }

    public function test_the_badge_in_the_list_and_on_the_ticket_says_pending_user(): void
    {
        $it = $this->user('it_support');
        $ticket = $this->ticket('pending', null, $it);

        $this->actingAs($it)->get(route('tickets.index'))->assertOk()->assertSee('Pending User');
        $this->actingAs($it)->get(route('tickets.show', $ticket))->assertOk()->assertSee('Pending User');
        $this->actingAs($ticket->creator)->get(route('tickets.show', $ticket))->assertOk()->assertSee('Pending User');
    }

    public function test_the_status_filter_offers_pending_user_and_still_filters_by_the_same_value(): void
    {
        $it = $this->user('it_support');
        $pending = $this->ticket('pending', null, $it);
        $open = $this->ticket('open');
        $pending->update(['category' => 'Pending Printer']);
        $open->update(['category' => 'Open Network']);

        $page = $this->actingAs($it)->get(route('tickets.index', ['status' => 'pending']));

        $page->assertOk()
            ->assertSee('value="pending"', false)                       // the filter value is unchanged
            ->assertSee('Pending User')                                 // what people read
            ->assertSee('Pending Printer')->assertDontSee('Open Network');
    }

    public function test_the_status_dropdown_on_a_ticket_uses_the_new_label(): void
    {
        $it = $this->user('it_support');
        $ticket = $this->ticket('in_progress', null, $it);

        $this->actingAs($it)->get(route('tickets.show', $ticket))
            ->assertSee('<option value="pending"', false)
            ->assertSee('Pending User');

        $this->actingAs($this->user('admin'))->get(route('tickets.edit', $ticket))
            ->assertOk()->assertSee('Pending User')->assertSee('Waiting on a reply or action from the requester');
    }

    public function test_moving_a_ticket_to_pending_user_still_works_and_tells_the_requester(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $it = $this->user('it_support');
        $ticket = $this->ticket('in_progress', null, $it);

        $this->actingAs($it)->patch(route('tickets.status', $ticket), ['status' => 'pending'])->assertSessionHasNoErrors();

        $this->assertSame('pending', $ticket->fresh()->status);
        \Illuminate\Support\Facades\Notification::assertSentTo($ticket->creator, TicketStatusUpdatedNotification::class);
    }

    public function test_the_status_email_and_in_app_notification_say_pending_user(): void
    {
        $ticket = $this->ticket('pending');
        $notification = new TicketStatusUpdatedNotification($ticket, 'in_progress', 'pending');

        $mail = $notification->toMail($ticket->creator);
        $this->assertStringContainsString('Pending User', $mail->subject);
        $this->assertStringContainsString('**Pending User**', implode("\n", $mail->introLines));

        $data = $notification->toArray($ticket->creator);
        $this->assertSame('Ticket status: Pending User', $data['title']);
        $this->assertStringContainsString('from In Progress to Pending User', $data['message']);
    }

    public function test_the_exports_use_the_new_label(): void
    {
        $admin = $this->user('admin');
        $this->ticket('pending');

        $this->actingAs($admin)->get(route('admin.tickets.export.pdf'))->assertOk();
        $this->actingAs($admin)->get(route('admin.tickets.export.excel'))->assertOk();

        // …and the printable list the PDF is built from.
        $html = view('tickets.export-pdf', [
            'tickets' => Ticket::with(['creator', 'assignee'])->get(),
            'filterSummary' => 'All tickets', 'generatedAt' => now(),
        ])->render();
        $this->assertStringContainsString('Pending User', $html);
    }

    public function test_the_reports_use_the_new_label(): void
    {
        $admin = $this->user('admin');
        $this->ticket('pending', null, $this->user('it_support'));

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('Pending User');
        $this->actingAs($admin)->get(route('admin.reports.export.pdf'))->assertOk();
    }

    public function test_supply_requests_show_the_ticket_as_pending_user(): void
    {
        $staff = $this->user('staff');
        $supply = \App\Models\Supply::create([
            'name' => 'Toner', 'category' => 'ink_toner', 'brand' => 'Brother', 'unit' => 'pcs', 'quantity' => 5,
            'reorder_level' => 2, 'critical_level' => 1, 'location' => 'CEB',
        ]);
        $this->actingAs($staff)->post(route('supplies.request', $supply), ['quantity' => 1, 'purpose' => 'Printer', 'urgency' => 'normal']);
        Ticket::firstOrFail()->update(['status' => 'pending']);

        $this->actingAs($staff)->get(route('supplies.requests'))->assertOk()->assertSee('Ticket pending user');
    }

    public function test_an_old_supply_request_status_is_a_different_thing_and_still_says_pending(): void
    {
        // "Pending" on a supply request means "waiting for IT" — not the ticket status — so it keeps its name.
        $this->assertSame('Pending', \App\Models\SupplyRequest::STATUS_LABELS['pending']);
    }
}
