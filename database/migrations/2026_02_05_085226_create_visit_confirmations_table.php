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
        Schema::create('visit_confirmations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['single', 'group']);
            
            // Common / Single Form Fields
            $table->date('visit_date');
            $table->string('location')->nullable();
            
            // Customer Info (Single Form)
            $table->string('customer_name')->nullable(); // Customer / Organization Name
            $table->string('contact_person')->nullable(); // Contact Person / Title
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            
            // EmCa Rep Info (Single Form)
            $table->string('representative_name')->nullable();
            $table->string('representative_title')->nullable();
            
            // Details (Single Form)
            $table->text('purpose')->nullable(); // Brief Description
            $table->text('feedback')->nullable(); // Comments / Suggestions
            $table->enum('satisfaction_level', ['Very Satisfied', 'Satisfied', 'Average', 'Unsatisfied'])->nullable();
            
            // Signatures (Single Form)
            $table->string('customer_signature_path')->nullable();
            $table->string('representative_signature_path')->nullable();
            
            // Group Form Specifics
            $table->string('subject')->nullable(); // For the Attendance Sheet subject line
            
            // Metadata
            $table->string('created_by_email')->nullable(); // The staff email used to access
            $table->timestamps();
        });

        Schema::create('visit_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_confirmation_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('institution')->nullable();
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('signature_path')->nullable(); // Individual signature for group attendees
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_confirmations');
    }
};
