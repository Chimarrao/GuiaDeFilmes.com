<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('cache:clear')->dailyAt('00:00');
        $schedule->command('cache:generate')->dailyAt('00:00');

        $schedule->command('justwatch:backfill --limit=1000')->dailyAt('01:00');
        $schedule->command('trailers:download --limit=50')->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected $commands = [
        \App\Console\Commands\FetchMoviesTMDB::class,
        \App\Console\Commands\JustwatchBackfill::class,
        \App\Console\Commands\CacheMovies::class,
        \App\Console\Commands\GenerateSitemap::class,
        \App\Console\Commands\DownloadTrailers::class,
    ];
}