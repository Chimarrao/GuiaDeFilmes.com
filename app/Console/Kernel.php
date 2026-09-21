<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * 
     * NOTA: cache:generate agora faz swap atômico (TEMP_ -> definitivo)
     * Por isso não é mais necessário rodar cache:clear antes (zero downtime!)
     */
    protected function schedule(Schedule $schedule): void
    {
        // Cache com troca atômica (zero downtime)
        $schedule->command('cache:generate')->dailyAt('00:00');

        $schedule->command('justwatch:backfill --limit=1000')->dailyAt('01:00');
        $schedule->command('trailers:download --limit=50')->dailyAt('02:00');

        // Atualiza filmes já existentes: 1000 mais relevantes dos últimos anos + 1000 antigos aleatórios
        $schedule->command('movies:refresh-existing --recent=1000 --old=1000')->dailyAt('03:00');

        $schedule->command('sitemap:generate')->dailyAt('04:00');
    }

    /**
     * Register the commands for the application.
     */
    protected $commands = [
        \App\Console\Commands\JustwatchBackfill::class,
        \App\Console\Commands\CacheMovies::class,
        \App\Console\Commands\GenerateSitemap::class,
        \App\Console\Commands\DownloadTrailers::class,
        \App\Console\Commands\RefreshExistingMovies::class,
    ];
}