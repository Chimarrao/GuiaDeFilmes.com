<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * @deprecated Usando N8N
 */
class FetchMoviesTMDB extends Command
{
    protected $signature = 'fetch:movies {--count=50 : Number of movies to fetch per category}';
    protected $description = 'Fetch movies from TMDB API and store in database';

    private $apiKey;
    private $baseUrl = 'https://api.themoviedb.org/3';
    private $imageBaseUrl = 'https://image.tmdb.org/t/p/';

    public function handle()
    {
        $this->apiKey = env('TMDB_API_KEY');

        if (!$this->apiKey) {
            $this->error('TMDB API key not found in environment variables');
            return 1;
        }

        $count = (int) $this->option('count');
        $this->info("Fetching movies from TMDB API...");

        // Fetch different categories
        $this->info("\n🎬 Fetching UPCOMING movies...");
        $this->fetchUpcoming($count);

        $this->info("\n🎟️ Fetching NOW PLAYING (In Theaters) movies...");
        $this->fetchNowPlaying($count);

        $this->info("\n📦 Fetching POPULAR/RELEASED movies...");
        $this->fetchPopular($count);

        $total = Movie::count();
        $this->info("\n✅ Finished! Total movies in database: {$total}");
        
        // Show distribution
        $this->info("\nDistribution by status:");
        $this->info("  Upcoming: " . Movie::where('status', 'upcoming')->count());
        $this->info("  In Theaters: " . Movie::where('status', 'in_theaters')->count());
        $this->info("  Released: " . Movie::where('status', 'released')->count());

        return 0;
    }

    private function fetchUpcoming($limit)
    {
        $this->fetchMoviesByEndpoint('movie/upcoming', 'upcoming', $limit);
    }

    private function fetchNowPlaying($limit)
    {
        $this->fetchMoviesByEndpoint('movie/now_playing', 'in_theaters', $limit);
    }

    private function fetchPopular($limit)
    {
        $this->fetchMoviesByEndpoint('movie/popular', 'released', $limit);
    }

    private function fetchMoviesByEndpoint($endpoint, $status, $limit)
    {
        $page = 1;
        $fetchedCount = 0;
        $maxPages = ceil($limit / 20); // TMDB returns 20 per page

        while ($fetchedCount < $limit && $page <= $maxPages) {
            try {
                $response = Http::withOptions(['verify' => false])->get("{$this->baseUrl}/{$endpoint}", [
                    'api_key' => $this->apiKey,
                    'language' => 'pt-BR',
                    'page' => $page,
                    'region' => 'BR'
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['results']) && is_array($data['results'])) {
                        foreach ($data['results'] as $movieData) {
                            if ($fetchedCount >= $limit) break;

                            // Check if movie already exists
                            $existing = Movie::where('title', $movieData['title'])->first();
                            
                            // Get full movie details including trailer
                            $details = $this->getMovieDetails($movieData['id']);
                            
                            if ($details) {
                                if ($existing) {
                                    // UPDATE existing movie
                                    $this->updateMovie($existing, $details, $status);
                                    $this->line("  ↻ Updated: {$movieData['title']}");
                                } else {
                                    // CREATE new movie
                                    $movie = $this->saveMovie($details, $status);
                                    if ($movie) {
                                        $this->info("  ✅ Saved: {$movie->title} [{$status}]");
                                        $fetchedCount++;
                                    }
                                }
                            }

                            usleep(100000); // 100ms delay between requests
                        }
                    }
                }

                $page++;
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }
        }
    }

    private function getMovieDetails($movieId)
    {
        try {
            // Get movie details with all extras
            $response = Http::withOptions(['verify' => false])->get("{$this->baseUrl}/movie/{$movieId}", [
                'api_key' => $this->apiKey,
                'language' => 'pt-BR',
                'append_to_response' => 'videos,credits,images'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Get images in English too for better quality
                $imagesResponse = Http::withOptions(['verify' => false])->get("{$this->baseUrl}/movie/{$movieId}/images", [
                    'api_key' => $this->apiKey,
                    'include_image_language' => 'pt,en,null'
                ]);
                
                if ($imagesResponse->successful()) {
                    $data['all_images'] = $imagesResponse->json();
                }
                
                return $data;
            }
        } catch (\Exception $e) {
            $this->error("Error fetching details for movie {$movieId}: " . $e->getMessage());
        }

        return null;
    }

    private function getWhereToWatch($tmdbId)
    {
        if (!$tmdbId) {
            return [];
        }

        try {
            // Use TMDB Watch Providers API
            $response = Http::withOptions(['verify' => false])
                ->get("{$this->baseUrl}/movie/{$tmdbId}/watch/providers", [
                    'api_key' => $this->apiKey
                ]);

            if (!$response->successful()) {
                $this->line("  No watch providers data for TMDB ID: {$tmdbId}");
                return [];
            }

            $data = $response->json();
            
            // Extract watch providers for Brazil (BR)
            if (!isset($data['results']['BR'])) {
                $this->line("  No BR watch providers for TMDB ID: {$tmdbId}");
                return [];
            }

            $brProviders = $data['results']['BR'];
            $whereToWatch = [];

            // Process flatrate (subscription services)
            if (isset($brProviders['flatrate']) && is_array($brProviders['flatrate'])) {
                foreach ($brProviders['flatrate'] as $provider) {
                    $whereToWatch[] = [
                        'name' => $provider['provider_name'] ?? 'Unknown',
                        'type' => 'subscription',
                        'region' => 'BR',
                        'web_url' => $brProviders['link'] ?? null,
                        'format' => 'HD',
                        'price' => null,
                        'logo' => isset($provider['logo_path']) 
                            ? $this->imageBaseUrl . 'original' . $provider['logo_path']
                            : null,
                        'display_priority' => $provider['display_priority'] ?? 999,
                    ];
                }
            }

            // Process rent
            if (isset($brProviders['rent']) && is_array($brProviders['rent'])) {
                foreach ($brProviders['rent'] as $provider) {
                    $whereToWatch[] = [
                        'name' => $provider['provider_name'] ?? 'Unknown',
                        'type' => 'rent',
                        'region' => 'BR',
                        'web_url' => $brProviders['link'] ?? null,
                        'format' => 'HD',
                        'price' => null,
                        'logo' => isset($provider['logo_path']) 
                            ? $this->imageBaseUrl . 'original' . $provider['logo_path']
                            : null,
                        'display_priority' => $provider['display_priority'] ?? 999,
                    ];
                }
            }

            // Process buy
            if (isset($brProviders['buy']) && is_array($brProviders['buy'])) {
                foreach ($brProviders['buy'] as $provider) {
                    $whereToWatch[] = [
                        'name' => $provider['provider_name'] ?? 'Unknown',
                        'type' => 'buy',
                        'region' => 'BR',
                        'web_url' => $brProviders['link'] ?? null,
                        'format' => 'HD',
                        'price' => null,
                        'logo' => isset($provider['logo_path']) 
                            ? $this->imageBaseUrl . 'original' . $provider['logo_path']
                            : null,
                        'display_priority' => $provider['display_priority'] ?? 999,
                    ];
                }
            }

            if (count($whereToWatch) > 0) {
                $this->info("  ✓ Found " . count($whereToWatch) . " watch providers in Brazil");
            }

            return $whereToWatch;

        } catch (\Exception $e) {
            $this->error("  ✗ Watch Providers API error: " . $e->getMessage());
            return [];
        }
    }

    private function updateMovie($movie, $movieData, $defaultStatus)
    {
        try {
            // Get genres
            $genres = [];
            if (isset($movieData['genres']) && is_array($movieData['genres'])) {
                $genres = array_map(fn($g) => $g['name'], $movieData['genres']);
            }

            // Get cast (top 15 actors)
            $cast = [];
            if (isset($movieData['credits']['cast']) && is_array($movieData['credits']['cast'])) {
                $cast = array_slice(array_map(function($actor) {
                    return [
                        'id' => $actor['id'],
                        'name' => $actor['name'],
                        'character' => $actor['character'],
                        'profile_path' => isset($actor['profile_path']) 
                            ? $this->imageBaseUrl . 'w185' . $actor['profile_path']
                            : null,
                    ];
                }, $movieData['credits']['cast']), 0, 15);
            }

            // Get crew (director, writer, producer)
            $crew = [];
            if (isset($movieData['credits']['crew']) && is_array($movieData['credits']['crew'])) {
                $crewRoles = ['Director', 'Writer', 'Producer'];
                foreach ($movieData['credits']['crew'] as $member) {
                    if (in_array($member['job'], $crewRoles)) {
                        $crew[] = [
                            'id' => $member['id'],
                            'name' => $member['name'],
                            'job' => $member['job'],
                            'department' => $member['department'],
                        ];
                    }
                }
            }

            // Get videos (all trailers, teasers, clips, etc)
            $videos = [];
            if (isset($movieData['videos']['results']) && is_array($movieData['videos']['results'])) {
                foreach ($movieData['videos']['results'] as $video) {
                    if ($video['site'] === 'YouTube') {
                        $videos[] = [
                            'key' => $video['key'],
                            'name' => $video['name'],
                            'type' => $video['type'],
                            'official' => $video['official'] ?? false,
                            'url' => "https://www.youtube.com/watch?v={$video['key']}",
                        ];
                    }
                }
            }

            // Get trailer URL
            $trailerUrl = null;
            foreach ($videos as $video) {
                if ($video['type'] === 'Trailer' && $video['official']) {
                    $trailerUrl = $video['url'];
                    break;
                }
            }
            if (!$trailerUrl && count($videos) > 0) {
                $trailerUrl = $videos[0]['url'];
            }

            // Get images
            $images = [
                'backdrops' => [],
                'posters' => []
            ];
            if (isset($movieData['all_images']['backdrops']) && is_array($movieData['all_images']['backdrops'])) {
                $images['backdrops'] = array_slice(array_map(function($img) {
                    return ['url' => $this->imageBaseUrl . 'original' . $img['file_path']];
                }, $movieData['all_images']['backdrops']), 0, 10);
            }
            if (isset($movieData['all_images']['posters']) && is_array($movieData['all_images']['posters'])) {
                $images['posters'] = array_slice(array_map(function($img) {
                    return ['url' => $this->imageBaseUrl . 'w500' . $img['file_path']];
                }, $movieData['all_images']['posters']), 0, 10);
            }

            // Production companies/countries
            $productionCompanies = [];
            if (isset($movieData['production_companies']) && is_array($movieData['production_companies'])) {
                $productionCompanies = array_map(fn($c) => ['name' => $c['name']], $movieData['production_companies']);
            }

            $productionCountries = [];
            if (isset($movieData['production_countries']) && is_array($movieData['production_countries'])) {
                $productionCountries = array_map(fn($c) => ['name' => $c['name']], $movieData['production_countries']);
            }

            // Determine status
            $releaseDate = $movieData['release_date'] ?? null;
            $status = $this->determineStatus($releaseDate, $defaultStatus);

            // Get "Where to Watch" using TMDB Watch Providers API
            $tmdbId = $movieData['id'] ?? null;
            $whereToWatch = $this->getWhereToWatch($tmdbId);

            // Update the movie
            $movie->update([
                'tmdb_id' => $tmdbId,
                'synopsis' => $movieData['overview'] ?? $movie->synopsis,
                'release_date' => $releaseDate ?? $movie->release_date,
                'status' => $status,
                'genres' => $genres,
                'poster_url' => isset($movieData['poster_path']) 
                    ? $this->imageBaseUrl . 'w500' . $movieData['poster_path']
                    : $movie->poster_url,
                'backdrop_url' => isset($movieData['backdrop_path'])
                    ? $this->imageBaseUrl . 'original' . $movieData['backdrop_path']
                    : $movie->backdrop_url,
                'trailer_url' => $trailerUrl ?? $movie->trailer_url,
                'cast' => $cast,
                'crew' => $crew,
                'videos' => $videos,
                'images' => $images,
                'tmdb_rating' => $movieData['vote_average'] ?? $movie->tmdb_rating,
                'tmdb_vote_count' => $movieData['vote_count'] ?? $movie->tmdb_vote_count,
                'original_language' => $movieData['original_language'] ?? $movie->original_language,
                'runtime' => $movieData['runtime'] ?? $movie->runtime,
                'budget' => $movieData['budget'] ?? $movie->budget,
                'revenue' => $movieData['revenue'] ?? $movie->revenue,
                'production_companies' => $productionCompanies,
                'production_countries' => $productionCountries,
                'tagline' => $movieData['tagline'] ?? $movie->tagline,
                'where_to_watch' => $whereToWatch,
            ]);

            return $movie;
        } catch (\Exception $e) {
            $this->error("Error updating movie: " . $e->getMessage());
            return null;
        }
    }

    private function saveMovie($movieData, $defaultStatus)
    {
        try {
            // Get genres
            $genres = [];
            if (isset($movieData['genres']) && is_array($movieData['genres'])) {
                $genres = array_map(fn($g) => $g['name'], $movieData['genres']);
            }

            // Get cast (top 15 actors)
            $cast = [];
            if (isset($movieData['credits']['cast']) && is_array($movieData['credits']['cast'])) {
                $cast = array_slice(array_map(function($actor) {
                    return [
                        'id' => $actor['id'],
                        'name' => $actor['name'],
                        'character' => $actor['character'] ?? null,
                        'profile_path' => $actor['profile_path'] 
                            ? $this->imageBaseUrl . 'w185' . $actor['profile_path']
                            : null,
                    ];
                }, $movieData['credits']['cast']), 0, 15);
            }

            // Get crew (director, writer, etc)
            $crew = [];
            if (isset($movieData['credits']['crew']) && is_array($movieData['credits']['crew'])) {
                foreach ($movieData['credits']['crew'] as $member) {
                    if (in_array($member['job'], ['Director', 'Writer', 'Screenplay', 'Producer'])) {
                        $crew[] = [
                            'id' => $member['id'],
                            'name' => $member['name'],
                            'job' => $member['job'],
                            'department' => $member['department'] ?? null,
                        ];
                    }
                }
            }

            // Get all videos (trailers, teasers, etc)
            $videos = [];
            if (isset($movieData['videos']['results']) && is_array($movieData['videos']['results'])) {
                foreach ($movieData['videos']['results'] as $video) {
                    if ($video['site'] === 'YouTube') {
                        $videos[] = [
                            'key' => $video['key'],
                            'name' => $video['name'],
                            'type' => $video['type'],
                            'official' => $video['official'] ?? false,
                            'url' => "https://www.youtube.com/watch?v={$video['key']}",
                        ];
                    }
                }
            }

            // Get trailer URL (first official trailer)
            $trailerUrl = null;
            foreach ($videos as $video) {
                if ($video['type'] === 'Trailer' && $video['official']) {
                    $trailerUrl = $video['url'];
                    break;
                }
            }
            if (!$trailerUrl && count($videos) > 0) {
                $trailerUrl = $videos[0]['url'];
            }

            // Get images (backdrops and posters)
            $images = [
                'backdrops' => [],
                'posters' => []
            ];
            if (isset($movieData['all_images']['backdrops']) && is_array($movieData['all_images']['backdrops'])) {
                $images['backdrops'] = array_slice(array_map(function($img) {
                    return $this->imageBaseUrl . 'original' . $img['file_path'];
                }, $movieData['all_images']['backdrops']), 0, 10);
            }
            if (isset($movieData['all_images']['posters']) && is_array($movieData['all_images']['posters'])) {
                $images['posters'] = array_slice(array_map(function($img) {
                    return $this->imageBaseUrl . 'w500' . $img['file_path'];
                }, $movieData['all_images']['posters']), 0, 10);
            }

            // Production companies
            $productionCompanies = [];
            if (isset($movieData['production_companies']) && is_array($movieData['production_companies'])) {
                $productionCompanies = array_map(fn($c) => $c['name'], $movieData['production_companies']);
            }

            // Production countries
            $productionCountries = [];
            if (isset($movieData['production_countries']) && is_array($movieData['production_countries'])) {
                $productionCountries = array_map(fn($c) => $c['name'], $movieData['production_countries']);
            }

            // Determine status based on release date
            $releaseDate = $movieData['release_date'] ?? null;
            $status = $this->determineStatus($releaseDate, $defaultStatus);

            // Get "Where to Watch" using TMDB Watch Providers API
            $tmdbId = $movieData['id'] ?? null;
            $whereToWatch = $this->getWhereToWatch($tmdbId);

            $movie = Movie::create([
                'tmdb_id' => $tmdbId,
                'title' => $movieData['title'],
                'slug' => Str::slug($movieData['title']),
                'synopsis' => $movieData['overview'] ?? 'Sinopse não disponível',
                'release_date' => $releaseDate,
                'status' => $status,
                'genres' => $genres,
                'poster_url' => isset($movieData['poster_path']) 
                    ? $this->imageBaseUrl . 'w500' . $movieData['poster_path']
                    : null,
                'backdrop_url' => isset($movieData['backdrop_path'])
                    ? $this->imageBaseUrl . 'original' . $movieData['backdrop_path']
                    : null,
                'trailer_url' => $trailerUrl,
                'cast' => $cast,
                'crew' => $crew,
                'videos' => $videos,
                'images' => $images,
                'tmdb_rating' => $movieData['vote_average'] ?? null,
                'tmdb_vote_count' => $movieData['vote_count'] ?? null,
                'original_language' => $movieData['original_language'] ?? null,
                'runtime' => $movieData['runtime'] ?? null,
                'budget' => $movieData['budget'] ?? null,
                'revenue' => $movieData['revenue'] ?? null,
                'production_companies' => $productionCompanies,
                'production_countries' => $productionCountries,
                'tagline' => $movieData['tagline'] ?? null,
                'where_to_watch' => $whereToWatch,
            ]);

            return $movie;
        } catch (\Exception $e) {
            $this->error("Error saving movie: " . $e->getMessage());
            return null;
        }
    }

    private function determineStatus($releaseDate, $defaultStatus)
    {
        if (!$releaseDate) {
            return $defaultStatus;
        }

        try {
            $now = \Carbon\Carbon::now();
            $release = \Carbon\Carbon::parse($releaseDate);
            
            // If it's from the upcoming endpoint and is in the future, keep as upcoming
            if ($defaultStatus === 'upcoming' && $release->isFuture()) {
                return 'upcoming';
            }
            
            // Future release
            if ($release->isFuture()) {
                // More than 30 days in future = upcoming
                if ($release->diffInDays($now) > 30) {
                    return 'upcoming';
                }
                // Within 30 days = in_theaters (coming soon)
                return 'in_theaters';
            }
            
            // Released within last 180 days (6 months) = in_theaters
            if ($release->diffInDays($now) <= 180 && $release->isPast()) {
                return 'in_theaters';
            }
            
            // Released more than 180 days ago
            return 'released';
        } catch (\Exception $e) {
            return $defaultStatus;
        }
    }
}
