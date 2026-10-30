<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Announcement extends Model
{
    /**
     * Category => label, colours and an icon (Heroicons outline path).
     * Used by the pop-up, the feed, the dashboard banner and the admin form,
     * so a category always looks the same everywhere.
     */
    public const CATEGORIES = [
        'feature' => [
            'label' => 'New feature', 'fg' => '#15803d', 'bg' => '#ecf7f0',
            'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z',
        ],
        'update' => [
            'label' => 'System update', 'fg' => '#1d4ed8', 'bg' => '#eff6ff',
            'icon' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
        ],
        'maintenance' => [
            'label' => 'Maintenance', 'fg' => '#b45309', 'bg' => '#fffbeb',
            'icon' => 'M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z',
        ],
        'reminder' => [
            'label' => 'Reminder', 'fg' => '#6d28d9', 'bg' => '#f5f3ff',
            'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
        ],
        'policy' => [
            'label' => 'Policy', 'fg' => '#475569', 'bg' => '#f1f5f9',
            'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        ],
        'important' => [
            'label' => 'Important', 'fg' => '#b91c1c', 'bg' => '#fef2f2',
            'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        ],
    ];

    public const AUDIENCES = [
        'all' => 'Everyone',
        'staff' => 'Staff only',
        'it_support' => 'IT Support only',
        'admin' => 'Administrators only',
    ];

    /** How long an un-pinned announcement keeps popping up / counting as "new". */
    public const FRESH_DAYS = 30;

    /** Small heading above the title in the pop-up. */
    public const POPUP_EYEBROWS = [
        'important' => 'Important notice',
        'maintenance' => 'Heads up',
        'reminder' => 'Friendly reminder',
        'policy' => 'Policy update',
    ];

    protected $fillable = [
        'title', 'body', 'category', 'audience',
        'is_popup', 'is_pinned', 'is_published',
        'starts_at', 'ends_at', 'created_by',
    ];

    protected $casts = [
        'is_popup' => 'boolean',
        'is_pinned' => 'boolean',
        'is_published' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function readers()
    {
        return $this->belongsToMany(User::class, 'announcement_reads')->withPivot('read_at');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    /** Published, already started and not yet expired. */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeForUser(Builder $q, User $user): Builder
    {
        return $q->whereIn('audience', ['all', $user->role]);
    }

    public function scopeUnreadBy(Builder $q, User $user): Builder
    {
        return $q->whereDoesntHave('readers', fn ($r) => $r->where('users.id', $user->id));
    }

    /** Filter by the same four states status() reports. */
    public function scopeWithStatus(Builder $q, ?string $status): Builder
    {
        return match ($status) {
            'live' => $q->live(),
            'scheduled' => $q->where('is_published', true)->where('starts_at', '>', now()),
            'draft' => $q->where('is_published', false),
            'expired' => $q->where('is_published', true)->whereNotNull('ends_at')->where('ends_at', '<', now()),
            default => $q,
        };
    }

    /** Pinned ones always count; others only while they are fresh. */
    public function scopeRecentOrPinned(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->where('is_pinned', true)
            ->orWhere('created_at', '>=', now()->subDays(self::FRESH_DAYS)));
    }

    // ── Lookups used by the layout (never allowed to break a page) ───

    /** Unread pop-up announcements to show right after the person opens the system. */
    public static function popupsFor(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        try {
            return static::live()->forUser($user)->unreadBy($user)->recentOrPinned()
                ->where('is_popup', true)
                ->with('creator:id,name')
                ->orderByDesc('is_pinned')->orderByDesc('created_at')
                ->limit(5)->get();
        } catch (\Throwable $e) {
            return collect(); // table not migrated yet
        }
    }

    /** What the dashboard banner shows: pinned first, then the latest few. */
    public static function bannerFor(?User $user, int $limit = 2): Collection
    {
        if (! $user) {
            return collect();
        }

        try {
            return static::live()->forUser($user)->recentOrPinned()
                ->orderByDesc('is_pinned')->orderByDesc('created_at')
                ->limit($limit)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function unreadCountFor(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        try {
            return static::live()->forUser($user)->unreadBy($user)->recentOrPinned()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * A cheap fingerprint of "what this person can see right now", used by the
     * live poll. It changes when an announcement is published, edited, pinned,
     * expired, scheduled-in, deleted — or when this person's unread set changes.
     * The browser sends back the last one it saw; same fingerprint = nothing to do.
     *
     * @return array{sig: string, unread: int}
     */
    public static function liveState(?User $user): array
    {
        if (! $user) {
            return ['sig' => '', 'unread' => 0];
        }

        try {
            $live = static::live()->forUser($user)
                ->get(['id', 'is_pinned', 'created_at', 'updated_at']);

            $readIds = $live->isEmpty() ? [] : DB::table('announcement_reads')
                ->where('user_id', $user->id)
                ->whereIn('announcement_id', $live->pluck('id'))
                ->pluck('announcement_id')
                ->all();

            // Same rule as unreadCountFor(): unread, and pinned or still fresh.
            $fresh = now()->subDays(self::FRESH_DAYS);
            $unreadIds = $live
                ->filter(fn ($a) => ! in_array($a->id, $readIds) && ($a->is_pinned || $a->created_at >= $fresh))
                ->pluck('id')
                ->sort()
                ->values();

            $items = $live->sortBy('id')
                ->map(fn ($a) => $a->id.'.'.(int) $a->is_pinned.'.'.$a->updated_at?->timestamp)
                ->implode(',');

            return [
                'sig' => md5($items.'#'.$unreadIds->implode(',')),
                'unread' => $unreadIds->count(),
            ];
        } catch (\Throwable $e) {
            return ['sig' => '', 'unread' => 0]; // table not migrated yet
        }
    }

    /** What the pop-up needs to draw this announcement (first load and live pushes alike). */
    public function popupPayload(): array
    {
        $c = $this->category();

        return [
            'id' => $this->id,
            'v' => $this->updated_at?->timestamp ?? 0, // changes on edit / "show it again"
            'title' => $this->title,
            'html' => $this->bodyHtml(),
            'label' => $c['label'],
            'eyebrow' => self::POPUP_EYEBROWS[$this->category] ?? "What's new",
            'fg' => $c['fg'],
            'bg' => $c['bg'],
            'icon' => $c['icon'],
            'date' => $this->created_at->format('M j, Y'),
            'by' => $this->creator?->name,
            'pinned' => (bool) $this->is_pinned,
        ];
    }

    // ── Presentation helpers ─────────────────────────────────────────

    public function category(): array
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['update'];
    }

    public function audienceLabel(): string
    {
        return self::AUDIENCES[$this->audience] ?? 'Everyone';
    }

    /** draft | scheduled | live | expired */
    public function status(): string
    {
        if (! $this->is_published) {
            return 'draft';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        return 'live';
    }

    public function excerpt(int $chars = 140): string
    {
        $plain = preg_replace('/\s+/u', ' ', str_replace(['**', "\n- "], ['', ' '], (string) $this->body));

        return Str::limit(trim($plain), $chars);
    }

    /**
     * The message as safe HTML. Everything is escaped first; only a few simple
     * conveniences are turned back into markup: blank line = new paragraph,
     * lines starting with "- " = bullet list, **bold**, and plain web links.
     */
    public function bodyHtml(): string
    {
        $html = '';
        $list = [];
        $para = [];

        $flushList = function () use (&$html, &$list) {
            if ($list) {
                $html .= '<ul>'.implode('', array_map(fn ($i) => '<li>'.$i.'</li>', $list)).'</ul>';
                $list = [];
            }
        };
        $flushPara = function () use (&$html, &$para) {
            if ($para) {
                $html .= '<p>'.implode('<br>', $para).'</p>';
                $para = [];
            }
        };

        foreach (preg_split('/\r\n|\r|\n/', trim((string) $this->body)) as $line) {
            $line = trim($line);

            if ($line === '') {
                $flushList();
                $flushPara();
                continue;
            }
            if (preg_match('/^[-•]\s+(.+)$/u', $line, $m)) {
                $flushPara();
                $list[] = self::inline($m[1]);
                continue;
            }
            $flushList();
            $para[] = self::inline($line);
        }
        $flushList();
        $flushPara();

        return $html;
    }

    private static function inline(string $text): string
    {
        $text = e($text);
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text);

        return preg_replace_callback('~https?://[^\s<]+~i', function ($m) {
            $url = $m[0];
            $trail = '';
            while ($url !== '' && preg_match('/[.,;:!?)]$/', $url)) {
                $trail = substr($url, -1).$trail;
                $url = substr($url, 0, -1);
            }

            return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$url.'</a>'.$trail;
        }, $text);
    }
}
