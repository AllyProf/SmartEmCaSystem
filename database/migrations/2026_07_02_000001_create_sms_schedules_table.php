<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('send_to'); // single|selected|all
            $table->text('message_template');
            $table->string('sms_type')->default('custom');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('scheduled'); // scheduled|cancelled|completed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_schedules');
    }
};

