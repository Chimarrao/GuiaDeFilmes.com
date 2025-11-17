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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->unique();
            $table->boolean('adult')->default(false);
            $table->json('also_known_as')->nullable();
            $table->text('biography')->nullable();
            $table->date('birthday')->nullable();
            $table->date('deathday')->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->string('homepage')->nullable();
            $table->string('imdb_id')->nullable();
            $table->string('known_for_department')->nullable();
            $table->string('name');
            $table->string('place_of_birth')->nullable();
            $table->decimal('popularity', 8, 3)->default(0);
            $table->string('profile_path')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('tmdb_id');
            $table->index('name');
            $table->index('popularity');
            $table->index('known_for_department');

            // Fulltext index
            $table->fullText(['name', 'biography']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
