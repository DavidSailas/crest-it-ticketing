<?php

namespace App\Models;

use App\Support\SupplyImage;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * An IT supply item — a consumable counted by quantity (ink, batteries, mice,
 * keyboards, USB drives…). Individually tagged devices belong in Asset.
 *
 * The quantity is never edited directly. Every change goes through
 * applyMovement(), which locks the row, writes a SupplyMovement and keeps the
 * running balance honest.
 */
class Supply extends Model
{
    public const CATEGORIES = [
        'ink_toner' => 'Ink & Toner',
        'batteries' => 'Batteries',
        'mouse' => 'Mouse',
        'keyboard' => 'Keyboard',
        'usb_storage' => 'USB & Storage',
        'cables' => 'Cables & Adapters',
        'telephone' => 'Telephone & Handsets',   // desk phones, spare handsets, handset cords, phone line cords
        'other' => 'Other',
    ];

    public const UNITS = ['pcs', 'box', 'pack', 'set', 'pair', 'bottle', 'cartridge', 'roll'];

    /**
     * Four levels, each with its own colour and wording:
     *   ok       comfortably stocked
     *   low      at/below the reorder level   — plan a reorder
     *   critical at/below the critical level  — reorder now
     *   out      nothing left
     */
    public const STOCK_STATUSES = [
        'ok' => 'In Stock',
        'low' => 'Low Stock',
        'critical' => 'Critical',
        'out' => 'Out of Stock',
    ];

    public const STOCK_HINTS = [
        'ok' => 'Comfortably stocked',
        'low' => 'Running low — plan a reorder',
        'critical' => 'Critically low — reorder now',
        'out' => 'Nothing left in stock',
    ];

    /**
     * The company's own printers. These are always offered first for ink & toner.
     * Another brand is still allowed (a printer that isn't on the list yet, or a third-party
     * cartridge) — it just has to say which printer it is for. See SupplyController::detailRules().
     */
    public const PRINTER_BRANDS = ['Brother', 'Epson'];

    /**
     * Starter suggestions for the Brand picker. Every brand already in stock is added to these,
     * and anything new is typed once under "Other brand…" and is a one-click choice from then on.
     */
    public const COMMON_BRANDS = [
        'ink_toner' => self::PRINTER_BRANDS,
        'batteries' => ['Duracell', 'Energizer'],
        'mouse' => ['Logitech', 'A4Tech'],
        'keyboard' => ['Logitech', 'A4Tech'],
        'usb_storage' => ['Kingston', 'SanDisk'],
        'cables' => ['Ugreen', 'Belkin'],
        'telephone' => ['Panasonic', 'Yealink', 'Grandstream'],
    ];

    /** What each kind of stock movement is called on screen. */
    public const MOVEMENT_LABELS = [
        'initial' => 'Opening stock',
        'in' => 'Stock in',
        'out' => 'Stock out',
        'count' => 'Count correction',
    ];

    protected $fillable = [
        'name',
        'category',
        'brand',
        'model_no',
        'unit',
        'quantity',
        'reorder_level',
        'critical_level',
        'location',
        'notes',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reorder_level' => 'integer',
            'critical_level' => 'integer',
        ];
    }

    public function movements()
    {
        return $this->hasMany(SupplyMovement::class);
    }

    public function requests()
    {
        return $this->hasMany(SupplyRequest::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'location', 'code');
    }

    /* ---------- Display helpers ---------- */

    /** The full-size photo, or null when the item has none. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk(SupplyImage::DISK)->url($this->image_path) : null;
    }

    /** The small version for lists and dialogs, or null when the item has no photo. */
    public function getThumbUrlAttribute(): ?string
    {
        return $this->image_path
            ? Storage::disk(SupplyImage::DISK)->url(SupplyImage::thumbPath($this->image_path))
            : null;
    }


    /** Stable, human-friendly item code, e.g. SUP-0042. */
    public function getCodeAttribute(): string
    {
        return sprintf('SUP-%04d', $this->id);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }

    public function getBranchNameAttribute(): ?string
    {
        return Branch::nameForCode($this->location);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out';
        }

        if ($this->critical_level > 0 && $this->quantity <= $this->critical_level) {
            return 'critical';
        }

        return $this->quantity <= $this->reorder_level ? 'low' : 'ok';
    }

    public function getStockHintAttribute(): string
    {
        return self::STOCK_HINTS[$this->stock_status];
    }

    /** Units promised to pending requests (their tickets aren't resolved or cancelled yet). */
    public function getReservedQuantityAttribute(): int
    {
        // Lists load this in one query via withReserved(); fall back to asking for a single item.
        if (array_key_exists('reserved_sum', $this->attributes)) {
            return (int) $this->attributes['reserved_sum'];
        }

        return (int) $this->requests()->active()->sum('quantity');
    }

    /** What can still be promised: on hand minus reserved. */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /* ---------- Query helpers ---------- */

    public function scopeStockStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'out' => $query->where('quantity', '<=', 0),
            'critical' => $query->where('quantity', '>', 0)
                ->where('critical_level', '>', 0)
                ->whereColumn('quantity', '<=', 'critical_level'),
            'low' => $query->where('quantity', '>', 0)
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->where(fn ($q) => $q->where('critical_level', '<=', 0)->orWhereColumn('quantity', '>', 'critical_level')),
            'ok' => $query->whereColumn('quantity', '>', 'reorder_level'),
            default => $query,
        };
    }

    /** Adds `reserved_sum` to each row in one query (used by the list page). */
    public function scopeWithReserved(Builder $query): Builder
    {
        return $query->withSum(['requests as reserved_sum' => fn ($q) => $q->active()], 'quantity');
    }

    /**
     * Items that need restocking (low, critical or out), and how many of those are urgent
     * (critical or out). Drives the menu badge: amber when it's just "low", red when urgent.
     *
     * @return array{total: int, severe: int}
     */
    public static function restockAlert(): array
    {
        try {
            $row = static::query()->toBase()
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN quantity <= 0 OR (critical_level > 0 AND quantity <= critical_level) THEN 1 ELSE 0 END) as severe')
                ->first();

            return ['total' => (int) ($row->total ?? 0), 'severe' => (int) ($row->severe ?? 0)];
        } catch (QueryException) {
            // Table not migrated yet — never let a nav badge take the whole app down.
            return ['total' => 0, 'severe' => 0];
        }
    }

    public static function needingRestockCount(): int
    {
        return static::restockAlert()['total'];
    }

    /* ---------- Brands ---------- */

    /** Letters and digits only, lower-case — so "A4 Tech", "a4-tech" and "A4Tech" are the same brand. */
    private static function brandKey(string $brand): string
    {
        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($brand));
    }

    /** Is this one of the company's own printer brands (Brother, Epson)? */
    public static function isCompanyPrinterBrand(?string $brand): bool
    {
        if (blank($brand)) {
            return false;
        }

        $key = self::brandKey($brand);

        foreach (self::PRINTER_BRANDS as $printerBrand) {
            if (self::brandKey($printerBrand) === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every brand we already know: the starter lists first, then whatever is in stock
     * (most used first). One entry per brand, whatever the capitalisation or spacing.
     *
     * @return list<string>
     */
    public static function knownBrands(): array
    {
        $inStock = static::query()->toBase()
            ->whereNotNull('brand')->where('brand', '!=', '')
            ->groupBy('brand')
            ->orderByRaw('COUNT(*) DESC')->orderBy('brand')
            ->pluck('brand');

        return collect(self::COMMON_BRANDS)->flatten()
            ->merge($inStock)
            ->unique(fn ($brand) => self::brandKey($brand))
            ->values()
            ->all();
    }

    /**
     * Tidy a typed brand so one brand never turns into several: trims and collapses spaces, and
     * reuses the spelling we already have ("logitech" and "A4 Tech" become "Logitech" and "A4Tech").
     * Returns null when nothing was entered.
     */
    public static function normalizeBrand(?string $brand): ?string
    {
        $brand = trim((string) preg_replace('/\s+/u', ' ', (string) $brand));

        if ($brand === '') {
            return null;
        }

        $key = self::brandKey($brand);

        if ($key !== '') {
            foreach (self::knownBrands() as $known) {
                if (self::brandKey($known) === $key) {
                    return $known;
                }
            }
        }

        return $brand;
    }

    /**
     * What the Brand picker offers for each category: the starter brands, then any other brand
     * already used in that category.
     *
     * @return array<string, list<string>>
     */
    public static function brandOptions(): array
    {
        $used = static::query()->toBase()
            ->whereNotNull('brand')->where('brand', '!=', '')
            ->groupBy('category', 'brand')
            ->orderBy('brand')
            ->get(['category', 'brand'])
            ->groupBy('category');

        $options = [];

        foreach (array_keys(self::CATEGORIES) as $category) {
            $options[$category] = collect(self::COMMON_BRANDS[$category] ?? [])
                ->merge(($used->get($category) ?? collect())->pluck('brand'))
                ->unique(fn ($brand) => self::brandKey($brand))
                ->values()
                ->all();
        }

        return $options;
    }

    /* ---------- Stock changes ---------- */

    /**
     * Change the quantity and record why.
     *
     * @param  string  $type  in | out | count   (count = "the real number is N")
     * @param  int  $amount  units to add/remove, or the counted total for `count`
     * @param  bool  $usesReservation  true when the units being removed ARE the reserved ones
     *                                 (i.e. issuing a request), so the reservation check is skipped
     *
     * @throws DomainException when the change can't be made (e.g. not enough stock)
     */
    public function applyMovement(string $type, int $amount, ?string $note = null, ?int $userId = null, bool $usesReservation = false): SupplyMovement
    {
        return DB::transaction(function () use ($type, $amount, $note, $userId, $usesReservation) {
            // Lock so two people issuing stock at the same moment can't
            // both take the last item.
            $row = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $before = $row->quantity;

            $after = match ($type) {
                'in' => $before + $amount,
                'out' => $before - $amount,
                'count' => $amount,
                default => throw new DomainException('Unknown stock action.'),
            };

            if ($after < 0) {
                throw new DomainException(
                    $before === 0
                        ? 'There is nothing in stock to take out.'
                        : "Only {$before} {$row->unit} in stock — you can't take out {$amount}."
                );
            }

            // Units promised to pending requests can't be taken out by hand.
            if ($type === 'out' && ! $usesReservation) {
                $reserved = (int) $row->requests()->active()->sum('quantity');

                if ($reserved > 0 && $after < $reserved) {
                    $available = max(0, $before - $reserved);
                    throw new DomainException("Only {$available} {$row->unit} available — {$reserved} are reserved for pending requests.");
                }
            }

            if ($type === 'count' && $after === $before) {
                throw new DomainException("The count already matches the current stock ({$before}).");
            }

            $row->update(['quantity' => $after]);
            $this->quantity = $after;

            return $row->movements()->create([
                'user_id' => $userId,
                'type' => $type,
                'change' => $after - $before,
                'balance_after' => $after,
                'note' => $note,
            ]);
        });
    }
}
