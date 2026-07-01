<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    protected $fillable = [
        'customer_id',
        'visit_date',
        'visit_time',
        'visit_purpose',
        'notes',
        'status',
        'next_follow_up_date',
        'next_follow_up_time',
        'reminder_date',
        'reminder_time',
        'reminder_message',
        'reminder_sent_at',
        'remind_via',
        'assigned_to',
        'collaborators',
        'created_by',
    ];

    protected $casts = [
        'visit_date'          => 'date',
        'next_follow_up_date' => 'date',
        'reminder_date'       => 'date',
        'reminder_sent_at'    => 'datetime',
        'collaborators'       => 'array',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
