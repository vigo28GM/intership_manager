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
        // First, drop the procedure if it exists
        DB::statement('DROP PROCEDURE IF EXISTS create_internship_application');
        
        // Read and execute the procedure creation SQL
        $procedurePath = database_path('procedures/create_internship_application.sql');
        $procedureSql = file_get_contents($procedurePath);
        
        // Remove the DROP statement from the file content since we already executed it
        $procedureSql = preg_replace('/^DROP PROCEDURE IF EXISTS create_internship_application;\s*/m', '', $procedureSql);
        
        DB::unprepared($procedureSql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP PROCEDURE IF EXISTS create_internship_application');
    }
};
