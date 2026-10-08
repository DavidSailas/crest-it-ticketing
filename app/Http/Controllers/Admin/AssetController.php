<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Department;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    /**
     * Inventory list — every asset across all users. Admins get full
     * create/edit/delete controls on this page; IT Support sees the same
     * table read-only (enforced in the view, not here, since both roles
     * are allowed to view this route).
     */
    public function index(Request $request)
    {
        $assets = $this->assetQuery($request)
            ->with(['user', 'department'])
            ->orderBy('asset_tag')
            ->paginate(20)
            ->withQueryString();

        // The overview counts follow the search + department only (not the
        // type / assignment / status facets), so the type tiles always show the
        // full breakdown and stay clickable as filters.
        $summary = $this->summarize($this->assetQuery($request, false));

        $hasFilters = $request->filled('search')
            || $request->filled('department_id')
            || $request->filled('location')
            || $request->filled('type')
            || $request->filled('assignment')
            || $request->filled('status');

        $departments = Department::orderBy('name')->get();

        $this->rememberList($request, 'assets');

        return view('admin.assets.index', compact('assets', 'departments', 'summary', 'hasFilters'));
    }

    /**
     * Full-page "Assign New Asset" form — mirrors Admin > Add User rather
     * than a modal, so it gets its own URL, works better on mobile, and
     * doesn't need to carry the whole inventory table's Alpine state.
     */
    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $departments = Department::orderBy('name')->get();

        return view('admin.assets.create', compact('users', 'departments'));
    }

    /**
     * Assign a new asset to a user, or leave it unassigned in inventory.
     * The asset tag (e.g. CFI-CEB-IT-LT-001) is normally generated
     * automatically from the company, location, department code, device
     * type, and the next number in that sequence — but the tag number can
     * also be typed in by hand (e.g. to match an existing physical label,
     * or to backfill assets logged elsewhere). When typed in, it's checked
     * against the same company/location/department/type group so it still
     * can't collide with another asset.
     *
     * $user is route-bound when this comes from a specific user's "Assign
     * Asset" form (admin.users.assets.store); it's null on the standalone
     * "New Asset" page, which posts an optional user_id in the body so the
     * asset can be left unassigned.
     */
    public function store(Request $request, ?User $user = null)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'company' => ['required', 'in:'.implode(',', array_keys(Asset::COMPANIES))],
            'location' => ['required', 'in:'.implode(',', array_keys(Asset::locations()))],
            'department_id' => ['required', 'exists:departments,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(Asset::TYPES))],
            'sequence' => ['nullable', 'integer', 'min:1', 'max:999'],
            'device_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'assigned_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'company.required' => 'Choose which company this asset belongs to.',
            'location.required' => 'Choose a location.',
            'department_id.required' => 'Choose a department.',
            'type.required' => 'Choose a device type.',
            'sequence.integer' => 'Tag number must be a number.',
            'sequence.min' => 'Tag number must be at least 1.',
            'sequence.max' => 'Tag number can\'t exceed 999 (three digits).',
            'assigned_date.before_or_equal' => 'Assigned date can\'t be in the future.',
        ]);

        // Route-bound user (from a user's own page) wins; otherwise fall
        // back to whatever was picked (or left blank) on the New Asset form.
        $ownerId = $user?->id ?? $validated['user_id'] ?? null;

        $department = Department::findOrFail($validated['department_id']);

        if (blank($department->code)) {
            return back()->with('error', "\"{$department->name}\" doesn't have an asset code yet. Set one on the Departments page first.")->withInput();
        }

        if (filled($validated['sequence'] ?? null)) {
            // A number was typed in by hand — use it, but make sure it
            // doesn't collide with another asset in the same group.
            $sequence = (int) $validated['sequence'];
            $tag = Asset::formatTag($validated['company'], $validated['location'], $department->code, $validated['type'], $sequence);

            if ($existing = Asset::with('user')->where('asset_tag', $tag)->first()) {
                $message = $this->tagTakenMessage($tag, $existing, $validated['company'], $validated['location'], $department, $validated['type']);

                // The "Assign Asset" form on a user's page has no field-level error spot, so it gets the banner.
                return $user
                    ? back()->with('error', $message)->withInput()
                    : back()->withErrors(['sequence' => $message])->withInput();
            }
        } else {
            [$sequence, $tag] = Asset::nextTag($validated['company'], $validated['location'], $department->id, $department->code, $validated['type']);
        }

        try {
            Asset::create([
            'user_id' => $ownerId,
            'department_id' => $department->id,
            'company' => $validated['company'],
            'location' => $validated['location'],
            'type' => $validated['type'],
            'sequence' => $sequence,
            'asset_tag' => $tag,
            'device_name' => $validated['device_name'],
            'serial_number' => $validated['serial_number'] ?? null,
            // Defaults to today if left blank, since most assets are logged
            // the same day they're physically handed over.
            'assigned_date' => $validated['assigned_date'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
            ]);
        } catch (QueryException $e) {
            // Two people saving the same tag at the same instant: the database's unique
            // index is the last line of defence. Show a friendly message, not a 500.
            if (str_contains(strtolower($e->getMessage()), 'asset_tag')) {
                $message = "{$tag} was just taken by someone else. Please try again — leave the number blank to get the next free one.";

                return $user
                    ? back()->with('error', $message)->withInput()
                    : back()->withErrors(['sequence' => $message])->withInput();
            }
            throw $e;
        }

        $ownerName = $ownerId ? User::find($ownerId)?->name : null;
        $status = $ownerName
            ? "Asset {$tag} assigned to {$ownerName}."
            : "Asset {$tag} added to inventory as unassigned.";

        return redirect()->route('assets.index')->with('status', $status);
    }

    /**
     * Full-page "Edit Asset" form — mirrors the "New Asset" page rather
     * than a modal, so it gets its own URL, works better on mobile, and
     * doesn't need to carry the whole inventory table's Alpine state.
     */
    public function edit(Asset $asset)
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $departments = Department::orderBy('name')->get();

        return view('admin.assets.edit', compact('asset', 'users', 'departments'));
    }

    /**
     * Edit an asset's details. Every part of the tag can be corrected here —
     * company, location, department, device type, and the sequence number
     * (the 001/002 at the end). Changing any of them regenerates the tag and
     * checks it doesn't collide with another asset.
     */
    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'device_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', array_keys(Asset::STATUSES))],
            // Tag parts are optional so the quick-edit / unassign forms on the
            // user page (which only send the number) keep working unchanged.
            'company' => ['sometimes', 'required', 'in:'.implode(',', array_keys(Asset::COMPANIES))],
            'location' => ['sometimes', 'required', 'in:'.implode(',', array_keys(Asset::locations()))],
            'department_id' => ['sometimes', 'required', 'exists:departments,id'],
            'type' => ['sometimes', 'required', 'in:'.implode(',', array_keys(Asset::TYPES))],
            'sequence' => ['required', 'integer', 'min:1', 'max:999'],
            'assigned_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'company.required' => 'Choose which company this asset belongs to.',
            'location.required' => 'Choose a location.',
            'department_id.required' => 'Choose a department.',
            'type.required' => 'Choose a device type.',
            'sequence.required' => 'Enter a sequence number.',
            'sequence.min' => 'Sequence number must be at least 1.',
            'sequence.max' => 'Sequence number can\'t exceed 999 (three digits).',
            'assigned_date.before_or_equal' => 'Assigned date can\'t be in the future.',
        ]);

        $company = $validated['company'] ?? $asset->company;
        $location = $validated['location'] ?? $asset->location;
        $type = $validated['type'] ?? $asset->type;
        $department = isset($validated['department_id'])
            ? Department::findOrFail($validated['department_id'])
            : $asset->department;

        if (blank($department?->code)) {
            return back()->with('error', "\"{$department?->name}\" doesn't have an asset code yet. Set one on the Departments page first.")->withInput();
        }

        $newTag = Asset::formatTag($company, $location, $department->code, $type, (int) $validated['sequence']);

        $existing = Asset::with('user')->where('asset_tag', $newTag)->where('id', '!=', $asset->id)->first();
        if ($existing) {
            $message = $this->tagTakenMessage($newTag, $existing, $company, $location, $department, $type, $asset->id);

            // The full edit page shows the message under the Tag Number field; the quick-edit
            // forms on the user page (which only send the number) use the top banner instead.
            return $request->has('company')
                ? back()->withErrors(['sequence' => $message])->withInput()
                : back()->with('error', $message)->withInput();
        }

        $asset->update([
            'user_id' => $validated['user_id'] ?? null,
            'device_name' => $validated['device_name'],
            'serial_number' => $validated['serial_number'] ?? null,
            'status' => $validated['status'],
            'assigned_date' => $validated['assigned_date'] ?? $asset->assigned_date,
            'notes' => $validated['notes'] ?? null,
            'company' => $company,
            'location' => $location,
            'type' => $type,
            'department_id' => $department->id,
            'sequence' => $validated['sequence'],
            'asset_tag' => $newTag,
        ]);

        return redirect()->to($this->listUrl('assets', route('assets.index')))->with('status', "Updated asset {$newTag}.");
    }

    public function destroy(Asset $asset)
    {
        $tag = $asset->asset_tag;
        $asset->delete();

        return back()->with('status', "Removed asset {$tag}.");
    }

    /**
     * Live check used by the New / Edit Asset forms: is this tag free, who has it if not,
     * and what is the next free number? Always answers with JSON and never changes data.
     */
    public function checkTag(Request $request)
    {
        $companies = array_keys(Asset::COMPANIES);
        $locations = array_keys(Asset::locations());
        $types = array_keys(Asset::TYPES);

        $company = $request->query('company');
        $location = $request->query('location');
        $type = $request->query('type');
        $departmentId = $request->query('department_id');

        if (! in_array($company, $companies, true) || ! in_array($location, $locations, true)
            || ! in_array($type, $types, true) || ! ctype_digit((string) $departmentId)) {
            return response()->json(['ready' => false]);
        }

        $department = Department::find($departmentId);
        if (! $department) {
            return response()->json(['ready' => false]);
        }
        if (blank($department->code)) {
            return response()->json([
                'ready' => false,
                'problem' => "\"{$department->name}\" doesn't have an asset code yet. Set one on the Departments page first.",
            ]);
        }

        $ignoreId = ctype_digit((string) $request->query('ignore')) ? (int) $request->query('ignore') : null;
        $nextSequence = $this->nextFreeSequence($company, $location, $department, $type, $ignoreId);

        $payload = [
            'ready' => true,
            'next' => $nextSequence === null ? null : [
                'sequence' => $nextSequence,
                'tag' => Asset::formatTag($company, $location, $department->code, $type, $nextSequence),
            ],
        ];

        $sequence = $request->query('sequence');
        if ($sequence === null || $sequence === '') {
            return response()->json($payload + ['tag' => null, 'taken' => false]);
        }
        if (! ctype_digit((string) $sequence) || (int) $sequence < 1 || (int) $sequence > 999) {
            return response()->json($payload + ['tag' => null, 'taken' => false, 'invalid' => 'Use a whole number from 1 to 999.']);
        }

        $tag = Asset::formatTag($company, $location, $department->code, $type, (int) $sequence);
        $existing = Asset::with('user')->where('asset_tag', $tag)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->first();

        return response()->json($payload + [
            'tag' => $tag,
            'taken' => (bool) $existing,
            'existing' => $existing ? [
                'device_name' => $existing->device_name,
                'owner' => $existing->user?->name,
                'status' => Asset::STATUSES[$existing->status] ?? ucfirst(str_replace('_', ' ', (string) $existing->status)),
                'url' => route('assets.edit', $existing),
            ] : null,
        ]);
    }

    /** Lowest number above the current highest one whose tag isn't used yet (null if 999 is reached). */
    private function nextFreeSequence(string $company, string $location, Department $department, string $type, ?int $ignoreId = null): ?int
    {
        $next = (int) Asset::where('company', $company)
            ->where('location', $location)
            ->where('department_id', $department->id)
            ->where('type', $type)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->max('sequence') + 1;

        while ($next <= 999 && Asset::where('asset_tag', Asset::formatTag($company, $location, $department->code, $type, $next))
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $next++;
        }

        return $next <= 999 ? $next : null;
    }

    /** Plain-English "that tag is taken" message: who has it, plus the next number that is free. */
    private function tagTakenMessage(string $tag, Asset $existing, string $company, string $location, Department $department, string $type, ?int $ignoreId = null): string
    {
        $who = $existing->user?->name ? "assigned to {$existing->user->name}" : 'in inventory (unassigned)';
        $message = "{$tag} is already used by \"{$existing->device_name}\", {$who}.";

        $next = $this->nextFreeSequence($company, $location, $department, $type, $ignoreId);

        return $next
            ? $message.' The next free number is '.str_pad((string) $next, 3, '0', STR_PAD_LEFT).'.'
            : $message.' Please choose a different number.';
    }

    /**
     * The one place inventory filters are applied — used by the list page,
     * the overview counts, and both exports so they always agree.
     *
     * $withFacets = false leaves out the type / assignment / status filters
     * (used for the overview tiles, which should show the whole breakdown).
     */
    private function assetQuery(Request $request, bool $withFacets = true)
    {
        $type = (string) $request->query('type', '');
        $assignment = (string) $request->query('assignment', '');
        $status = (string) $request->query('status', '');

        return Asset::query()
            // Matches the tag, device name, serial number, or the name of the
            // person the asset is assigned to. % and _ typed by the user are
            // treated as plain characters, not wildcards.
            ->when(trim((string) $request->query('search', '')) !== '', function ($query) use ($request) {
                $like = '%'.addcslashes(trim((string) $request->query('search')), '%_\\').'%';
                $query->where(function ($q) use ($like) {
                    $q->where('asset_tag', 'like', $like)
                        ->orWhere('device_name', 'like', $like)
                        ->orWhere('serial_number', 'like', $like)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
                });
            })
            ->when($request->filled('department_id'), function ($query) use ($request) {
                $query->where('department_id', $request->integer('department_id'));
            })
            // Branch office / location (stored as the branch code, e.g. CEB).
            ->when(array_key_exists((string) $request->query('location'), Asset::locations()), function ($query) use ($request) {
                $query->where('location', (string) $request->query('location'));
            })
            ->when($withFacets && array_key_exists($type, Asset::TYPES), fn ($q) => $q->where('type', $type))
            ->when($withFacets && $assignment === 'unassigned', fn ($q) => $q->whereNull('user_id'))
            ->when($withFacets && $assignment === 'assigned', fn ($q) => $q->whereNotNull('user_id'))
            ->when($withFacets && array_key_exists($status, Asset::STATUSES), fn ($q) => $q->where('status', $status));
    }

    /**
     * Headline numbers for a set of assets: totals, assigned vs unassigned,
     * repair/retired, and a per-device-type breakdown (every type is listed,
     * even at zero, so the report always reads the same way).
     */
    private function summarize($query): array
    {
        $rows = $query->toBase()
            ->selectRaw("type, COUNT(*) as total, SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as unassigned, SUM(CASE WHEN status = 'in_repair' THEN 1 ELSE 0 END) as in_repair, SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired")
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $byType = [];
        foreach (Asset::TYPES as $code => $label) {
            $row = $rows->get($code);
            $total = (int) ($row->total ?? 0);
            $unassigned = (int) ($row->unassigned ?? 0);

            $byType[$code] = [
                'label' => $label,
                'total' => $total,
                'assigned' => $total - $unassigned,
                'unassigned' => $unassigned,
                'in_repair' => (int) ($row->in_repair ?? 0),
            ];
        }

        $total = (int) $rows->sum('total');
        $unassigned = (int) $rows->sum('unassigned');

        return [
            'total' => $total,
            'assigned' => $total - $unassigned,
            'unassigned' => $unassigned,
            'in_repair' => (int) $rows->sum('in_repair'),
            'retired' => (int) $rows->sum('retired'),
            'byType' => $byType,
        ];
    }

    /**
     * Same filters as the list page, returned as one plain collection
     * instead of a paginator, since a report is a single document.
     */
    private function filteredAssetsForExport(Request $request)
    {
        return $this->assetQuery($request)
            ->with(['user', 'department'])
            ->orderBy('asset_tag')
            ->get();
    }

    /**
     * A short, human-readable line describing which filters shaped this
     * report, shown under the title on both the PDF and the Excel export.
     */
    private function exportFilterSummary(Request $request): string
    {
        $parts = [];

        if ($search = trim((string) $request->query('search', ''))) {
            $parts[] = "Search: \"{$search}\"";
        }

        if ($request->filled('department_id')) {
            $department = Department::find($request->integer('department_id'));
            if ($department) {
                $parts[] = 'Department: '.$department->name;
            }
        }

        if (array_key_exists((string) $request->query('location'), Asset::locations())) {
            $parts[] = 'Branch: '.Asset::locations()[$request->query('location')];
        }

        if (array_key_exists((string) $request->query('type'), Asset::TYPES)) {
            $parts[] = 'Type: '.Asset::TYPES[$request->query('type')];
        }

        if (in_array($request->query('assignment'), ['assigned', 'unassigned'], true)) {
            $parts[] = 'Assignment: '.ucfirst($request->query('assignment'));
        }

        if (array_key_exists((string) $request->query('status'), Asset::STATUSES)) {
            $parts[] = 'Status: '.Asset::STATUSES[$request->query('status')];
        }

        return $parts ? implode(' · ', $parts) : 'All assets';
    }

    /**
     * Download the current inventory report as a professionally formatted
     * PDF — letterhead, applied filters, and a clean table.
     */
    public function exportPdf(Request $request)
    {
        $assets = $this->filteredAssetsForExport($request);

        $pdf = Pdf::loadView('admin.assets.export-pdf', [
            'assets' => $assets,
            'summary' => $this->summarize($this->assetQuery($request)),
            'filterSummary' => $this->exportFilterSummary($request),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('assets-report-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Download the current inventory report as a polished .xlsx workbook —
     * bold header row, brand color fill, borders, autosized columns, and a
     * frozen header row.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $assets = $this->filteredAssetsForExport($request);
        $summary = $this->summarize($this->assetQuery($request));
        $filterSummary = $this->exportFilterSummary($request);

        $spreadsheet = new Spreadsheet();

        // ---------- Sheet 1: Summary ----------
        $overview = $spreadsheet->getActiveSheet();
        $overview->setTitle('Summary');

        $overview->setCellValue('A1', 'Crest IT Service Desk — Asset Inventory Summary');
        $overview->mergeCells('A1:E1');
        $overview->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('123F24');

        $overview->setCellValue('A2', 'Generated '.now()->format('F j, Y g:i A').' · '.$filterSummary);
        $overview->mergeCells('A2:E2');
        $overview->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6B7280');

        $overview->setCellValue('A4', 'Overview');
        $overview->setCellValue('B4', 'Count');
        $this->styleHeader($overview, 'A4:B4');

        $overviewRows = [
            ['Total assets', $summary['total']],
            ['Assigned', $summary['assigned']],
            ['Unassigned', $summary['unassigned']],
            ['In repair', $summary['in_repair']],
            ['Retired', $summary['retired']],
        ];
        $r = 5;
        foreach ($overviewRows as [$label, $value]) {
            $overview->setCellValue("A{$r}", $label);
            $overview->setCellValue("B{$r}", $value);
            $r++;
        }
        $overview->getStyle('A5:B5')->getFont()->setBold(true);
        $overview->getStyle('A7:B7')->getFont()->getColor()->setRGB('B45309'); // unassigned stands out
        $overview->getStyle('A7:B7')->getFont()->setBold(true);
        $overview->getStyle('B5:B9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $overview->getStyle('A4:B9')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

        $typeHeaderRow = 12;
        $overview->setCellValue("A{$typeHeaderRow}", 'Device type');
        $overview->setCellValue("B{$typeHeaderRow}", 'Total');
        $overview->setCellValue("C{$typeHeaderRow}", 'Assigned');
        $overview->setCellValue("D{$typeHeaderRow}", 'Unassigned');
        $overview->setCellValue("E{$typeHeaderRow}", 'In repair');
        $this->styleHeader($overview, "A{$typeHeaderRow}:E{$typeHeaderRow}");

        $r = $typeHeaderRow + 1;
        foreach ($summary['byType'] as $row) {
            $overview->setCellValue("A{$r}", $row['label']);
            $overview->setCellValue("B{$r}", $row['total']);
            $overview->setCellValue("C{$r}", $row['assigned']);
            $overview->setCellValue("D{$r}", $row['unassigned']);
            $overview->setCellValue("E{$r}", $row['in_repair']);
            if ($row['total'] === 0) {
                $overview->getStyle("A{$r}:E{$r}")->getFont()->getColor()->setRGB('9CA3AF');
            }
            $r++;
        }

        $overview->setCellValue("A{$r}", 'Total');
        $overview->setCellValue("B{$r}", $summary['total']);
        $overview->setCellValue("C{$r}", $summary['assigned']);
        $overview->setCellValue("D{$r}", $summary['unassigned']);
        $overview->setCellValue("E{$r}", $summary['in_repair']);
        $overview->getStyle("A{$r}:E{$r}")->getFont()->setBold(true);
        $overview->getStyle("A{$r}:E{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F3EC');

        $overview->getStyle("B{$typeHeaderRow}:E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $overview->getStyle("A{$typeHeaderRow}:E{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

        $overview->getColumnDimension('A')->setWidth(24);
        foreach (['B', 'C', 'D', 'E'] as $col) {
            $overview->getColumnDimension($col)->setWidth(14);
        }

        // ---------- Sheet 2: full asset list ----------
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Assets');

        $sheet->setCellValue('A1', 'Crest IT Service Desk — Asset Inventory Report');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('123F24');

        $sheet->setCellValue('A2', 'Generated '.now()->format('F j, Y g:i A').' · '.$filterSummary.' · '.$summary['total'].' asset'.($summary['total'] === 1 ? '' : 's'));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('6B7280');

        $headers = ['Asset Tag', 'Device Name', 'Type', 'Company', 'Location', 'Department', 'Assigned To', 'Serial Number', 'Status', 'Assigned Since'];
        $sheet->fromArray($headers, null, 'A4');
        $this->styleHeader($sheet, 'A4:J4');

        $row = 5;
        foreach ($assets as $asset) {
            $sheet->setCellValue("A{$row}", $asset->asset_tag);
            $sheet->setCellValue("B{$row}", $asset->device_name);
            $sheet->setCellValue("C{$row}", Asset::TYPES[$asset->type] ?? $asset->type);
            $sheet->setCellValue("D{$row}", Asset::COMPANIES[$asset->company] ?? $asset->company);
            $sheet->setCellValue("E{$row}", Asset::locations()[$asset->location] ?? $asset->location);
            $sheet->setCellValue("F{$row}", $asset->department->name ?? '—');
            $sheet->setCellValue("G{$row}", $asset->user->name ?? 'Unassigned');
            $sheet->setCellValue("H{$row}", $asset->serial_number ?? '—');
            $sheet->setCellValue("I{$row}", Asset::STATUSES[$asset->status] ?? ucfirst($asset->status));
            $sheet->setCellValue("J{$row}", $asset->assigned_date?->format('M j, Y') ?? '—');

            if (! $asset->user_id) {
                $sheet->getStyle("G{$row}")->getFont()->setItalic(true)->getColor()->setRGB('B45309');
            }
            $row++;
        }

        $lastRow = $row - 1;

        if ($lastRow >= 5) {
            $sheet->getStyle("A4:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

            for ($i = 5; $i <= $lastRow; $i++) {
                if ($i % 2 === 0) {
                    $sheet->getStyle("A{$i}:J{$i}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
                }
            }

            $sheet->setAutoFilter("A4:J{$lastRow}");
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A5');

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'assets-report-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Brand-green header row used across the workbook's tables.
     */
    private function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A6B3C');
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }
}
