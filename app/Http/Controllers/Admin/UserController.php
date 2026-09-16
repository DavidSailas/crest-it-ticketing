<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Department;
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

        $users = $query->latest()->paginate(10)->withQueryString();

        $counts = [
            'staff' => User::where('role', 'staff')->count(),
            'it_support' => User::where('role', 'it_support')->count(),
            'vip' => User::where('is_vip', true)->count(),
        ];

        return view('admin.users.index', compact('users', 'tab', 'counts'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:staff,it_support,admin'],
            'is_vip' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'That email is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_vip' => $request->boolean('is_vip'),
        ])->save();

        return redirect()->route('admin.users.index', ['tab' => $validated['role'] === 'it_support' ? 'it_support' : 'staff'])
            ->with('status', "Created account for {$validated['name']}.");
    }

    public function edit(User $user)
    {
        $assets = Asset::where('user_id', $user->id)->latest()->get();

        // Only departments with a code can be picked, since the code is
        // what makes up the middle segment of the asset tag.
        $assetDepartments = Department::whereNotNull('code')->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'assets', 'assetDepartments'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:staff,it_support,admin'],
            'is_vip' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'That email is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $fill = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
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

        return $query->orderBy('name')->get();
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
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('123F24');

        $sheet->setCellValue('A2', 'Generated '.now()->format('F j, Y g:i A').' · '.($tab === 'all' ? 'All users' : ucfirst(str_replace('_', ' ', $tab))));
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('6B7280');

        // Header row
        $headers = ['Name', 'Email', 'Role', 'VIP'];
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:D4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:D4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A6B3C');
        $sheet->getStyle('A4:D4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Data rows
        $row = 5;
        foreach ($users as $user) {
            $sheet->setCellValue("A{$row}", $user->name);
            $sheet->setCellValue("B{$row}", $user->email);
            $sheet->setCellValue("C{$row}", ucfirst(str_replace('_', ' ', $user->role)));
            $sheet->setCellValue("D{$row}", $user->is_vip ? 'Yes' : 'No');
            $row++;
        }

        $lastRow = $row - 1;

        // Borders around the whole table
        $sheet->getStyle("A4:D{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

        // Zebra striping for readability
        for ($i = 5; $i <= $lastRow; $i++) {
            if ($i % 2 === 0) {
                $sheet->getStyle("A{$i}:D{$i}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
            }
        }

        // Autosize columns
        foreach (['A', 'B', 'C', 'D'] as $col) {
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
            fputcsv($handle, ['Name', 'Email', 'Role', 'VIP']);

            foreach ($this->filteredUsers($request) as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $user->role,
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

            $name = trim((string) ($row['name'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $role = strtolower(trim((string) ($row['role'] ?? 'staff')));
            $isVip = in_array(strtolower(trim((string) ($row['vip'] ?? 'no'))), ['yes', 'true', '1']);

            if ($name === '' || $email === '' || User::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            if (!in_array($role, ['staff', 'it_support', 'admin'])) {
                $role = 'staff';
            }

            $user = new User();
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(str()->random(12)),
                'role' => $role,
                'is_vip' => $isVip,
            ])->save();

            $created++;
        }

        return back()->with('status', "Imported {$created} user(s)." . ($skipped > 0 ? " Skipped {$skipped} (missing data or duplicate email)." : ''));
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
