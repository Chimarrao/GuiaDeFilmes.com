<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Adiciona índice composto para otimização de queries
 * 
 * ÍNDICES COMPOSTOS (Composite Indexes):
 * 
 * Um índice composto é um índice que utiliza MÚLTIPLAS colunas simultaneamente,
 * otimizando queries que filtram/ordenam por essas colunas juntas.
 * 
 * DIFERENÇA ENTRE ÍNDICES:
 * 
 * 1. ÍNDICE SIMPLES (Common Index):
 *    - Index em UMA coluna: $table->index('release_date')
 *    - Otimiza: WHERE release_date = '2024-01-01'
 *    - Otimiza: ORDER BY release_date
 * 
 * 2. ÍNDICE COMPOSTO (Composite Index):
 *    - Index em MÚLTIPLAS colunas: $table->index(['release_date', 'popularity'])
 *    - Otimiza: WHERE release_date = '2024-01-01' ORDER BY popularity DESC
 *    - Otimiza: WHERE release_date BETWEEN '2024-01-01' AND '2024-12-31'
 *    - A ORDEM DAS COLUNAS IMPORTA!
 * 
 * COMO FUNCIONA:
 * 
 * Imagine um índice como um livro com sumário:
 * 
 * - Índice simples = Sumário por capítulo (1 critério)
 * - Índice composto = Sumário por capítulo E seção (2+ critérios)
 * 
 * REGRA DA ORDEM (Left-Most Prefix):
 * 
 * Index ['release_date', 'popularity'] pode ser usado para:
 * ✅ WHERE release_date = X
 * ✅ WHERE release_date = X ORDER BY popularity
 * ✅ WHERE release_date = X AND popularity > Y
 * ❌ WHERE popularity = Y (não usa o index, pois não começa pela primeira coluna)
 * 
 * POR QUE USAMOS AQUI:
 * 
 * A query mais comum do CineRadar é:
 * SELECT * FROM movies 
 * WHERE release_date BETWEEN '2024-01-01' AND '2024-12-31' 
 * ORDER BY popularity DESC
 * 
 * Com índice composto ['release_date', 'popularity']:
 * 1. MySQL filtra por release_date RAPIDAMENTE (usando o index)
 * 2. Dentro desse resultado, já está ordenado por popularity (usando o index)
 * 3. Resultado: Query 10-100x mais rápida!
 * 
 * Sem índice composto:
 * 1. MySQL filtra por release_date (usando index simples)
 * 2. MySQL precisa ORDENAR TUDO na memória (slow!)
 * 3. Resultado: Lento em tabelas grandes (10k+ filmes)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Composite index para queries que filtram por data e ordenam por popularidade
            // Exemplo: WHERE release_date BETWEEN '2024-01-01' AND '2024-12-31' ORDER BY popularity DESC
            $table->index(['release_date', 'popularity'], 'movies_release_date_popularity_index');
        });
    }

    /**
     * Remove o índice composto
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex('movies_release_date_popularity_index');
        });
    }
};
