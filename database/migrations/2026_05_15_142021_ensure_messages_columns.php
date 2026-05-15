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
        $required = ['sender_id', 'receiver_id', 'body'];
        $hasAll   = collect($required)->every(fn($col) => Schema::hasColumn('messages', $col));

        if (!$hasAll) {
            // Drop and recreate — table is empty or has wrong schema
            Schema::dropIfExists('messages');
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sender_id');
                $table->unsignedBigInteger('receiver_id');
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['sender_id', 'receiver_id']);
                $table->index(['receiver_id', 'sender_id']);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
