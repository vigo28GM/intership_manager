<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: Creating triggers requires SUPER or SYSTEM_VARIABLES_ADMIN privilege.
     * If this migration fails, you can manually create the trigger by running
     * the SQL file: database/triggers/log_application_created.sql
     * 
     * Example:
     * mysql -u root -p database_name < database/triggers/log_application_created.sql
     */
    public function up(): void
    {
        try {
            // First, drop the trigger if it exists
            DB::statement('DROP TRIGGER IF EXISTS log_application_created');
            
            // Read and execute the trigger creation SQL
            $triggerPath = database_path('triggers/log_application_created.sql');
            $triggerSql = file_get_contents($triggerPath);
            
            // Remove the DROP statement from the file content since we already executed it
            $triggerSql = preg_replace('/^DROP TRIGGER IF EXISTS log_application_created;\s*/m', '', $triggerSql);
            
            DB::unprepared($triggerSql);
            
            echo "\n✓ Trigger 'log_application_created' created successfully.\n";
        } catch (\Exception $e) {
            Log::warning('Failed to create trigger log_application_created: ' . $e->getMessage());
            echo "\n⚠ Warning: Could not create trigger. Manual creation required.\n";
            echo "  Run: mysql -u root -p intership_manager < database/triggers/log_application_created.sql\n\n";
            
            // Don't fail the migration - application-level logging still works
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP TRIGGER IF EXISTS log_application_created');
        } catch (\Exception $e) {
            Log::warning('Failed to drop trigger log_application_created: ' . $e->getMessage());
        }
    }
};
