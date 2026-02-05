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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('phone_number', 20);
            $table->text('message');
            $table->enum('sms_type', ['engagement', 'holiday', 'custom', 'other'])->default('custom');
            $table->string('status')->nullable();
            $table->text('api_response')->nullable();
            $table->string('message_id')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
