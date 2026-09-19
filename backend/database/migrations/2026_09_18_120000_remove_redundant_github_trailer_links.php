<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove o trailer baixado do GitHub de filmes que já têm trailer do YouTube —
     * nesses casos o download foi redundante, o YouTube já cobre a necessidade.
     */
    public function up(): void
    {
        $affected = DB::table('movies')
            ->whereNotNull('trailer_url')
            ->where('trailer_url', '!=', '')
            ->whereNotNull('imdb_trailer_url')
            ->where('imdb_trailer_url', 'like', '%github%')
            ->update(['imdb_trailer_url' => null]);
    }

    /**
     * Não reversível: os valores removidos não são recuperáveis.
     */
    public function down(): void
    {
        
    }
};
