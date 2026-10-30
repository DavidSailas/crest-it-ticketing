<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['live', 'scheduled', 'draft', 'expired'], true)
            ? $request->query('status')
            : null;

        $announcements = Announcement::with('creator:id,name')
            ->withCount('readers')
            ->withStatus($status)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => Announcement::count(),
            'live' => Announcement::live()->count(),
            'scheduled' => Announcement::withStatus('scheduled')->count(),
            'draft' => Announcement::withStatus('draft')->count(),
            'expired' => Announcement::withStatus('expired')->count(),
        ];

        // How many active accounts each audience reaches — for "seen by 12 of 40".
        $active = User::whereNull('suspended_at');
        $reach = [
            'all' => (clone $active)->count(),
            'staff' => (clone $active)->where('role', 'staff')->count(),
            'it_support' => (clone $active)->where('role', 'it_support')->count(),
            'admin' => (clone $active)->where('role', 'admin')->count(),
        ];

        return view('admin.announcements.index', compact('announcements', 'reach', 'counts', 'status'));
    }

    public function create()
    {
        $announcement = new Announcement([
            'category' => 'feature',
            'audience' => 'all',
            'is_popup' => true,
            'is_pinned' => false,
            'is_published' => true,
        ]);

        return view('admin.announcements.form', compact('announcement'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['is_published'] = $request->input('action') !== 'draft';
        $data['created_by'] = $request->user()->id;

        $announcement = Announcement::create($data);

        return redirect()->route('admin.announcements.index')->with(
            'status',
            $announcement->is_published
                ? ($announcement->status() === 'scheduled'
                    ? "\"{$announcement->title}\" is scheduled and will appear on {$announcement->starts_at->format('M j, Y g:i A')}."
                    : "\"{$announcement->title}\" has been published.")
                : "\"{$announcement->title}\" was saved as a draft."
        );
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.form', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $this->validated($request);
        $data['is_published'] = $request->input('action') !== 'draft';

        $announcement->update($data);

        // "Show it again" — forget who already saw it so the pop-up returns.
        if ($request->boolean('resend')) {
            DB::table('announcement_reads')->where('announcement_id', $announcement->id)->delete();
            $announcement->touch(); // new version, so open tabs pop it up again live
        }

        return redirect()->route('admin.announcements.index')
            ->with('status', "\"{$announcement->title}\" was updated.");
    }

    /** Publish a draft / pull a live one back to draft. */
    public function toggle(Announcement $announcement)
    {
        $announcement->update(['is_published' => ! $announcement->is_published]);

        return back()->with('status', $announcement->is_published
            ? "\"{$announcement->title}\" is now published."
            : "\"{$announcement->title}\" was moved back to drafts.");
    }

    public function destroy(Announcement $announcement)
    {
        $title = $announcement->title;
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('status', "\"{$title}\" was deleted.");
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:5000',
            'category' => ['required', Rule::in(array_keys(Announcement::CATEGORIES))],
            'audience' => ['required', Rule::in(array_keys(Announcement::AUDIENCES))],
            'starts_at' => 'nullable|date',
            'ends_at' => ['nullable', 'date', $request->filled('starts_at') ? 'after:starts_at' : 'after:now'],
        ], [
            'ends_at.after' => 'The end date must be after the start date (and in the future).',
        ]);

        return [
            'title' => trim($request->input('title')),
            'body' => trim($request->input('body')),
            'category' => $request->input('category'),
            'audience' => $request->input('audience'),
            'is_popup' => $request->boolean('is_popup'),
            'is_pinned' => $request->boolean('is_pinned'),
            'starts_at' => $request->filled('starts_at') ? $request->input('starts_at') : null,
            'ends_at' => $request->filled('ends_at') ? $request->input('ends_at') : null,
        ];
    }
}
