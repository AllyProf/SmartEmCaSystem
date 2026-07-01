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
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->text('reminder_message')->nullable()->after('reminder_time');
        });
        
        // Change enum to varchar for more flexible statuses
        DB::statement("ALTER TABLE follow_ups MODIFY COLUMN status VARCHAR(255) DEFAULT 'Waiting for next call'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropColumn('reminder_message');
        });
        
        // Revert back to original enum if needed
        DB::statement("ALTER TABLE follow_ups MODIFY COLUMN status ENUM('pending','completed','cancelled') DEFAULT 'pending'");
    }
};
