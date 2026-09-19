<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PASSO 1: Remover índice funcional anterior (se existir)
        try {
            DB::statement('ALTER TABLE movies DROP INDEX idx_release_year');
        } catch (\Exception $e) {
        }
        
        // PASSO 2: Adicionar coluna STORED release_year
        // STORED = valor calculado e armazenado fisicamente (usa espaço, mas é muito rápido)
        DB::statement('
            ALTER TABLE movies 
            ADD COLUMN release_year INT 
            GENERATED ALWAYS AS (CAST(SUBSTR(release_date, 1, 4) AS UNSIGNED)) 
            STORED
        ');
        
        // PASSO 3: Criar índices 
        Schema::table('movies', function (Blueprint $table) {
            // Índice simples: release_year (filtros yearFrom/yearTo)
            $table->index('release_year', 'idx_release_year');
            
            // Índice simples: tmdb_rating (filtro minRating)
            $table->index('tmdb_rating', 'idx_tmdb_rating');
            
            // Índice simples: original_language (filtro language)
            $table->index('original_language', 'idx_original_language');
            
            // Índice composto: (release_year, popularity DESC)
            // Otimiza queries que filtram por ano E ordenam por popularidade
            // Exemplo: WHERE release_year >= 2020 ORDER BY popularity DESC
            $table->index(['release_year', 'popularity'], 'idx_year_popularity');
        });
        
        // PASSO 4: Criar índice JSON para gêneros (SQL raw, Blueprint não suporta)
        // JSON_EXTRACT extrai array de gêneros: ["Ação", "Aventura"]
        // Permite buscar gênero dentro do JSON de forma indexada
        DB::statement("
            CREATE INDEX idx_genres_json 
            ON movies((CAST(genres AS CHAR(500))))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover todos os índices criados
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex('idx_release_year');
            $table->dropIndex('idx_tmdb_rating');
            $table->dropIndex('idx_original_language');
            $table->dropIndex('idx_year_popularity');
        });
        
        // Remover índice JSON
        DB::statement('ALTER TABLE movies DROP INDEX idx_genres_json');
        
        // Remover coluna virtual STORED
        DB::statement('ALTER TABLE movies DROP COLUMN release_year');
        
        // Recriar índice funcional anterior (compatibilidade reversa)
        DB::statement('
            ALTER TABLE movies 
            ADD INDEX idx_release_year 
            ((CAST(SUBSTR(release_date, 1, 4) AS UNSIGNED)))
        ');
    }
};
