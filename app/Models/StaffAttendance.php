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
    ];

    protected $casts = [
        'signed_in_at' => 'datetime',
        'signed_out_at' => 'datetime',
        'location_verified_in' => 'boolean',
        'location_verified_out' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }}
