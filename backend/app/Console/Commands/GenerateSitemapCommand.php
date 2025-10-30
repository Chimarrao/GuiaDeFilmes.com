<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sitemap for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // TODO: Implement sitemap generation logic
        $this->info('Sitemap generated successfully!');
    }
}