<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // "Headsets & Webcams" doesn't fit a freight forwarder, so the category is gone (replaced
        // by "Telephone & Handsets"). Items already saved under it move to "Other" so nothing is
        // lost or left in a category the list can no longer show; IT Support can recategorise them.
        DB::table('supplies')->where('category', 'headsets')->update(['category' => 'other']);
    }

    public function down(): void
    {
        // Nothing to undo: we can't tell which "Other" items used to be headsets.
    }
};
