<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Users now sign in with a username only. The email column is kept (no data is
     * lost) but becomes optional, and anyone who has no username yet gets one built
     * from the part of their email before the "@" so they can still log in.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        $taken = DB::table('users')->whereNotNull('username')->pluck('username')
            ->map(fn ($u) => Str::lower($u))->all();

        DB::table('users')->whereNull('username')->orderBy('id')->each(function ($user) use (&$taken) {
            $base = Str::of((string) Str::before((string) $user->email, '@'))
                ->lower()->replaceMatches('/[^a-z0-9_-]+/', '_')->trim('_')->toString();
            $base = $base !== '' ? $base : 'user'.$user->id;

            $candidate = $base;
            $n = 1;
            while (in_array($candidate, $taken, true)) {
                $candidate = $base.(++$n);
            }

            $taken[] = $candidate;
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        });
    }

    public function down(): void
    {
        // Intentionally left as-is: rows without an email can't be made NOT NULL again.
    }
};
