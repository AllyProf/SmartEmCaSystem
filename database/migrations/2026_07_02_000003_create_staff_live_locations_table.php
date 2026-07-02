<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_live_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('accuracy')->nullable();
            $table->decimal('speed', 8, 3)->nullable(); // meters/sec
            $table->unsignedSmallInteger('heading')->nullable(); // degrees 0-359
            $table->string('travel_mode')->nullable(); // stationary|walking|motorcycle|driving
            $table->timestamp('captured_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_live_locations');
    }
};

