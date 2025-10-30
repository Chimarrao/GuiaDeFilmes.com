<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use App\Models\MovieAI;
use Illuminate\Support\Facades\Http;

class GenerateMovieAIContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:movie-ai {--movie_id= : Specific movie ID to generate content for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AI content for movies using Gemini API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            $this->error('GEMINI API key not found in environment variables');
            return 1;
        }

        // Get movies without AI content or specific movie
        $movieId = $this->option('movie_id');
        
        if ($movieId) {
            $movies = Movie::where('id', $movieId)->get();
        } else {
            $movies = Movie::whereDoesntHave('aiContent')->get();
        }

        if ($movies->isEmpty()) {
            $this->info('No movies found to generate AI content for.');
            return 0;
        }

        $this->info("Generating AI content for {$movies->count()} movie(s)...");

        foreach ($movies as $movie) {
            try {
                $this->info("Processing: {$movie->title}");

                // Prepare the prompt for Gemini
                $prompt = $this->buildPrompt($movie);

                // Call Gemini API
                $response = Http::withoutVerifying()->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];
                        
                        // Parse the AI response
                        $parsedContent = $this->parseAIResponse($aiResponse);

                        // Create or update AI content
                        MovieAI::updateOrCreate(
                            ['movie_id' => $movie->id],
                            [
                                'ai_synopsis' => $parsedContent['synopsis'],
                                'seo_description' => $parsedContent['seo'],
                                'reviews_summary' => $parsedContent['reviews'],
                            ]
                        );

                        $this->info("✅ AI content generated for: {$movie->title}");
                    }
                } else {
                    $this->error("Failed to generate AI content for {$movie->title}");
                    $this->error("Status: " . $response->status());
                    $this->error("Response: " . $response->body());
                }

                // Add delay to respect API limits
                sleep(2);

            } catch (\Exception $e) {
                $this->error("Error processing {$movie->title}: " . $e->getMessage());
            }
        }

        $this->info("✅ Finished generating AI content!");
        return 0;
    }

    /**
     * Build prompt for Gemini API
     */
    private function buildPrompt($movie)
    {
        $genres = is_array($movie->genres) ? implode(', ', $movie->genres) : '';
        
        return <<<PROMPT
Você é um especialista em cinema e crítico de filmes. Preciso que você gere conteúdo sobre o seguinte filme:

Título: {$movie->title}
Gêneros: {$genres}
Data de lançamento: {$movie->release_date}
Sinopse original: {$movie->synopsis}

Por favor, gere o seguinte conteúdo em formato JSON:

1. Uma nova sinopse mais atrativa e envolvente (máximo 300 caracteres)
2. Uma descrição SEO otimizada (máximo 160 caracteres)
3. Um resumo de críticas e recepção do filme (máximo 500 caracteres)

Retorne APENAS um JSON válido no seguinte formato, sem markdown ou formatação adicional:
{
  "synopsis": "sinopse aqui",
  "seo": "descrição seo aqui",
  "reviews": "resumo das críticas aqui"
}
PROMPT;
    }

    /**
     * Parse AI response to extract content
     */
    private function parseAIResponse($response)
    {
        // Try to extract JSON from response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonString, true);
            
            if ($parsed && isset($parsed['synopsis'], $parsed['seo'], $parsed['reviews'])) {
                return [
                    'synopsis' => substr($parsed['synopsis'], 0, 300),
                    'seo' => substr($parsed['seo'], 0, 160),
                    'reviews' => substr($parsed['reviews'], 0, 500),
                ];
            }
        }

        // Fallback if parsing fails
        return [
            'synopsis' => 'Content generation in progress...',
            'seo' => 'Discover this amazing movie.',
            'reviews' => 'Reviews are being compiled.',
        ];
    }
}
