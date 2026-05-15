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
        if (!Schema::hasColumn('posts', 'image_data')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->text('image_data')->nullable()->after('image');
            });
        }
        if (!Schema::hasColumn('stories', 'image_data')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->text('image_data')->nullable()->after('image');
            });
        }
        if (!Schema::hasColumn('profiles', 'avatar_data')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->text('avatar_data')->nullable()->after('avatar');
            });
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('image_data');
        });
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('image_data');
        });
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('avatar_data');
        });
    }
};
