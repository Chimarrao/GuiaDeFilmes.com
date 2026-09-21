<?php

namespace App\Console\Commands;

use App\Enums\CountryCode;
use App\Enums\DecadeRange;
use App\Enums\GenreSlug;
use App\Models\Movie;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Gera sitemap.xml (índice) + sitemaps de filmes/páginas estáticas em public/';

    private const BASE_URL = 'https://guiadefilmes.com';
    private const MAX_URLS_PER_FILE = 40000;

    public function handle()
    {
        $dir = public_path();
        $generatedAt = now()->toAtomString();
        $sitemapFiles = [];

        $sitemapFiles[] = $this->writeStaticSitemap($dir, $generatedAt);
        $sitemapFiles[] = $this->writeGenresSitemap($dir, $generatedAt);
        $sitemapFiles[] = $this->writeDecadesSitemap($dir, $generatedAt);
        $sitemapFiles[] = $this->writeCountriesSitemap($dir, $generatedAt);
        array_push($sitemapFiles, ...$this->writeMoviesSitemaps($dir));

        $this->writeIndex($dir, $sitemapFiles);

        $this->info('Sitemap gerado: ' . implode(', ', $sitemapFiles));

        return Command::SUCCESS;
    }

    private function urlEntry(string $loc, string $lastmod): string
    {
        return "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n    <lastmod>{$lastmod}</lastmod>\n  </url>\n";
    }

    private function writeStaticSitemap(string $dir, string $generatedAt): string
    {
        $paths = ['/', '/estreias', '/em-cartaz', '/lancamentos', '/sobre', '/explorar'];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($paths as $path) {
            $xml .= $this->urlEntry(self::BASE_URL . $path, $generatedAt);
        }
        $xml .= "</urlset>\n";

        $filename = 'sitemap-static.xml';
        file_put_contents($dir . '/' . $filename, $xml);

        return $filename;
    }

    private function writeGenresSitemap(string $dir, string $generatedAt): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach (GenreSlug::cases() as $genre) {
            $xml .= $this->urlEntry(self::BASE_URL . '/explorar/genero/' . $genre->value, $generatedAt);
        }
        $xml .= "</urlset>\n";

        $filename = 'sitemap-genres.xml';
        file_put_contents($dir . '/' . $filename, $xml);

        return $filename;
    }

    private function writeDecadesSitemap(string $dir, string $generatedAt): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach (DecadeRange::cases() as $decade) {
            $xml .= $this->urlEntry(self::BASE_URL . '/explorar/decada/' . $decade->value, $generatedAt);
        }
        $xml .= "</urlset>\n";

        $filename = 'sitemap-decades.xml';
        file_put_contents($dir . '/' . $filename, $xml);

        return $filename;
    }

    private function writeCountriesSitemap(string $dir, string $generatedAt): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach (CountryCode::cases() as $country) {
            $xml .= $this->urlEntry(self::BASE_URL . '/explorar/pais/' . $country->value, $generatedAt);
        }
        $xml .= "</urlset>\n";

        $filename = 'sitemap-countries.xml';
        file_put_contents($dir . '/' . $filename, $xml);

        return $filename;
    }

    /**
     * @return string[] Nomes dos arquivos de sitemap de filmes gerados
     */
    private function writeMoviesSitemaps(string $dir): array
    {
        $filenames = [];
        $fileIndex = 1;
        $urlsInCurrentFile = 0;
        $handle = null;

        $openNewFile = function () use (&$handle, &$fileIndex, &$filenames, $dir) {
            if ($handle) {
                fwrite($handle, "</urlset>\n");
                fclose($handle);
            }
            $filename = "sitemap-movies-{$fileIndex}.xml";
            $filenames[] = $filename;
            $handle = fopen($dir . '/' . $filename, 'w');
            fwrite($handle, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");
            $fileIndex++;
        };

        $openNewFile();

        Movie::where('adult', 0)
            ->whereNotNull('slug')
            ->orderBy('id')
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(5000, function ($movies) use (&$handle, &$urlsInCurrentFile, $openNewFile) {
                foreach ($movies as $movie) {
                    if ($urlsInCurrentFile >= self::MAX_URLS_PER_FILE) {
                        $openNewFile();
                        $urlsInCurrentFile = 0;
                    }

                    $lastmod = $movie->updated_at ? $movie->updated_at->toAtomString() : now()->toAtomString();
                    fwrite($handle, $this->urlEntry(self::BASE_URL . '/filme/' . $movie->slug, $lastmod));
                    $urlsInCurrentFile++;
                }
            });

        fwrite($handle, "</urlset>\n");
        fclose($handle);

        return $filenames;
    }

    private function writeIndex(string $dir, array $sitemapFiles): void
    {
        $generatedAt = now()->toAtomString();

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($sitemapFiles as $filename) {
            $xml .= "  <sitemap>\n    <loc>" . self::BASE_URL . '/' . $filename . "</loc>\n    <lastmod>{$generatedAt}</lastmod>\n  </sitemap>\n";
        }
        $xml .= "</sitemapindex>\n";

        file_put_contents($dir . '/sitemap.xml', $xml);
    }
}
