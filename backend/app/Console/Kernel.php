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
        $schedule->command('movies:update')
                 ->dailyAt('03:00');

        $schedule->command('movies:cleanup')
                 ->weekly();

        $schedule->command('sitemap:generate')
                 ->weekly();

        $schedule->command('fetch:movies --count=20')
                 ->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected $commands = [
        \App\Console\Commands\GenerateSitemapCommand::class,
        \App\Console\Commands\FetchMoviesTMDB::class,
        \App\Console\Commands\GenerateMovieAIContent::class,
    ];
}