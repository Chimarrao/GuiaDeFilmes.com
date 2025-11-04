<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executar as migrações.
     */
    public function up(): void
    {
        // Adicionar novos campos à tabela de filmes
        Schema::table('movies', function (Blueprint $table) {
            $table->boolean('adult')->default(false)->after('tmdb_id');
            $table->json('alternative_titles')->nullable()->after('where_to_watch');
            $table->json('external_ids')->nullable()->after('alternative_titles');
            $table->json('keywords')->nullable()->after('external_ids');
            $table->json('similar')->nullable()->after('keywords');
            $table->json('justwatch_watch_info')->nullable()->comment('informações da api não oficial via scrap do justwatch')->after('similar');
        });

        /**
         * Tabelas futuras - Ainda não implementadas, mas esquema pronto
         * 
         * Essas tabelas serão usadas para recursos futuros:
         * - reviews: Sistema de avaliações e comentários dos usuários
         * - sources: Rastreamento de fontes de dados externas
         * - movies_ai: Conteúdo e análise gerados por IA
         * 
         * Atualmente comentadas para manter o banco de dados limpo.
         * Descomente quando os recursos estiverem prontos para implementação.
         */
        
        // Remover tabelas não utilizadas se existirem (limpeza de migrações anteriores)
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('movies_ai');
    }

    /**
     * Reverter as migrações.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn([
                'adult',
                'alternative_titles',
                'external_ids',
                'keywords',
                'similar',
                'justwatch_watch_info'
            ]);
        });
        
        // Nota: Não recriamos as tabelas removidas no rollback pois não estavam sendo usadas
    }
};
