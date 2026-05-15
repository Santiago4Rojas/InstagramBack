<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // If table was pre-existing with different schema, add missing columns
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'sender_id')) {
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('messages', 'receiver_id')) {
                $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('messages', 'body')) {
                $table->text('body');
            }
            if (!Schema::hasColumn('messages', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }
            if (!Schema::hasColumn('messages', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
