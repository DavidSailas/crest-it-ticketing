<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One running thread per staff member, shared with the whole IT
        // Support team — like a helpdesk chat widget, not a 1:1 DM. Any
        // IT/admin can jump in and reply.
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // whose thread this is (the staff member)
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete(); // who actually wrote this message
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });

        // Read-receipt tracking, same pattern as ticket_reads: who has seen
        // a given thread up to what point in time.
        Schema::create('support_chat_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_user_id')->constrained('users')->cascadeOnDelete(); // whose thread
            $table->foreignId('reader_id')->constrained('users')->cascadeOnDelete(); // who read it
            $table->timestamp('last_read_at');
            $table->unique(['thread_user_id', 'reader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chat_reads');
        Schema::dropIfExists('support_messages');
    }
};
