<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordOtpReset extends Model
{
    protected $fillable = [
        'phone',
        'otp',
        'expires_at',
        'is_verified',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_verified' => 'boolean',
    ];
}
