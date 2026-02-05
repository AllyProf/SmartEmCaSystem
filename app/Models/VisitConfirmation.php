<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitConfirmation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function attendees()
    {
        return $this->hasMany(VisitAttendee::class);
    }
}
