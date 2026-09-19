<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona índices para melhorar a performance das queries mais usadas:
     * - tmdb_rating: usado em filtros de rating mínimo
     * - original_language: usado em filtros de idioma
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->index('tmdb_rating', 'movies_tmdb_rating_index');
            $table->index('original_language', 'movies_original_language_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex('movies_tmdb_rating_index');
            $table->dropIndex('movies_original_language_index');
        });
    }
};
