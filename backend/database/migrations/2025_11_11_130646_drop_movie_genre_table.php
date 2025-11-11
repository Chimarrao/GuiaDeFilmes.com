<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('movie_genre');
        
        // Também remover o índice composto que foi criado
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex('idx_movies_vote_popularity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não precisa reverter, mantém as tabelas removidas
    }
};
