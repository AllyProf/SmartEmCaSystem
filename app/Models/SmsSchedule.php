<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsSchedule extends Model
{
    protected $fillable = [
        'send_to',
        'message_template',
        'sms_type',
        'scheduled_at',
        'status',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'schedule_id');
    }
}

