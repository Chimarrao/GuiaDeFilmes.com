<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Movie;
use App\Models\MovieOrdering;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Ordem curada manualmente pra "Próximas Estreias": os campos de popularidade/voto
     * desses filmes ainda estão zerados no banco (nunca sincronizados de fato com o
     * TMDB), então a ordem automática por data não reflete o quão aguardado cada um
     * realmente é. Ordenado por reconhecimento de franquia/expectativa de público.
     */
    private const UPCOMING_TMDB_IDS_IN_ORDER = [
        1003596,  // Avengers: Doomsday
        1228710,  // O Mandaloriano e Grogu
        1260649,  // Jumanji 3
        1401586,  // Sonic 4: O Filme
        1320786,  // Terrifier 4
        1278853,  // Woodwalkers 2
        1487395,  // The Chosen: Crucifixion
        1488743,  // The Chosen: Resurrection
        1297842,  // Um Cabra Bom De Bola
        1327819,  // Cara de Um, Focinho de Outro
        1236153,  // Justiça Artificial
        1433027,  // Entity Within
        1301421,  // The Sheep Detectives
    ];

    public function up(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        $ordering = MovieOrdering::first() ?? MovieOrdering::create([
            'in_theaters' => [],
            'upcoming' => [],
            'released' => [],
        ]);

        // EM CARTAZ: a maioria dos filmes marcados "in_theaters" são lançamentos de
        // nicho/festival sem engajamento real (popularidade e votos zerados). Lista
        // completa (todas as páginas), com os que têm voto/nota real primeiro.
        $inTheaters = Movie::where('status', 'in_theaters')
            ->where('adult', 0)
            ->orderByDesc('tmdb_vote_count')
            ->orderByDesc('popularity')
            ->orderByDesc('tmdb_rating')
            ->orderByDesc('release_date')
            ->get(['tmdb_id', 'title']);

        $ordering->in_theaters = $inTheaters->map(fn ($m) => [
            'id_tmdb' => $m->tmdb_id,
            'title' => $m->title,
        ])->values()->all();

        // PRÓXIMAS ESTREIAS: lista completa (todas as páginas). Começa pela curadoria
        // manual acima, completa com o resto (não curado) ordenado por data.
        $upcomingMovies = Movie::where('status', 'upcoming')
            ->where('adult', 0)
            ->get(['tmdb_id', 'title']);

        $curated = [];
        foreach (self::UPCOMING_TMDB_IDS_IN_ORDER as $tmdbId) {
            $movie = $upcomingMovies->firstWhere('tmdb_id', $tmdbId);
            if ($movie) {
                $curated[] = ['id_tmdb' => $movie->tmdb_id, 'title' => $movie->title];
            }
        }

        $curatedIds = array_column($curated, 'id_tmdb');
        $rest = $upcomingMovies->whereNotIn('tmdb_id', $curatedIds)
            ->sortBy('release_date')
            ->map(fn ($m) => ['id_tmdb' => $m->tmdb_id, 'title' => $m->title])
            ->values()->all();

        $ordering->upcoming = array_merge($curated, $rest);
        $ordering->save();

        Artisan::call('cache:generate');
    }

    public function down(): void
    {
    }
};
