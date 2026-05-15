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
        Schema::table('messages', function (Blueprint $table) {
            // Use unsignedBigInteger (no FK constraint) to avoid failures on
            // pre-existing tables with data or non-standard structure
            if (!Schema::hasColumn('messages', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->nullable();
            }
            if (!Schema::hasColumn('messages', 'receiver_id')) {
                $table->unsignedBigInteger('receiver_id')->nullable();
            }
            if (!Schema::hasColumn('messages', 'body')) {
                $table->text('body')->nullable();
            }
            if (!Schema::hasColumn('messages', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }
            if (!Schema::hasColumn('messages', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('messages', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
