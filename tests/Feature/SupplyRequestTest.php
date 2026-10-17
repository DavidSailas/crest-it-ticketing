<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Supply;
use App\Models\SupplyRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Staff request supplies -> a ticket is created -> stock is reserved ->
 * stock is taken out automatically when IT marks the ticket Resolved.
 */
class SupplyRequestTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $overrides = []): User
    {
        return User::factory()->create($overrides + [
            'role' => 'staff',
            'department_id' => Department::first()->id,
            'location' => 'CEB',
        ]);
    }

    private function it(): User
    {
        return User::factory()->create(['role' => 'it_support', 'location' => 'CEB']);
    }

    private function supply(array $overrides = []): Supply
    {
        return Supply::create($overrides + [
            'name' => 'Brother TN-2380 Black Toner',
            'category' => 'ink_toner',
            'brand' => 'Brother',
            'model_no' => 'TN-2380',
            'unit' => 'cartridge',
            'quantity' => 10,
            'reorder_level' => 4,
            'critical_level' => 2,
            'location' => 'CEB',
        ]);
    }

    private function ask(User $user, Supply $supply, array $overrides = [])
    {
        return $this->actingAs($user)->post(route('supplies.request', $supply), $overrides + [
            'quantity' => 2,
            'purpose' => 'Accounting printer',
            'urgency' => 'normal',
        ]);
    }

    /** IT accepts the ticket, then moves it to $status — the way the real screens do it. */
    private function workTicket(Ticket $ticket, User $it, string $status): void
    {
        $this->actingAs($it);
        $ticket->update(['assigned_to' => $it->id, 'status' => 'in_progress']);
        $this->patch(route('tickets.status', $ticket), ['status' => $status, 'solution' => 'Handed over']);
    }

    /* ---------- Creating a request ---------- */

    public function test_a_request_becomes_a_ticket_and_reserves_stock_without_deducting_it(): void
    {
        $staff = $this->staff();
        $supply = $this->supply();

        $response = $this->ask($staff, $supply, ['urgency' => 'urgent']);

        $ticket = Ticket::firstOrFail();
        $response->assertRedirect(route('tickets.confirmation', $ticket));

        $this->assertSame($staff->id, $ticket->user_id);
        $this->assertSame('IT Supplies Request', $ticket->category);
        $this->assertSame('open', $ticket->status);
        $this->assertSame('high', $ticket->priority);
        $this->assertSame(Department::first()->name, $ticket->department);
        $this->assertStringContainsString('2 cartridge × Brother TN-2380 Black Toner', $ticket->description);
        $this->assertStringContainsString('Accounting printer', $ticket->description);

        $this->assertDatabaseHas('supply_requests', ['supply_id' => $supply->id, 'ticket_id' => $ticket->id, 'user_id' => $staff->id, 'quantity' => 2, 'status' => 'pending']);

        $supply->refresh();
        $this->assertSame(10, $supply->quantity);          // nothing deducted yet
        $this->assertSame(2, $supply->reserved_quantity);  // but 2 are promised
        $this->assertSame(8, $supply->available_quantity);
    }

    public function test_normal_urgency_is_medium_priority(): void
    {
        $this->ask($this->staff(), $this->supply());
        $this->assertSame('medium', Ticket::firstOrFail()->priority);
    }

    public function test_cannot_request_more_than_is_available(): void
    {
        $supply = $this->supply(['quantity' => 3]);

        $this->ask($this->staff(), $supply, ['quantity' => 4])->assertSessionHasErrorsIn('request', 'quantity');

        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('supply_requests', 0);
    }

    public function test_reserved_stock_cannot_be_requested_twice(): void
    {
        $supply = $this->supply(['quantity' => 3]);

        $this->ask($this->staff(), $supply, ['quantity' => 2])->assertRedirect();
        // Only 1 is still free for the next person.
        $this->ask($this->staff(), $supply, ['quantity' => 2])->assertSessionHasErrorsIn('request', 'quantity');
        $this->ask($this->staff(), $supply, ['quantity' => 1])->assertRedirect();

        $this->assertDatabaseCount('supply_requests', 2);
        $this->assertSame(0, $supply->fresh()->available_quantity);
    }

    public function test_an_out_of_stock_item_cannot_be_requested(): void
    {
        $this->ask($this->staff(), $this->supply(['quantity' => 0]))->assertSessionHasErrorsIn('request', 'quantity');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_one_pending_request_per_person_per_item(): void
    {
        $staff = $this->staff();
        $supply = $this->supply();

        $this->ask($staff, $supply)->assertRedirect();
        $this->ask($staff, $supply)->assertSessionHasErrorsIn('request', 'quantity');

        $this->assertDatabaseCount('supply_requests', 1);
    }

    public function test_staff_can_only_request_items_kept_at_their_own_branch(): void
    {
        $other = $this->supply(['location' => 'MNL']);

        $this->ask($this->staff(), $other)->assertSessionHasErrorsIn('request', 'quantity');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_a_profile_without_a_department_gets_a_clear_error(): void
    {
        $this->ask($this->staff(['department_id' => null]), $this->supply())->assertSessionHasErrorsIn('request', 'quantity');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_the_request_form_is_validated(): void
    {
        $staff = $this->staff();
        $supply = $this->supply();

        $this->ask($staff, $supply, ['quantity' => 0])->assertSessionHasErrorsIn('request', 'quantity');
        $this->ask($staff, $supply, ['purpose' => ''])->assertSessionHasErrorsIn('request', 'purpose');
        $this->ask($staff, $supply, ['urgency' => 'whenever'])->assertSessionHasErrorsIn('request', 'urgency');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_only_staff_can_use_the_request_button(): void
    {
        $supply = $this->supply();

        $this->ask($this->it(), $supply)->assertForbidden();
        $this->ask(User::factory()->create(['role' => 'admin']), $supply)->assertForbidden();
        $this->assertDatabaseCount('tickets', 0);
    }

    /* ---------- Stock comes out automatically on Resolved ---------- */

    public function test_resolving_the_ticket_takes_the_stock_out_automatically(): void
    {
        $staff = $this->staff();
        $it = $this->it();
        $supply = $this->supply();

        $this->ask($staff, $supply, ['quantity' => 3]);
        $ticket = Ticket::firstOrFail();

        $this->workTicket($ticket, $it, 'resolved');

        $supply->refresh();
        $request = SupplyRequest::firstOrFail();

        $this->assertSame(7, $supply->quantity);
        $this->assertSame(0, $supply->reserved_quantity);
        $this->assertSame('issued', $request->status);
        $this->assertSame($it->id, $request->issued_by);

        $this->assertDatabaseHas('supply_movements', [
            'supply_id' => $supply->id, 'type' => 'out', 'change' => -3, 'balance_after' => 7, 'user_id' => $it->id,
        ]);
        $this->assertStringContainsString($ticket->ticket_number, $supply->movements()->where('type', 'out')->value('note'));

        // The ticket thread explains the stock change.
        $this->assertTrue($ticket->comments()->where('body', 'like', 'Supply issued: 3 cartridge%')->exists());
    }

    public function test_closing_after_resolving_does_not_deduct_twice(): void
    {
        $supply = $this->supply();
        $it = $this->it();

        $this->ask($this->staff(), $supply, ['quantity' => 3]);
        $ticket = Ticket::firstOrFail();

        $this->workTicket($ticket, $it, 'resolved');
        $this->patch(route('tickets.status', $ticket->fresh()), ['status' => 'closed', 'solution' => 'Done']);

        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertSame(7, $supply->fresh()->quantity);
        $this->assertSame(1, $supply->movements()->where('type', 'out')->count());
    }

    public function test_closing_straight_away_also_issues_the_stock(): void
    {
        $supply = $this->supply();
        $this->ask($this->staff(), $supply, ['quantity' => 2]);

        $ticket = Ticket::firstOrFail();
        $ticket->update(['status' => 'resolved']);   // IT can skip straight past in_progress
        $ticket->update(['status' => 'closed']);

        $this->assertSame(8, $supply->fresh()->quantity);
    }

    public function test_moving_a_ticket_to_in_progress_or_pending_does_not_touch_stock(): void
    {
        $supply = $this->supply();
        $this->ask($this->staff(), $supply, ['quantity' => 2]);

        $ticket = Ticket::firstOrFail();
        $ticket->update(['status' => 'in_progress']);
        $ticket->update(['status' => 'pending']);

        $this->assertSame(10, $supply->fresh()->quantity);
        $this->assertSame('pending', SupplyRequest::firstOrFail()->status);
    }

    public function test_an_ordinary_ticket_being_resolved_is_unaffected(): void
    {
        $supply = $this->supply();
        $staff = $this->staff();

        $ticket = Ticket::create([
            'user_id' => $staff->id, 'department' => 'Accounting', 'location' => 'Cebu Office',
            'category' => 'Printer', 'title' => 'Printer', 'priority' => 'medium', 'status' => 'open', 'description' => 'Paper jam',
        ]);
        $ticket->update(['status' => 'resolved']);

        $this->assertSame(10, $supply->fresh()->quantity);
    }

    /* ---------- Cancelling frees the reservation ---------- */

    public function test_cancelling_the_ticket_cancels_the_request_and_frees_the_stock(): void
    {
        $staff = $this->staff();
        $supply = $this->supply();

        $this->ask($staff, $supply, ['quantity' => 4]);
        $this->assertSame(6, $supply->fresh()->available_quantity);

        $ticket = Ticket::firstOrFail();
        $this->actingAs($staff)->post(route('tickets.cancel', $ticket), ['reason' => 'No longer needed'])->assertRedirect();

        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame('cancelled', SupplyRequest::with('ticket')->firstOrFail()->display_status);

        $supply->refresh();
        $this->assertSame(10, $supply->quantity);
        $this->assertSame(0, $supply->reserved_quantity);

        // Free again, so it can be requested once more.
        $this->ask($staff, $supply)->assertRedirect();
    }

    /* ---------- Edge cases ---------- */

    public function test_reserved_units_cannot_be_taken_out_by_hand(): void
    {
        $supply = $this->supply(['quantity' => 5]);
        $this->ask($this->staff(), $supply, ['quantity' => 3]);

        // 5 on hand, 3 promised -> only 2 are free to take out.
        $this->actingAs($this->it())->post(route('supplies.adjust', $supply), ['type' => 'out', 'quantity' => 3])
            ->assertSessionHasErrorsIn('adjust', 'quantity');
        $this->assertSame(5, $supply->fresh()->quantity);

        $this->actingAs($this->it())->post(route('supplies.adjust', $supply), ['type' => 'out', 'quantity' => 2])
            ->assertSessionHasNoErrors();
        $this->assertSame(3, $supply->fresh()->quantity);
    }

    public function test_if_stock_vanished_before_resolving_the_request_is_flagged_not_lost(): void
    {
        $supply = $this->supply(['quantity' => 5]);
        $it = $this->it();

        $this->ask($this->staff(), $supply, ['quantity' => 4]);
        $ticket = Ticket::firstOrFail();

        // A stock-take finds only 1 left (counts are allowed to override reservations).
        $this->actingAs($it)->post(route('supplies.adjust', $supply), ['type' => 'count', 'quantity' => 1, 'note' => 'Stock-take']);

        $this->workTicket($ticket, $it, 'resolved');

        $request = SupplyRequest::firstOrFail();
        $this->assertSame('short', $request->status);
        $this->assertSame(1, $supply->fresh()->quantity);                 // nothing was deducted
        $this->assertSame('resolved', $ticket->fresh()->status);          // and the ticket was NOT blocked
        $this->assertTrue($ticket->comments()->where('body', 'like', 'Supply NOT deducted%')->exists());
    }

    public function test_an_item_with_pending_requests_cannot_be_deleted(): void
    {
        $supply = $this->supply();
        $this->ask($this->staff(), $supply);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete(route('admin.supplies.destroy', $supply))
            ->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('supplies', ['id' => $supply->id]);
    }

    /* ---------- Screens ---------- */

    public function test_staff_see_a_request_button_only_where_it_works(): void
    {
        $staff = $this->staff();
        $here = $this->supply();
        $elsewhere = $this->supply(['name' => 'Far Away Toner', 'location' => 'MNL']);
        $empty = $this->supply(['name' => 'Empty Toner', 'quantity' => 0]);

        $page = $this->actingAs($staff)->get(route('supplies.index'));
        $page->assertSee(route('supplies.show', [$here, 'request' => 1]), false)
            ->assertDontSee(route('supplies.show', [$elsewhere, 'request' => 1]), false)
            ->assertDontSee(route('supplies.show', [$empty, 'request' => 1]), false);

        $this->actingAs($staff)->get(route('supplies.show', $here))
            ->assertOk()->assertSee('Request this item')->assertSee(route('supplies.request', $here), false);

        $this->actingAs($staff)->get(route('supplies.show', $elsewhere))
            ->assertOk()->assertDontSee('Request this item');
    }

    public function test_it_support_do_not_get_a_request_button(): void
    {
        $supply = $this->supply();

        $this->actingAs($this->it())->get(route('supplies.show', $supply))
            ->assertOk()->assertDontSee('Request this item')->assertSee('Stock In');
    }

    public function test_after_requesting_the_item_page_points_to_the_ticket(): void
    {
        $staff = $this->staff();
        $supply = $this->supply();
        $this->ask($staff, $supply);
        $ticket = Ticket::firstOrFail();

        $this->actingAs($staff)->get(route('supplies.show', $supply))
            ->assertSee("You've requested 2 cartridge", false)
            ->assertSee($ticket->ticket_number)
            ->assertDontSee('Request this item');
    }

    public function test_the_requests_page_shows_staff_only_their_own_and_it_everything(): void
    {
        $alice = $this->staff(['name' => 'Alice Staff']);
        $bob = $this->staff(['name' => 'Bob Staff']);
        $toner = $this->supply();
        $ink = $this->supply(['name' => 'Epson 003 Ink', 'brand' => 'Epson']);

        $this->ask($alice, $toner);
        $this->ask($bob, $ink);

        $this->actingAs($alice)->get(route('supplies.requests'))
            ->assertOk()->assertSee('Brother TN-2380 Black Toner')->assertDontSee('Epson 003 Ink');

        $this->actingAs($this->it())->get(route('supplies.requests'))
            ->assertOk()->assertSee('Brother TN-2380 Black Toner')->assertSee('Epson 003 Ink')
            ->assertSee('Alice Staff')->assertSee('Bob Staff');
    }

    public function test_the_ticket_page_shows_the_supply_request_card(): void
    {
        $staff = $this->staff();
        $supply = $this->supply();
        $this->ask($staff, $supply, ['quantity' => 3]);
        $ticket = Ticket::firstOrFail();

        $this->actingAs($this->it())->get(route('tickets.show', $ticket))
            ->assertOk()->assertSee('Supply request')->assertSee('automatically when you mark this ticket Resolved');

        $this->actingAs($staff)->get(route('tickets.show', $ticket))
            ->assertOk()->assertSee('Supply request')->assertSee('IT Support will prepare this');

        $this->workTicket($ticket, $this->it(), 'resolved');

        $this->actingAs($staff)->get(route('tickets.show', $ticket->fresh()))
            ->assertSee('issued')->assertSee('stock has been updated');
    }

    public function test_the_ticket_page_warns_it_when_stock_is_short_of_the_request(): void
    {
        $supply = $this->supply(['quantity' => 5]);
        $this->ask($this->staff(), $supply, ['quantity' => 4]);
        $supply->update(['quantity' => 2]);

        $this->actingAs($this->it())->get(route('tickets.show', Ticket::firstOrFail()))
            ->assertSee('Only 2 on hand — restock before resolving');
    }

    public function test_the_item_list_shows_reserved_units(): void
    {
        $supply = $this->supply();
        $this->ask($this->staff(), $supply, ['quantity' => 3]);

        $this->actingAs($this->it())->get(route('supplies.index'))->assertSee('3 reserved');
    }
}
