<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movie_orderings', function (Blueprint $table) {
            $table->id();
            $table->json('in_theaters')->nullable()->comment('Ordem dos filmes Em Cartaz');
            $table->json('upcoming')->nullable()->comment('Ordem dos filmes Próximas Estreias');
            $table->json('released')->nullable()->comment('Ordem dos filmes já lançados');
            $table->timestamps();
        });

        // Inserir registro inicial
        DB::table('movie_orderings')->insert([
            'in_theaters' => json_encode([]),
            'upcoming' => json_encode([]),
            'released' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_orderings');
    }
};
