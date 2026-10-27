<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Supply;
use Illuminate\Database\Seeder;

/**
 * Optional starter data so the IT Supplies page isn't empty on a fresh install.
 * Safe to run more than once — items that already exist (same name + branch) are skipped.
 *
 *   php artisan db:seed --class=SupplySeeder
 */
class SupplySeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::orderBy('name')->first();

        if (! $branch) {
            $this->command?->warn('No branches found — add one under Admin > Branches first.');

            return;
        }

        // One of each warning level, so the colours are easy to see on a fresh install.
        $items = [
            // name, category, brand, model, unit, quantity, low at, critical at
            ['Brother TN-2380 Black Toner', 'ink_toner', 'Brother', 'TN-2380', 'cartridge', 6, 3, 1],   // in stock
            ['Epson 003 Black Ink Bottle', 'ink_toner', 'Epson', '003 / C13T00V100', 'bottle', 4, 6, 2], // low
            ['AA Batteries', 'batteries', 'Duracell', null, 'pack', 20, 8, 4],
            ['AAA Batteries', 'batteries', 'Duracell', null, 'pack', 3, 8, 4],                            // critical
            ['Wireless Mouse', 'mouse', 'Logitech', 'M185', 'pcs', 0, 3, 1],                              // out
            ['USB Keyboard', 'keyboard', 'Logitech', 'K120', 'pcs', 9, 3, 1],
            ['USB Flash Drive 32GB', 'usb_storage', 'Kingston', 'DataTraveler', 'pcs', 15, 5, 2],
            ['HDMI Cable 1.8m', 'cables', null, null, 'pcs', 7, 3, 1],
            ['Telephone Handset', 'telephone', 'Panasonic', null, 'pcs', 4, 2, 1],
            ['Telephone Line Cord (RJ11)', 'telephone', null, null, 'pcs', 12, 4, 2],
        ];

        foreach ($items as [$name, $category, $brand, $model, $unit, $quantity, $reorder, $critical]) {
            if (Supply::where('name', $name)->where('location', $branch->code)->exists()) {
                continue;
            }

            $supply = Supply::create([
                'name' => $name,
                'category' => $category,
                'brand' => $brand,
                'model_no' => $model,
                'unit' => $unit,
                'quantity' => $quantity,
                'reorder_level' => $reorder,
                'critical_level' => $critical,
                'location' => $branch->code,
            ]);

            if ($quantity > 0) {
                $supply->movements()->create([
                    'type' => 'initial',
                    'change' => $quantity,
                    'balance_after' => $quantity,
                    'note' => 'Opening stock',
                ]);
            }
        }
    }
}
