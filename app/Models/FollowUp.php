<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    protected $fillable = [
        'customer_id',
        'visit_date',
        'visit_purpose',
        'notes',
        'status',
        'next_follow_up_date',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'next_follow_up_date' => 'date',
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
