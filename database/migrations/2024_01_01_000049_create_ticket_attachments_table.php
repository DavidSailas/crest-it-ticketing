<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A ticket can now carry several files (screenshots, PDFs…) instead of just one.
     * The old single-file columns on tickets stay: they keep holding the FIRST file so the
     * paperclip in the ticket list keeps working. Existing attachments are copied over below.
     */
    public function up(): void
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        DB::table('tickets')
            ->whereNotNull('attachment_path')
            ->orderBy('id')
            ->each(function ($ticket) {
                DB::table('ticket_attachments')->insert([
                    'ticket_id' => $ticket->id,
                    'path' => $ticket->attachment_path,
                    'original_name' => $ticket->attachment_name ?: basename($ticket->attachment_path),
                    'created_at' => $ticket->created_at,
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
