<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Supply;
use App\Models\SupplyRequest;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * IT Supplies inventory (ink, batteries, mice, keyboards, USB drives…).
 *
 * Who can do what is enforced in routes/supplies.php:
 *   Staff       — view the list and an item's history
 *   IT Support  — view, add, edit, and adjust stock
 *   Admin       — everything above, plus delete
 */
class SupplyController extends Controller
{
    public function index(Request $request)
    {
        $supplies = $this->supplyQuery($request)
            ->withReserved()
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Overview numbers follow the search + branch only, so the status cards
        // and category tiles always show the full breakdown and stay clickable.
        $summary = $this->summarize($this->supplyQuery($request, false));

        $hasFilters = $request->filled('search')
            || $request->filled('location')
            || $request->filled('category')
            || $request->filled('status');

        $branches = Branch::options();

        // For the "Requests" button: IT sees everyone's open requests, staff only their own.
        $user = $request->user();
        $openRequests = SupplyRequest::query()->active()
            ->when(! ($user->isAdmin() || $user->isItSupport()), fn ($q) => $q->where('user_id', $user->id))
            ->count();

        $this->rememberList($request, 'supplies');

        return view('admin.supplies.index', compact('supplies', 'summary', 'hasFilters', 'branches', 'openRequests'));
    }

    public function show(Supply $supply)
    {
        $movements = $supply->movements()
            ->with('user:id,name')
            ->latest()
            ->latest('id')
            ->paginate(10);

        $user = request()->user();
        $myPending = $supply->requests()->active()->where('user_id', $user->id)->with('ticket')->first();

        return view('admin.supplies.show', compact('supply', 'movements', 'myPending'));
    }

    public function create()
    {
        $branches = Branch::options();

        return view('admin.supplies.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->detailRules($request) + [
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], $this->messages());

        if ($existing = $this->duplicateOf($data)) {
            return back()
                ->withErrors(['name' => "\"{$existing->name}\" is already in {$existing->branch_name}'s inventory ({$existing->code}). Adjust its stock instead of adding it again."])
                ->withInput();
        }

        $supply = DB::transaction(function () use ($data) {
            $supply = Supply::create($data);

            if ($supply->quantity > 0) {
                $supply->movements()->create([
                    'user_id' => auth()->id(),
                    'type' => 'initial',
                    'change' => $supply->quantity,
                    'balance_after' => $supply->quantity,
                    'note' => 'Opening stock',
                ]);
            }

            return $supply;
        });

        ActivityLog::record('supply_created', "Added supply {$supply->code} {$supply->name}", ['supply_id' => $supply->id]);

        return redirect()->route('supplies.index')->with('status', "Added {$supply->name} ({$supply->code}).");
    }

    public function edit(Supply $supply)
    {
        $branches = Branch::options();

        return view('admin.supplies.edit', compact('supply', 'branches'));
    }

    /**
     * Updates the item's details only. The quantity is changed through
     * adjust() so every change is recorded.
     */
    public function update(Request $request, Supply $supply)
    {
        $data = $request->validate($this->detailRules($request), $this->messages());

        if ($existing = $this->duplicateOf($data, $supply->id)) {
            return back()
                ->withErrors(['name' => "\"{$existing->name}\" is already in {$existing->branch_name}'s inventory ({$existing->code})."])
                ->withInput();
        }

        $supply->update($data);

        ActivityLog::record('supply_updated', "Updated supply {$supply->code} {$supply->name}", ['supply_id' => $supply->id]);

        return redirect()
            ->to($this->listUrl('supplies', route('supplies.index')))
            ->with('status', "Updated {$supply->name}.");
    }

    /**
     * Stock in (received), stock out (issued / used) or a count correction.
     */
    public function adjust(Request $request, Supply $supply)
    {
        $isCount = $request->input('type') === 'count';

        $data = $request->validateWithBag('adjust', [
            'type' => ['required', Rule::in(['in', 'out', 'count'])],
            'quantity' => ['required', 'integer', 'max:1000000', $isCount ? 'min:0' : 'min:1'],
            'note' => [$isCount ? 'required' : 'nullable', 'string', 'max:255'],
        ], [
            'type.required' => 'Choose what happened to the stock.',
            'quantity.required' => 'Enter a quantity.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => $isCount ? 'The counted quantity can\'t be negative.' : 'Quantity must be at least 1.',
            'quantity.max' => 'That quantity is too large.',
            'note.required' => 'Add a short reason for the count correction.',
        ]);

        try {
            $movement = $supply->applyMovement($data['type'], (int) $data['quantity'], $data['note'] ?? null, auth()->id());
        } catch (DomainException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()], 'adjust')->withInput();
        }

        ActivityLog::record(
            'supply_stock_'.$data['type'],
            sprintf('%s %s: %+d (now %d)', $movement->type_label, $supply->name, $movement->change, $movement->balance_after),
            ['supply_id' => $supply->id, 'change' => $movement->change, 'balance_after' => $movement->balance_after]
        );

        return redirect()
            ->route('supplies.show', $supply)
            ->with('status', "{$movement->type_label} recorded — {$supply->name} now has {$supply->quantity} {$supply->unit}.");
    }

    /** Admin only (see routes). Removes the item and its history. */
    public function destroy(Supply $supply)
    {
        $waiting = $supply->requests()->active()->count();
        if ($waiting > 0) {
            return redirect()
                ->to($this->listUrl('supplies', route('supplies.index')))
                ->with('error', "{$supply->name} has {$waiting} pending request".($waiting === 1 ? '' : 's').' — resolve or cancel '.($waiting === 1 ? 'it' : 'them').' before deleting this item.');
        }

        $name = $supply->name;
        $code = $supply->code;
        $supply->delete();

        ActivityLog::record('supply_deleted', "Deleted supply {$code} {$name}");

        return redirect()
            ->to($this->listUrl('supplies', route('supplies.index')))
            ->with('status', "Removed {$name} from supplies.");
    }

    /* ---------- Helpers ---------- */

    /** Validation shared by create and edit (everything except the quantity). */
    private function detailRules(Request $request): array
    {
        // Ink & toner is only bought for the company's printers: Brother and Epson.
        $isInk = $request->input('category') === 'ink_toner';

        return [
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(Supply::CATEGORIES))],
            'brand' => $isInk
                ? ['required', 'string', Rule::in(Supply::PRINTER_BRANDS)]
                : ['nullable', 'string', 'max:100'],
            'model_no' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', Rule::in(Supply::UNITS)],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
            'critical_level' => ['required', 'integer', 'min:0', 'max:1000000', 'lte:reorder_level'],
            'location' => ['required', Rule::in(array_keys(Branch::options()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Give the item a name, e.g. "HP 305A Black Toner".',
            'category.required' => 'Choose a category.',
            'category.in' => 'Choose one of the listed categories.',
            'unit.required' => 'Choose a unit.',
            'reorder_level.required' => 'Set a reorder level (use 0 if you don\'t need alerts).',
            'reorder_level.integer' => 'Reorder level must be a whole number.',
            'critical_level.required' => 'Set a critical level (use 0 for no separate critical warning).',
            'critical_level.integer' => 'Critical level must be a whole number.',
            'critical_level.lte' => "The critical level can't be higher than the reorder level — it's the more urgent warning.",
            'brand.required' => 'Pick Brother or Epson — ink & toner is only stocked for those printers.',
            'brand.in' => 'Ink & toner is only stocked for Brother and Epson printers.',
            'quantity.required' => 'Enter the quantity on hand (0 is fine).',
            'quantity.integer' => 'Quantity must be a whole number.',
            'location.required' => 'Choose the branch this stock is kept in.',
            'location.in' => 'Choose one of the listed branches.',
        ];
    }

    /**
     * The same item already stocked at the same branch (name + brand + model,
     * case-insensitive)? Then people should adjust it, not add a second row.
     */
    private function duplicateOf(array $data, ?int $ignoreId = null): ?Supply
    {
        $same = function ($query, string $column) use ($data) {
            filled($data[$column] ?? null)
                ? $query->whereRaw("LOWER({$column}) = ?", [mb_strtolower($data[$column])])
                : $query->whereNull($column);
        };

        return Supply::query()
            ->where('location', $data['location'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->tap(fn ($q) => $same($q, 'brand'))
            ->tap(fn ($q) => $same($q, 'model_no'))
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->first();
    }

    private function supplyQuery(Request $request, bool $withFacets = true)
    {
        $category = (string) $request->query('category', '');
        $status = (string) $request->query('status', '');

        return Supply::query()
            // Name, brand, or model/part number. % and _ typed by the user are plain characters.
            ->when(trim((string) $request->query('search', '')) !== '', function ($query) use ($request) {
                $like = '%'.addcslashes(trim((string) $request->query('search')), '%_\\').'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('model_no', 'like', $like);
                });
            })
            ->when(array_key_exists((string) $request->query('location'), Branch::options()), function ($query) use ($request) {
                $query->where('location', (string) $request->query('location'));
            })
            ->when($withFacets && array_key_exists($category, Supply::CATEGORIES), fn ($q) => $q->where('category', $category))
            ->when($withFacets && array_key_exists($status, Supply::STOCK_STATUSES), fn ($q) => $q->stockStatus($status));
    }

    /** Headline numbers plus a per-category breakdown (every category listed, even at zero). */
    private function summarize($query): array
    {
        $rows = $query->toBase()
            ->selectRaw('category, COUNT(*) as total, SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_count, SUM(CASE WHEN quantity > 0 AND critical_level > 0 AND quantity <= critical_level THEN 1 ELSE 0 END) as critical_count, SUM(CASE WHEN quantity > 0 AND quantity <= reorder_level AND NOT (critical_level > 0 AND quantity <= critical_level) THEN 1 ELSE 0 END) as low_count')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        $byCategory = [];
        foreach (Supply::CATEGORIES as $code => $label) {
            $row = $rows->get($code);

            $byCategory[$code] = [
                'label' => $label,
                'total' => (int) ($row->total ?? 0),
                'low' => (int) ($row->low_count ?? 0),
                'critical' => (int) ($row->critical_count ?? 0),
                'out' => (int) ($row->out_count ?? 0),
            ];
        }

        $total = (int) $rows->sum('total');
        $low = (int) $rows->sum('low_count');
        $critical = (int) $rows->sum('critical_count');
        $out = (int) $rows->sum('out_count');

        return [
            'total' => $total,
            'ok' => $total - $low - $critical - $out,
            'low' => $low,
            'critical' => $critical,
            'out' => $out,
            'byCategory' => $byCategory,
        ];
    }
}
