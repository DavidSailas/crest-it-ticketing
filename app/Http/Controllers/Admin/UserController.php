<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Position;
use App\Models\Ticket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Staff / IT Support / VIP are shown as separate tabs rather than one
     * long mixed list, since an admin usually only cares about one group
     * at a time.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'staff');

        $query = User::query();

        if ($tab === 'it_support') {
            $query->where('role', 'it_support');
        } elseif ($tab === 'vip') {
            $query->where('is_vip', true);
        } else {
            $tab = 'staff';
            $query->where('role', 'staff');
        }

        $users = $query->with(['department', 'position'])->latest()->paginate(10)->withQueryString();

        $counts = [
            'staff' => User::where('role', 'staff')->count(),
            'it_support' => User::where('role', 'it_support')->count(),
            'vip' => User::where('is_vip', true)->count(),
        ];

        return view('admin.users.index', compact('users', 'tab', 'counts'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        return view('admin.users.create', compact('departments', 'positions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => ['required', 'exists:departments,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'role' => ['required', 'in:staff,it_support,admin'],
            'location' => ['required', 'in:'.implode(',', array_keys(Asset::locations()))],
            'is_vip' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'That email is already registered.',
            'username.unique' => 'That username is already taken.',
            'username.alpha_dash' => 'Username can only contain letters, numbers, dashes, and underscores.',
            'password.min' => 'Password must be at least 8 characters.',
            'department_id.required' => 'Choose which department this person belongs to.',
            'position_id.required' => 'Choose this person\'s position.',
            'location.required' => 'Choose which branch this person works out of.',
        ]);

        $user = new User();
        $user->forceFill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'],
            'position_id' => $validated['position_id'],
            'role' => $validated['role'],
            'location' => $validated['location'],
            'is_vip' => $request->boolean('is_vip'),
        ])->save();

        return redirect()->route('admin.users.index', ['tab' => $validated['role'] === 'it_support' ? 'it_support' : 'staff'])
            ->with('status', "Created account for {$user->name}.");
    }

    public function edit(User $user)
    {
        $assets = Asset::where('user_id', $user->id)->latest()->get();

        // Only departments with a code can be picked, since the code is
        // what makes up the middle segment of the asset tag.
        $assetDepartments = Department::whereNotNull('code')->orderBy('name')->get();

        // Full department list (no code required) — used for the person's
        // own department assignment, separate from the asset-tagging list.
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'assets', 'assetDepartments', 'departments', 'positions'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username,'.$user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'department_id' => ['required', 'exists:departments,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'role' => ['required', 'in:staff,it_support,admin'],
            'location' => ['required', 'in:'.implode(',', array_keys(Asset::locations()))],
            'is_vip' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'That email is already registered.',
            'username.unique' => 'That username is already taken.',
            'username.alpha_dash' => 'Username can only contain letters, numbers, dashes, and underscores.',
            'password.min' => 'Password must be at least 8 characters.',
            'department_id.required' => 'Choose which department this person belongs to.',
            'position_id.required' => 'Choose this person\'s position.',
            'location.required' => 'Choose which branch this person works out of.',
        ]);

        $fill = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
            'position_id' => $validated['position_id'],
            'role' => $validated['role'],
            'location' => $validated['location'],
            'is_vip' => $request->boolean('is_vip'),
        ];

        if (!empty($validated['password'])) {
            $fill['password'] = Hash::make($validated['password']);
        }

        $user->forceFill($fill)->save();

        return redirect()->route('admin.users.index', ['tab' => $user->role === 'it_support' ? 'it_support' : 'staff'])
            ->with('status', "Updated {$user->name}.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', "You can't delete your own account while logged in.");
        }

        $name = $user->name;
        $user->delete();

        return back()->with('status', "Deleted {$name}.");
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:staff,it_support,admin']);

        $user->update(['role' => $request->role]);

        return back()->with('status', "Updated {$user->name}'s role to {$request->role}.");
    }

    public function updateVip(Request $request, User $user)
    {
        $user->forceFill(['is_vip' => ! $user->is_vip])->save();

        $status = $user->is_vip
            ? "{$user->name} is now marked as VIP — their tickets will auto-escalate to Critical."
            : "Removed VIP status from {$user->name}.";

        return back()->with('status', $status);
    }

    /**
     * Read-only staff directory for IT Support. Same underlying data as
     * Manage Users, but with no edit, delete, role, or VIP controls — IT
     * Support can look someone up while working a ticket, nothing more.
     */
    public function directory(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        // Only staff accounts show here — IT Support and Admin accounts
        // are excluded from this directory.
        $query = User::query()->where('role', 'staff');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        // Asset counts per user, fetched separately so this read-only view
        // doesn't depend on a relationship being defined on the User model.
        $assetCounts = Asset::whereIn('user_id', $users->pluck('id'))
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        return view('admin.users.directory', compact('users', 'search', 'assetCounts'));
    }

    /**
     * Read-only detail view for IT Support: everything they need while
     * working a ticket for this person — account info, every asset issued
     * to them (with tags), and their recent ticket history.
     */
    public function directoryShow(User $user)
    {
        abort_unless($user->role === 'staff', 404);

        $assets = Asset::with('department')
            ->where('user_id', $user->id)
            ->orderBy('type')
            ->orderBy('sequence')
            ->get();

        $tickets = Ticket::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $ticketCounts = [
            'total' => Ticket::where('user_id', $user->id)->count(),
            'open' => Ticket::where('user_id', $user->id)->where('status', 'open')->count(),
            'in_progress' => Ticket::where('user_id', $user->id)->where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('user_id', $user->id)->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('admin.users.directory-show', compact('user', 'assets', 'tickets', 'ticketCounts'));
    }

    /**
     * Same tab filter used by index(), reused by every export format so
     * "Export" always reflects whichever tab (Staff / IT Support / VIP)
     * the admin currently has open.
     */
    private function filteredUsers(Request $request)
    {
        $tab = $request->query('tab', 'all');

        $query = User::query();

        if ($tab === 'it_support') {
            $query->where('role', 'it_support');
        } elseif ($tab === 'vip') {
            $query->where('is_vip', true);
        } elseif ($tab === 'staff') {
            $query->where('role', 'staff');
        }
        // 'all' (default for exports) applies no filter.

        return $query->with(['department', 'position'])->orderBy('name')->get();
    }

    /**
     * Download users as a professionally formatted PDF report — letterhead,
     * generated timestamp, and a clean table. Respects the current tab
     * filter via ?tab=staff|it_support|vip (omit for everyone).
     */
    public function exportPdf(Request $request)
    {
        $users = $this->filteredUsers($request);
        $tab = $request->query('tab', 'all');

        $pdf = Pdf::loadView('admin.users.export-pdf', [
            'users' => $users,
            'tab' => $tab,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $filename = 'users-'.($tab === 'all' ? 'all' : $tab).'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Download users as a polished .xlsx workbook — bold header row, brand
     * color fill, borders, autosized columns, and a frozen header so it
     * reads like a real report rather than a raw data dump.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $users = $this->filteredUsers($request);
        $tab = $request->query('tab', 'all');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        // Title row
        $sheet->setCellValue('A1', 'Crest IT Service Desk — User Directory');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('123F24');

        $sheet->setCellValue('A2', 'Generated '.now()->format('F j, Y g:i A').' · '.($tab === 'all' ? 'All users' : ucfirst(str_replace('_', ' ', $tab))));
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('6B7280');

        // Header row
        $headers = ['Name', 'Username', 'Email', 'Department', 'Position', 'Role', 'Branch', 'VIP'];
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:H4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A6B3C');
        $sheet->getStyle('A4:H4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Data rows
        $row = 5;
        foreach ($users as $user) {
            $sheet->setCellValue("A{$row}", $user->name);
            $sheet->setCellValue("B{$row}", $user->username ?? '—');
            $sheet->setCellValue("C{$row}", $user->email);
            $sheet->setCellValue("D{$row}", $user->department->name ?? '—');
            $sheet->setCellValue("E{$row}", $user->position->name ?? '—');
            $sheet->setCellValue("F{$row}", ucfirst(str_replace('_', ' ', $user->role)));
            $sheet->setCellValue("G{$row}", $user->branch_name ?? '—');
            $sheet->setCellValue("H{$row}", $user->is_vip ? 'Yes' : 'No');
            $row++;
        }

        $lastRow = $row - 1;

        // Borders around the whole table
        $sheet->getStyle("A4:H{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

        // Zebra striping for readability
        for ($i = 5; $i <= $lastRow; $i++) {
            if ($i % 2 === 0) {
                $sheet->getStyle("A{$i}:H{$i}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
            }
        }

        // Autosize columns
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze header row so it stays visible while scrolling
        $sheet->freezePane('A5');

        $filename = 'users-'.($tab === 'all' ? 'all' : $tab).'-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Download every user as a CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        $tab = $request->query('tab', 'all');
        $filename = 'users-'.($tab === 'all' ? 'all' : $tab).'-'.now()->format('Y-m-d').'.csv';

        $callback = function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Username', 'Email', 'Department', 'Position', 'Role', 'Branch', 'VIP']);

            foreach ($this->filteredUsers($request) as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->username ?? '',
                    $user->email,
                    $user->department->name ?? '',
                    $user->position->name ?? '',
                    $user->role,
                    $user->branch_name ?? '',
                    $user->is_vip ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Bulk-create users from an uploaded CSV or Excel (.xlsx) file. Expected
     * columns (with header row): Name, Email, Role, VIP (optional). New
     * accounts get a random temporary password — share it with each person,
     * or have them use "Forgot password" to set their own.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ], [
            'file.required' => 'Please choose a file to import.',
            'file.mimes' => 'The file must be a CSV or Excel (.xlsx) file.',
        ]);

        $path = $request->file('file')->getRealPath();
        $extension = strtolower($request->file('file')->getClientOriginalExtension());

        $rows = in_array($extension, ['xlsx', 'xls'])
            ? $this->readExcelRows($path)
            : $this->readCsvRows($path);

        if (empty($rows)) {
            return back()->with('error', 'The file appears to be empty or missing a header row.');
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($rows));

        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            // Pad/truncate so array_combine never fails on a short row.
            $row = array_pad(array_slice($row, 0, count($header)), count($header), '');
            $row = array_combine($header, $row);

            // Accept either separate "First Name"/"Last Name" columns
            // (matching our export format) or a single legacy "Name" column
            // split on the first space, so older files still import fine.
            $firstName = trim((string) ($row['first name'] ?? ''));
            $lastName = trim((string) ($row['last name'] ?? ''));
            if ($firstName === '' && $lastName === '') {
                $legacyName = trim((string) ($row['name'] ?? ''));
                if ($legacyName !== '') {
                    $parts = explode(' ', $legacyName, 2);
                    $firstName = $parts[0];
                    $lastName = $parts[1] ?? '';
                }
            }

            $username = trim((string) ($row['username'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $role = strtolower(trim((string) ($row['role'] ?? 'staff')));
            $isVip = in_array(strtolower(trim((string) ($row['vip'] ?? 'no'))), ['yes', 'true', '1']);

            // Accept either the short code (CEB) or full name (Cebu) in the
            // Branch column, matching whatever format the sheet was exported
            // with or typed in by hand.
            $branchInput = trim((string) ($row['branch'] ?? ''));
            $location = null;
            if ($branchInput !== '') {
                $upper = strtoupper($branchInput);
                if (array_key_exists($upper, Asset::locations())) {
                    $location = $upper;
                } else {
                    $match = array_search($branchInput, Asset::locations(), true)
                        ?: array_search(ucfirst(strtolower($branchInput)), Asset::locations(), true);
                    $location = $match ?: null;
                }
            }

            // Match the Department column by name, case-insensitively.
            $departmentInput = trim((string) ($row['department'] ?? ''));
            $departmentId = $departmentInput !== ''
                ? Department::whereRaw('LOWER(name) = ?', [strtolower($departmentInput)])->value('id')
                : null;

            // Match the Position column by name, case-insensitively.
            $positionInput = trim((string) ($row['position'] ?? ''));
            $positionId = $positionInput !== ''
                ? Position::whereRaw('LOWER(name) = ?', [strtolower($positionInput)])->value('id')
                : null;

            if (
                $firstName === '' || $lastName === '' || $email === ''
                || $username === '' || $departmentId === null || $positionId === null
                || User::where('email', $email)->exists()
                || User::where('username', $username)->exists()
            ) {
                $skipped++;
                continue;
            }

            if (!in_array($role, ['staff', 'it_support', 'admin'])) {
                $role = 'staff';
            }

            $user = new User();
            $user->forceFill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make(str()->random(12)),
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'role' => $role,
                'location' => $location,
                'is_vip' => $isVip,
            ])->save();

            $created++;
        }

        return back()->with('status', "Imported {$created} user(s)." . ($skipped > 0 ? " Skipped {$skipped} (missing/duplicate data — check First Name, Last Name, Username, Email, Department, and Position for each skipped row)." : ''));
    }

    /**
     * Read a CSV file into a plain array of rows (first row is the header,
     * left untouched here — normalized by the caller).
     */
    private function readCsvRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Read an Excel (.xlsx/.xls) file's first sheet into the same plain
     * array-of-rows shape as readCsvRows(), so both formats can be
     * processed identically afterward.
     */
    private function readExcelRows(string $path): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }
}
