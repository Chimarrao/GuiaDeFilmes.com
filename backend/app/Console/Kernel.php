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
    }

    /**
     * Register the commands for the application.
     */
    protected $commands = [
        \App\Console\Commands\FetchMoviesTMDB::class,
        \App\Console\Commands\JustwatchBackfill::class,
        \App\Console\Commands\CacheMovies::class,
    ];
}