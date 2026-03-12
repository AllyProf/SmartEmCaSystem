<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            // When to send the reminder (date only)
            $table->date('reminder_date')->nullable()->after('next_follow_up_date');
            // Timestamp of when the reminder SMS was actually sent
            $table->timestamp('reminder_sent_at')->nullable()->after('reminder_date');
            // Who receives the SMS: 'assigned_user', 'customer', 'both'
            $table->string('remind_via')->default('assigned_user')->after('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropColumn(['reminder_date', 'reminder_sent_at', 'remind_via']);
        });
    }
};
