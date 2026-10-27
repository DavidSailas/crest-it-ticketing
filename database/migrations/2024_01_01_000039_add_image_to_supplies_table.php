<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Path (on the public disk) of the item's photo, so staff can recognise what they are
        // requesting. A small thumbnail is stored next to it; see App\Support\SupplyImage.
        Schema::table('supplies', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
