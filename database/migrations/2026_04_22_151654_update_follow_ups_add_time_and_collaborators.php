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
            $table->time('visit_time')->nullable()->after('visit_date');
            $table->time('next_follow_up_time')->nullable()->after('next_follow_up_date');
            $table->time('reminder_time')->nullable()->after('reminder_date');
            $table->json('collaborators')->nullable()->after('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropColumn(['visit_time', 'next_follow_up_time', 'reminder_time', 'collaborators']);
        });
    }
};
