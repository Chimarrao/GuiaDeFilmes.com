<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Stored procedures só existem no MySQL; SQLite (usado em dev local) não suporta.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Remove a procedure se já existir
        DB::unprepared('DROP PROCEDURE IF EXISTS fix_genres');

        // Cria a stored procedure para corrigir gêneros
        DB::unprepared("
            CREATE PROCEDURE fix_genres(IN find_str VARCHAR(20), IN replace_str VARCHAR(10))
            BEGIN
              DECLARE rows_affected INT DEFAULT 1;

              WHILE rows_affected > 0 DO
                UPDATE movies
                SET genres = REPLACE(genres, find_str, replace_str)
                WHERE genres LIKE CONCAT('%', find_str, '%')
                ORDER BY id
                LIMIT 1;
                SET rows_affected = ROW_COUNT();
              END WHILE;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP PROCEDURE IF EXISTS fix_genres');
        }
    }
};
