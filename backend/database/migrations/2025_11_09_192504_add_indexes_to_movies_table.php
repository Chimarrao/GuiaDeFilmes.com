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
        Schema::table('movies', function (Blueprint $table) {
            // Composite index para queries que filtram por data e ordenam por popularidade
            // Este é o mais importante para performance nas queries complexas
            $table->index(['release_date', 'popularity'], 'movies_release_date_popularity_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Remove o composite index
            $table->dropIndex('movies_release_date_popularity_index');
        });
    }
};
