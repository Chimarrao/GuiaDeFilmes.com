<?php

// generateSitemap.php - Generate sitemap.xml and robots.txt

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Movie;

echo "Generating sitemap...\n";

$movies = Movie::all();
$sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($movies as $movie) {
    $sitemap .= '<url>' . "\n";
    $sitemap .= '<loc>https://codebr.net/cineradar/movie/' . $movie->slug . '</loc>' . "\n";
    $sitemap .= '<lastmod>' . $movie->updated_at->toISOString() . '</lastmod>' . "\n";
    $sitemap .= '<changefreq>weekly</changefreq>' . "\n";
    $sitemap .= '<priority>0.8</priority>' . "\n";
    $sitemap .= '</url>' . "\n";
}

$sitemap .= '</urlset>';

file_put_contents(__DIR__ . '/../public/sitemap.xml', $sitemap);

$robots = "User-agent: *\n";
$robots .= "Allow: /\n";
$robots .= "Sitemap: https://codebr.net/cineradar/sitemap.xml\n";

file_put_contents(__DIR__ . '/../public/robots.txt', $robots);

echo "Sitemap and robots.txt generated.\n";