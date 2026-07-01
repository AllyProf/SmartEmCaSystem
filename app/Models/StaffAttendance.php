<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    protected $fillable = [
        'user_id',
        'signed_in_at',
        'signed_out_at',
        'latitude_in',
        'longitude_in',
        'latitude_out',
        'longitude_out',
        'verification_type_in',
        'verification_type_out',
        'location_verified_in',
        'location_verified_out',
        'photo_in',
        'photo_out',
        'gps_flagged_in',
        'gps_flagged_out',
        'gps_flags',
        'path_trace',
        'is_late',
        'is_early_out',
        'overtime_minutes',
        'auto_signed_out',
    ];

    protected $casts = [
        'signed_in_at' => 'datetime',
        'signed_out_at' => 'datetime',
        'location_verified_in' => 'boolean',
        'location_verified_out' => 'boolean',
        'gps_flagged_in' => 'boolean',
        'gps_flagged_out' => 'boolean',
        'gps_flags' => 'array',
        'path_trace' => 'array',
        'is_late' => 'boolean',
        'is_early_out' => 'boolean',
        'auto_signed_out' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photoInUrl(): ?string
    {
        return $this->photo_in ? asset('storage/' . ltrim($this->photo_in, '/')) : null;
    }

    public function workingMinutes(): ?int
    {
        if (!$this->signed_out_at) {
            return null;
        }

        return (int) $this->signed_in_at->diffInMinutes($this->signed_out_at);
    }
}
