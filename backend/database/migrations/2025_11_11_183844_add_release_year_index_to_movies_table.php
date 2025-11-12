<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ÍNDICE DE COLUNA VIRTUAL PARA ANO DE LANÇAMENTO
     * 
     * PROBLEMA:
     * A query do método released() usa CAST(SUBSTR(release_date, 1, 4) AS UNSIGNED)
     * 
     * SOLUÇÃO - ÍNDICE EM COLUNA VIRTUAL:
     * Segundo o gpt MySQL 5.7+ suporta índices em expressões.
     * Criamos um índice virtual que pré-calcula o ano de lançamento.
     */
    public function up(): void
    {
        // Blueprint não suporta índices funcionais nativamente
        // Usar DB::statement() para SQL raw
        DB::statement('ALTER TABLE movies ADD INDEX idx_release_year ((CAST(SUBSTR(release_date, 1, 4) AS UNSIGNED)))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex('idx_release_year');
        });
    }
};
