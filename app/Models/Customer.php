<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone_number',
        'location',
        'visiting_purpose',
        'created_by',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public static function normalizePhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 10) {
            return '255' . substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '255')) {
            $normalized = substr($cleaned, 0, 12);
        } elseif (strlen($cleaned) === 9) {
            $normalized = '255' . $cleaned;
        } else {
            $normalized = $cleaned ?: null;
        }

        return ($normalized && strlen($normalized) >= 12) ? $normalized : null;
    }

    public static function findOrCreateByPhone(string $phone, array $attributes = [], ?int $createdBy = null): ?self
    {
        $normalized = static::normalizePhoneNumber($phone);

        if (!$normalized) {
            return null;
        }

        $customer = static::where('phone_number', $normalized)->first();

        if ($customer) {
            $updates = [];

            foreach (['name', 'location', 'visiting_purpose'] as $field) {
                if (!empty($attributes[$field])) {
                    $updates[$field] = $attributes[$field];
                }
            }

            if ($updates) {
                $customer->update($updates);
            }

            return $customer;
        }

        return static::create([
            'phone_number' => $normalized,
            'name' => $attributes['name'] ?? null,
            'location' => $attributes['location'] ?? null,
            'visiting_purpose' => $attributes['visiting_purpose'] ?? null,
            'created_by' => $createdBy,
        ]);
    }
}
