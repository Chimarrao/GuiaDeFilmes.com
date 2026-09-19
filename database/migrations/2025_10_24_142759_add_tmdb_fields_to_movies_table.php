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
        Schema::table('movies', function (Blueprint $table) {
            $table->json('cast')->nullable()->after('trailer_url');
            $table->json('crew')->nullable()->after('cast');
            $table->json('videos')->nullable()->after('crew');
            $table->json('images')->nullable()->after('videos');
            $table->decimal('tmdb_rating', 3, 1)->nullable()->after('images');
            $table->integer('tmdb_vote_count')->nullable()->after('tmdb_rating');
            $table->string('original_language', 10)->nullable()->after('tmdb_vote_count');
            $table->integer('runtime')->nullable()->after('original_language');
            $table->decimal('budget', 15, 2)->nullable()->after('runtime');
            $table->decimal('revenue', 15, 2)->nullable()->after('budget');
            $table->json('production_companies')->nullable()->after('revenue');
            $table->json('production_countries')->nullable()->after('production_companies');
            $table->string('tagline')->nullable()->after('production_countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn([
                'cast', 'crew', 'videos', 'images', 'tmdb_rating', 'tmdb_vote_count',
                'original_language', 'runtime', 'budget', 'revenue', 
                'production_companies', 'production_countries', 'tagline'
            ]);
        });
    }
};
