<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // Short code used in asset tags and stored on users.location /
            // assets.location, e.g. Cebu -> CEB gives CFI-CEB-IT-LT-001.
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        // Seed with the branches that were previously hardcoded in
        // Asset::LOCATIONS, so existing users and assets (which already
        // store these exact codes) keep matching a real branch record.
        $existing = [
            'CEB' => 'Cebu',
            'MNL' => 'Manila',
            'CDO' => 'Cagayan de Oro',
            'DVO' => 'Davao',
        ];

        foreach ($existing as $code => $name) {
            DB::table('branches')->insert([
                'name' => $name,
                'code' => $code,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
