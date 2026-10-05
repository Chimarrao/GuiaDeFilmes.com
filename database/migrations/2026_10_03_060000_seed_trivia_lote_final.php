<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;

/**
 * Leva final de curiosidades (trivia), consolidando os lotes 3, 4 e 5.
 *
 * Antes de entrar aqui, cada tmdb_id passou por reconciliação completa
 * contra o catálogo real (scripts_tmp/trivia_reconciliation.py): os que
 * ainda não existiam no site foram importados via webhook n8n
 * (buscar-filme), um a um, com confirmação dupla depois. Só os
 * confirmados entram nesta migration.
 *
 * database/data/movie_trivia_lote_final.json contém 24 filmes — o
 * resultado de pegar os 290 filmes originais dos lotes 3+4+5, remover 84
 * que já estavam cobertos pelos lotes 1/2 (já em produção), remover 32
 * que não existiam no catálogo nem depois da tentativa de importação via
 * n8n (31 com tmdb_id inválido na própria API do TMDB, 1 que o n8n não
 * confirmou), e remover mais 150 que passaram na checagem de existência
 * mas eram sinopse genérica disfarçada de fato (ex: "Título de 1980 de
 * Diretor." / "Ator em clássico de horror.") em vez de curiosidade
 * verificável — auditoria encontrada após o lote original (gerado por um
 * agente sem muita confiança nos filmes da leva) produzir ~60% de fatos
 * inválidos. Ver .claude/skills/curiosidades-filmes/SKILL.md, seção
 * "Teste pra saber se é curiosidade de verdade ou sinopse disfarçada".
 *
 * Todos os 24 fatos restantes foram conferidos manualmente: sem
 * scraping/IA automatizada, sem invenção, específicos de cada filme.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/movie_trivia_lote_final.json');

        if (!file_exists($path)) {
            return;
        }

        $trivia = json_decode(file_get_contents($path), true);

        if (!is_array($trivia)) {
            return;
        }

        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $bar = new \Symfony\Component\Console\Helper\ProgressBar($output, count($trivia));
        $bar->setFormat('  %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->setMessage('iniciando...');
        $bar->start();

        $updated = 0;

        foreach ($trivia as $tmdbId => $facts) {
            $movie = Movie::where('tmdb_id', (int) $tmdbId)->first();

            if ($movie) {
                $movie->update(['trivia' => $facts]);
                $updated++;
                $bar->setMessage("tmdb_id {$tmdbId} ({$movie->title})");
            } else {
                $bar->setMessage("tmdb_id {$tmdbId} (não encontrado)");
            }

            $bar->advance();
        }

        $bar->finish();
        $output->writeln('');

        \Illuminate\Support\Facades\Log::info("seed_trivia_lote_final: {$updated}/" . count($trivia) . ' filmes atualizados.');
    }

    public function down(): void
    {
    }
};
