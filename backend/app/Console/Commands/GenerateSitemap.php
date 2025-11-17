<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use App\Enums\CountryCode;

class GenerateSitemap extends Command
{
    /**
     * O nome e assinatura do comando do console.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * A descrição do comando do console.
     *
     * @var string
     */
    protected $description = 'Gerar sitemap XML para a aplicação';

    /**
     * Executar o comando do console.
     */
    public function handle()
    {
        $baseUrl = config('app.url');

        $urls = [];

        // URL inicial (homepage)
        $urls[] = [
            'loc' => $baseUrl,
            'lastmod' => now()->toDateString(),
            'changefreq' => 'daily',
            'priority' => '1.0'
        ];

        // URLs do frontend (páginas estáticas)
        $staticPages = ['upcoming', 'in-theaters', 'released', 'countries'];
        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => $baseUrl . '/' . $page,
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        }

        // URLs por gênero
        $genres = ['acao', 'aventura', 'comedia', 'drama', 'ficcao-cientifica', 'terror', 'romance', 'suspense', 'animacao', 'fantasia'];
        foreach ($genres as $genre) {
            $urls[] = [
                'loc' => $baseUrl . '/explorar/genero/' . $genre,
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.6'
            ];
        }

        // URLs por década
        $decades = ['2020s', '2010s', '2000s', '1990s', '1980s', '1970s'];
        foreach ($decades as $decade) {
            $urls[] = [
                'loc' => $baseUrl . '/explorar/decada/' . $decade,
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.6'
            ];
        }

        // URLs por país
        $countries = array_keys(CountryCode::all());
        foreach ($countries as $country) {
            $urls[] = [
                'loc' => $baseUrl . '/explorar/pais/' . $country,
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.6'
            ];
        }

        // URLs de todos os filmes
        Movie::select()->chunk(1000, function ($movies) use (&$urls, $baseUrl) {
            foreach ($movies as $movie) {
                $urls[] = [
                    'loc' => $baseUrl . '/filme/' . $movie->slug,
                    'lastmod' => $movie->updated_at ? $movie->updated_at->toDateString() : now()->toDateString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5'
                ];
            }
        });

        // Gerar XML do sitemap
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>';

        // Salvar no arquivo public/sitemap.xml
        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('Sitemap gerado com sucesso em public/sitemap.xml');
    }
}