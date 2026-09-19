<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // FULLTEXT só existe no MySQL; SQLite (usado em dev local) não suporta.
        if (DB::getDriverName() === 'mysql') {
            // Adiciona índice FULLTEXT para busca rápida
            // Nota: genres é JSON, então não pode ser indexado diretamente
            DB::statement('ALTER TABLE movies ADD FULLTEXT fulltext_search (title, synopsis, tagline)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Remove índice FULLTEXT
            DB::statement('ALTER TABLE movies DROP INDEX fulltext_search');
        }
    }
};
