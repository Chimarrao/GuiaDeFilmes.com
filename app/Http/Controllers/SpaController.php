<?php

namespace App\Http\Controllers;

use App\Enums\CountryCode;
use App\Enums\DecadeRange;
use App\Enums\GenreSlug;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Serve a SPA (frontend/index.html buildado) preenchendo <title>, meta tags,
 * canonical e um bloco de conteúdo real no <div id="app"> com base na rota
 * pedida, ANTES do Vue montar. Sem isso o Google via só um shell vazio com
 * o title/canonical genérico da home em toda página do site (inclusive
 * páginas de filme), o que fazia o Google tratar cada filme como cópia da
 * home ("Alternate page with proper canonical tag").
 *
 * O Vue continua trocando esses mesmos elementos via useHead() depois de
 * montar — o que está aqui é só o que aparece pro primeiro fetch (crawler,
 * preview de link, curl), sem JS.
 */
class SpaController extends Controller
{
    private const BASE_URL = 'https://guiadefilmes.com';

    public function render(Request $request, string $any = '')
    {
        $path = '/' . ltrim($any, '/');
        $indexPath = public_path('index.html');

        if (!File::exists($indexPath)) {
            abort(500, 'Build do frontend não encontrado (public/index.html ausente).');
        }

        $cacheKey = 'spa_seo_html:' . $path;
        $cached = Cache::remember($cacheKey, now()->addHours(6), function () use ($path, $indexPath) {
            $template = File::get($indexPath);
            $meta = $this->resolveMeta($path);

            return [
                'html' => $this->injectMeta($template, $meta),
                'status' => $meta['notFound'] ?? false ? 404 : 200,
            ];
        });

        return response($cached['html'], $cached['status'])->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function resolveMeta(string $path): array
    {
        $canonicalPath = rtrim($path, '/');
        $canonicalPath = $canonicalPath === '' ? '/' : $canonicalPath;
        $canonical = $canonicalPath === '/' ? self::BASE_URL . '/' : self::BASE_URL . $canonicalPath;

        $default = [
            'title' => 'Guia de Filmes - Descubra os Melhores Filmes e Onde Assistir',
            'description' => 'Guia de Filmes: Descubra filmes em cartaz, próximas estreias, lançamentos e onde assistir online. Catálogo completo com informações detalhadas sobre os melhores filmes.',
            'ogTitle' => 'Guia de Filmes - Seu Guia Completo de Cinema',
            'ogDescription' => 'Explore o melhor do cinema: filmes em cartaz, estreias, lançamentos e onde assistir.',
            'ogType' => 'website',
            'ogImage' => self::BASE_URL . '/og-image.jpg',
            'canonical' => $canonical,
            'content' => null,
            'jsonLd' => null,
        ];

        if ($canonicalPath === '/') {
            return $default;
        }

        if (preg_match('#^/filme/([^/]+)$#', $canonicalPath, $m)) {
            return $this->movieMeta($m[1], $default);
        }

        if (preg_match('#^/explorar/genero/([^/]+)$#', $canonicalPath, $m)) {
            $name = GenreSlug::tryFrom($m[1])?->label() ?? ucfirst($m[1]);

            return array_merge($default, [
                'title' => "{$name} - Explorar - Guia de Filmes",
                'description' => "Descubra os filmes mais populares do gênero {$name}.",
                'ogTitle' => "Filmes de {$name} - Guia de Filmes",
                'ogDescription' => "Descubra os filmes mais populares do gênero {$name}.",
                'canonical' => $canonical,
                'content' => "<h1>Filmes de {$name}</h1><p>Descubra os filmes mais populares do gênero {$name} no Guia de Filmes.</p>",
                'jsonLd' => [$this->breadcrumbs([
                    ['name' => 'Explorar', 'path' => '/explorar'],
                    ['name' => $name, 'path' => $canonicalPath],
                ])],
            ]);
        }

        if (preg_match('#^/explorar/(?:decada|ano)/([^/]+)$#', $canonicalPath, $m)) {
            $label = DecadeRange::tryFromValue($m[1])?->label() ?? $m[1];

            return array_merge($default, [
                'title' => "{$label} - Explorar - Guia de Filmes",
                'description' => "Descubra filmes dos {$label}.",
                'ogTitle' => "Filmes dos {$label} - Guia de Filmes",
                'ogDescription' => "Descubra filmes dos {$label}.",
                'canonical' => $canonical,
                'content' => "<h1>Filmes dos {$label}</h1><p>Descubra os melhores filmes dos {$label} no Guia de Filmes.</p>",
                'jsonLd' => [$this->breadcrumbs([
                    ['name' => 'Explorar', 'path' => '/explorar'],
                    ['name' => $label, 'path' => $canonicalPath],
                ])],
            ]);
        }

        if (preg_match('#^/explorar/pais/([^/]+)$#', $canonicalPath, $m)) {
            $name = CountryCode::tryFrom(strtoupper($m[1]))?->label() ?? $m[1];

            return array_merge($default, [
                'title' => "Filmes {$name} - Guia de Filmes",
                'description' => "Descubra os melhores filmes de {$name}. Veja títulos populares, clássicos e lançados em alta do cinema " . mb_strtolower($name) . '.',
                'ogTitle' => "Filmes {$name} - Guia de Filmes",
                'ogDescription' => "Descubra os melhores filmes de {$name}.",
                'canonical' => $canonical,
                'content' => "<h1>Filmes de {$name}</h1><p>Descubra os melhores filmes de {$name} no Guia de Filmes.</p>",
                'jsonLd' => [$this->breadcrumbs([
                    ['name' => 'Explorar', 'path' => '/explorar'],
                    ['name' => $name, 'path' => $canonicalPath],
                ])],
            ]);
        }

        $staticPages = [
            '/estreias' => [
                'title' => 'Próximas Estreias - Guia de Filmes',
                'description' => 'Confira os próximos lançamentos de filmes nos cinemas. Fique por dentro das estreias mais aguardadas e planeje sua próxima sessão de cinema.',
                'ogTitle' => 'Próximas Estreias - Guia de Filmes',
                'ogDescription' => 'Os filmes que estão chegando aos cinemas em breve',
                'content' => '<h1>Próximas Estreias</h1><p>Confira os próximos lançamentos de filmes nos cinemas.</p>',
            ],
            '/em-cartaz' => [
                'title' => 'Filmes em Cartaz - Guia de Filmes',
                'description' => 'Descubra quais filmes estão em cartaz nos cinemas. Confira a lista completa de filmes disponíveis para assistir agora mesmo.',
                'ogTitle' => 'Filmes em Cartaz - Guia de Filmes',
                'ogDescription' => 'Filmes disponíveis nos cinemas agora',
                'content' => '<h1>Filmes em Cartaz</h1><p>Descubra quais filmes estão em cartaz nos cinemas agora.</p>',
            ],
            '/lancamentos' => [
                'title' => 'Lançados em Alta - Guia de Filmes',
                'description' => 'Veja os filmes que acabaram de estrear nos cinemas. Fique por dentro dos lançados em alta e não perca nenhuma novidade.',
                'ogTitle' => 'Lançados em Alta - Guia de Filmes',
                'ogDescription' => 'Filmes recém lançados nos cinemas',
                'content' => '<h1>Lançados em Alta</h1><p>Veja os filmes que acabaram de estrear nos cinemas.</p>',
            ],
            '/sobre' => [
                'title' => 'Sobre - Guia de Filmes',
                'description' => 'Conheça o Guia de Filmes, quem mantém o site, de onde vêm os dados e como entrar em contato.',
                'ogTitle' => 'Sobre o Guia de Filmes',
                'ogDescription' => 'Plataforma moderna para descoberta e exploração de filmes com informações sobre onde assistir',
                'content' => '<h1>Sobre o Guia de Filmes</h1>'
                    . '<p>O Guia de Filmes é um catálogo de cinema independente, criado e mantido de forma '
                    . 'independente. O objetivo é reunir, num só lugar, informações de filmes (sinopse, elenco, '
                    . 'nota, onde assistir) de forma rápida e sem anúncios invasivos.</p>'
                    . '<p>De onde vêm os dados: as informações de filmes (sinopse, elenco, pôsteres, notas) são '
                    . 'fornecidas pela API do TMDB (The Movie Database); os dados de onde assistir (streaming) vêm '
                    . 'do JustWatch. O Guia de Filmes não é afiliado a nenhuma das duas plataformas.</p>',
                // TODO: adicionar um e-mail/canal de contato público aqui — de
                // propósito deixei sem e-mail pessoal, isso é uma decisão sua.
            ],
            '/buscar' => [
                'title' => 'Buscar Filmes - Guia de Filmes',
                'description' => 'Busque por seus filmes favoritos no Guia de Filmes.',
                'content' => '<h1>Buscar Filmes</h1><p>Busque por seus filmes favoritos no Guia de Filmes.</p>',
            ],
            '/explorar' => [
                'title' => 'Explorar - Guia de Filmes',
                'description' => 'Descubra filmes por gênero, década ou use filtros avançados para encontrar o filme perfeito.',
                'content' => '<h1>Explorar Filmes</h1><p>Descubra filmes por gênero, década ou país.</p>',
            ],
        ];

        if (isset($staticPages[$canonicalPath])) {
            return array_merge($default, $staticPages[$canonicalPath], ['canonical' => $canonical]);
        }

        // Rota não mapeada (ex: SPA route desconhecida) — usa os defaults da
        // home, mas com o canonical correto pra não duplicar sinal de conteúdo.
        return array_merge($default, ['canonical' => $canonical]);
    }

    private function movieMeta(string $slug, array $default): array
    {
        $movie = Movie::where('slug', $slug)->first();

        if (!$movie) {
            return array_merge($default, ['notFound' => true]);
        }

        $year = $movie->release_date ? $movie->release_date->format('Y') : '-';
        $synopsis = trim((string) ($movie->synopsis ?: $movie->imdb_synopsis));
        $shortSynopsis = mb_substr($synopsis, 0, 200);
        $poster = $movie->poster_url ?: ($movie->imdb_poster_url ?: (self::BASE_URL . '/og-image.jpg'));
        $genres = is_array($movie->genres) ? $movie->genres : [];
        $releaseDateFormatted = $movie->release_date ? $movie->release_date->format('d/m/Y') : '-';

        $movieJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Movie',
            'name' => $movie->title,
            'description' => $synopsis,
            'image' => $poster,
            'datePublished' => $movie->release_date?->format('Y-m-d'),
            'genre' => $genres,
        ];

        if ($movie->tmdb_vote_count > 0) {
            // bestRating/worstRating são obrigatórios pro Google aceitar o
            // snippet: sem eles, a validação assume a escala padrão do
            // schema.org (1-5) e marca qualquer nota 0-10 como "fora do
            // intervalo" (confirmado via Search Console: "A classificação
            // está fora do intervalo padrão ou especificado").
            $movieJsonLd['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $movie->tmdb_rating,
                'ratingCount' => $movie->tmdb_vote_count,
                'bestRating' => 10,
                'worstRating' => 0,
            ];
        }

        $breadcrumbTrail = [];
        if (!empty($genres)) {
            $firstGenreSlug = $this->genreNameToSlug($genres[0]);
            if ($firstGenreSlug) {
                $breadcrumbTrail[] = ['name' => $genres[0], 'path' => '/explorar/genero/' . $firstGenreSlug];
            }
        }
        $breadcrumbTrail[] = ['name' => $movie->title, 'path' => '/filme/' . $movie->slug];

        $jsonLdBlocks = array_values(array_filter([
            $movieJsonLd,
            $this->breadcrumbs($breadcrumbTrail),
            $this->movieFaq($movie),
        ]));

        return [
            'title' => "{$movie->title} ({$year}) - Data de Lançamento, Elenco e Trailer | Guia de Filmes",
            'description' => "{$movie->title} estreia em {$releaseDateFormatted}. Veja elenco completo, trailer, sinopse e onde assistir. " . mb_substr($synopsis, 0, 100),
            'ogTitle' => "{$movie->title} ({$year})",
            'ogDescription' => $shortSynopsis ?: 'Veja detalhes completos',
            'ogType' => 'video.movie',
            'ogImage' => $poster,
            'canonical' => self::BASE_URL . '/filme/' . $movie->slug,
            'content' => $this->movieContentHtml($movie, $year, $synopsis, $poster, $genres, $releaseDateFormatted),
            'jsonLd' => $jsonLdBlocks,
        ];
    }

    /**
     * Converte o nome de um gênero (como salvo no filme, ex: "Ação") pro
     * slug correspondente do enum (ex: "acao"), pra montar o link do
     * breadcrumb. Devolve null se não encontrar correspondência exata.
     */
    private function genreNameToSlug(string $genreName): ?string
    {
        foreach (GenreSlug::cases() as $case) {
            if (mb_strtolower($case->label()) === mb_strtolower($genreName)) {
                return $case->value;
            }
        }

        return null;
    }

    /**
     * Monta o schema.org BreadcrumbList a partir de uma trilha de
     * [name, path] (sempre prefixada por "Início"). Path relativo,
     * resolvido aqui pra URL absoluta.
     */
    private function breadcrumbs(array $trail): array
    {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => self::BASE_URL . '/'],
        ];

        $position = 2;
        foreach ($trail as $step) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $step['name'],
                'item' => self::BASE_URL . $step['path'],
            ];
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Gera um FAQPage schema.org com 2-4 perguntas a partir de dados que já
     * existem no filme (nunca inventa resposta sem dado real por trás — uma
     * pergunta só entra se tiver dado pra responder). Mesmas perguntas
     * aparecem como accordion visual na MovieDetail.vue.
     */
    private function movieFaq(Movie $movie): ?array
    {
        $questions = [];

        $platforms = $this->wherToWatchPlatformNames($movie);
        if (!empty($platforms)) {
            $questions[] = [
                '@type' => 'Question',
                'name' => "Onde assistir {$movie->title}?",
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => "{$movie->title} está disponível em: " . implode(', ', $platforms) . '.',
                ],
            ];
        }

        if ($movie->runtime > 0) {
            $hours = intdiv($movie->runtime, 60);
            $minutes = $movie->runtime % 60;
            $durationText = $hours > 0
                ? "{$hours}h" . ($minutes > 0 ? "{$minutes}min" : '')
                : "{$minutes} minutos";
            $questions[] = [
                '@type' => 'Question',
                'name' => "Qual a duração de {$movie->title}?",
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => "{$movie->title} tem {$movie->runtime} minutos de duração ({$durationText}).",
                ],
            ];
        }

        if ($movie->release_date) {
            $isFuture = $movie->release_date->isFuture();
            $dateText = $movie->release_date->format('d/m/Y');
            $questions[] = [
                '@type' => 'Question',
                'name' => $isFuture ? "Quando {$movie->title} estreia?" : "Quando {$movie->title} foi lançado?",
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $isFuture
                        ? "{$movie->title} tem estreia prevista para {$dateText}."
                        : "{$movie->title} foi lançado em {$dateText}.",
                ],
            ];
        }

        if ($movie->tmdb_vote_count > 0) {
            $questions[] = [
                '@type' => 'Question',
                'name' => "Qual a nota de {$movie->title}?",
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => "{$movie->title} tem nota " . number_format((float) $movie->tmdb_rating, 1)
                        . "/10 no TMDB, baseada em " . number_format($movie->tmdb_vote_count, 0, ',', '.') . ' avaliações.',
                ],
            ];
        }

        if (empty($questions)) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];
    }

    /**
     * Nomes únicos de plataforma a partir de where_to_watch (dados limpos
     * do TMDB, ex: "Looke", "HBO Max") — não justwatch_watch_info, que tem
     * um bug histórico de dados antigos salvos como "Unknown" (corrigido
     * no script pra filmes novos, mas nunca reprocessado em massa pros
     * registros antigos).
     */
    private function wherToWatchPlatformNames(Movie $movie): array
    {
        $whereToWatch = is_array($movie->where_to_watch) ? $movie->where_to_watch : [];

        $names = collect($whereToWatch)
            ->pluck('name')
            ->filter(fn ($name) => !empty($name) && $name !== 'Unknown')
            ->unique()
            ->values()
            ->all();

        return array_slice($names, 0, 8);
    }

    private function movieContentHtml(Movie $movie, string $year, string $synopsis, string $poster, array $genres, string $releaseDateFormatted): string
    {
        $title = e($movie->title);
        $synopsisEsc = e($synopsis ?: 'Sinopse não disponível.');
        $posterEsc = e($poster);
        $parts = [
            "<h1>{$title} ({$year})</h1>",
            "<img src=\"{$posterEsc}\" alt=\"Pôster de {$title}\">",
            "<p>{$synopsisEsc}</p>",
            "<p>Data de lançamento: " . e($releaseDateFormatted) . '</p>',
        ];

        if ($movie->tmdb_vote_count > 0) {
            $parts[] = '<p>Nota: ' . e(number_format((float) $movie->tmdb_rating, 1)) . '/10</p>';
        }

        if (!empty($genres)) {
            $parts[] = '<p>Gêneros: ' . e(implode(', ', $genres)) . '</p>';
        }

        $platforms = $this->wherToWatchPlatformNames($movie);
        if (!empty($platforms)) {
            $parts[] = '<p>Onde assistir: ' . e(implode(', ', $platforms)) . '</p>';
        }

        if (!empty($movie->trivia) && is_array($movie->trivia)) {
            $parts[] = '<h2>Curiosidades</h2><ul>';
            foreach ($movie->trivia as $fact) {
                $parts[] = '<li>' . e($fact) . '</li>';
            }
            $parts[] = '</ul>';
        }

        return implode('', $parts);
    }

    private function injectMeta(string $html, array $meta): string
    {
        $title = e($meta['title']);
        $desc = e($meta['description']);
        $canonical = e($meta['canonical']);
        $ogTitle = e($meta['ogTitle']);
        $ogDesc = e($meta['ogDescription']);
        $ogType = e($meta['ogType']);
        $ogImage = e($meta['ogImage']);

        $html = preg_replace('#<title>.*?</title>#s', "<title>{$title}</title>", $html, 1);
        $html = preg_replace('#(<meta\s+name="description"\s+content=")[^"]*(")#i', "\$1{$desc}\$2", $html, 1);
        $html = preg_replace('#(<link\s+rel="canonical"\s+href=")[^"]*("\s*/?>)#i', "\$1{$canonical}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="og:url"\s+content=")[^"]*(")#i', "\$1{$canonical}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="og:title"\s+content=")[^"]*(")#i', "\$1{$ogTitle}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="og:description"\s+content=")[^"]*(")#i', "\$1{$ogDesc}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="og:type"\s+content=")[^"]*(")#i', "\$1{$ogType}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="og:image"\s+content=")[^"]*(")#i', "\$1{$ogImage}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="twitter:url"\s+content=")[^"]*(")#i', "\$1{$canonical}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="twitter:title"\s+content=")[^"]*(")#i', "\$1{$ogTitle}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="twitter:description"\s+content=")[^"]*(")#i', "\$1{$ogDesc}\$2", $html, 1);
        $html = preg_replace('#(<meta\s+property="twitter:image"\s+content=")[^"]*(")#i', "\$1{$ogImage}\$2", $html, 1);

        if (!empty($meta['jsonLd'])) {
            $json = json_encode($meta['jsonLd'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $html = str_replace('</head>', "<script type=\"application/ld+json\">{$json}</script>\n</head>", $html);
        }

        if (!empty($meta['content'])) {
            $html = str_replace('<div id="app"></div>', '<div id="app">' . $meta['content'] . '</div>', $html);
        }

        return $html;
    }
}
