<?php

// updateMovies.php - Daily cron to fetch and update movies from OMDB API

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Movie;
use App\Models\Source;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

echo "Starting movie update process...\n";

$omdbApiKey = env('OMDB_API_KEY');
$source = Source::firstOrCreate(
    ['name' => 'OMDB API'],
    ['url' => 'https://www.omdbapi.com/']
);

echo "Fetching movies from OMDB...\n";

// For demo, fetch a few movies. In real, fetch upcoming or search.
$moviesToFetch = ['tt0111161', 'tt0068646', 'tt0071562']; // Example IMDB IDs

foreach ($moviesToFetch as $imdbId) {
    $response = Http::get("http://www.omdbapi.com/", [
        'apikey' => $omdbApiKey,
        'i' => $imdbId,
    ]);

    if ($response->successful()) {
        $data = $response->json();
        echo "Fetched data for: " . $data['Title'] . "\n";

        $releaseDate = Carbon::parse($data['Released']);
        $today = Carbon::today();

        $status = 'upcoming';
        if ($releaseDate->lte($today)) {
            if ($releaseDate->gte($today->subDays(30))) {
                $status = 'in_theaters';
            } else {
                $status = 'released';
            }
        }

        $genres = explode(', ', $data['Genre']);

        // Generate synopsis using Gemini if needed
        $synopsis = $data['Plot'];
        if (empty($synopsis)) {
            $synopsis = generateSynopsisWithAI($data['Title']);
        }

        Movie::updateOrCreate(
            ['slug' => Str::slug($data['Title'])],
            [
                'title' => $data['Title'],
                'synopsis' => $synopsis,
                'release_date' => $releaseDate,
                'status' => $status,
                'genres' => $genres,
                'poster_url' => $data['Poster'],
                'backdrop_url' => null, // OMDB doesn't have backdrop
                'trailer_url' => null, // Would need another API
            ]
        );

        echo "Updated movie: " . $data['Title'] . " with status: " . $status . "\n";
    } else {
        echo "Failed to fetch data for IMDB ID: " . $imdbId . "\n";
    }
}

$source->update(['last_fetched' => now()]);
echo "Updated last_fetched for source.\n";
echo "Movie update process completed.\n";

function generateSynopsisWithAI($title) {
    // Integrate Gemini AI
    $apiKey = env('GEMINI_API_KEY');
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $response = Http::post($url, [
        'contents' => [
            [
                'parts' => [
                    ['text' => "Generate a short synopsis for the movie titled '$title'."]
                ]
            ]
        ]
    ]);

    if ($response->successful()) {
        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Synopsis not available.';
    }

    return 'Synopsis not available.';
}