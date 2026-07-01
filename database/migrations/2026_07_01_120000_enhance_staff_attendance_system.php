<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'sign_pin')) {
                $table->string('sign_pin')->nullable()->after('password');
            }
        });

        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->string('photo_in')->nullable()->after('location_verified_in');
            $table->string('photo_out')->nullable()->after('location_verified_out');
            $table->boolean('gps_flagged_in')->default(false)->after('photo_in');
            $table->boolean('gps_flagged_out')->default(false)->after('photo_out');
            $table->json('gps_flags')->nullable()->after('gps_flagged_out');
            $table->json('path_trace')->nullable()->after('gps_flags');
            $table->boolean('is_late')->default(false)->after('path_trace');
            $table->boolean('is_early_out')->default(false)->after('is_late');
            $table->unsignedInteger('overtime_minutes')->default(0)->after('is_early_out');
            $table->boolean('auto_signed_out')->default(false)->after('overtime_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'photo_in', 'photo_out', 'gps_flagged_in', 'gps_flagged_out',
                'gps_flags', 'path_trace', 'is_late', 'is_early_out',
                'overtime_minutes', 'auto_signed_out',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sign_pin')) {
                $table->dropColumn('sign_pin');
            }
        });
    }
};
