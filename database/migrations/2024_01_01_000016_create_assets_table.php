<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company', 10);            // CFI or C5
            $table->string('type', 5);                // DT (Desktop) or LT (Laptop)
            $table->unsignedInteger('sequence');       // running number within company+department+type
            $table->string('asset_tag')->unique();     // e.g. CFI-ACC-DT-001
            $table->string('device_name');             // e.g. "Dell Latitude 5420"
            $table->string('serial_number')->nullable();
            $table->string('status', 20)->default('active'); // active, in_repair, retired
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company', 'department_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
