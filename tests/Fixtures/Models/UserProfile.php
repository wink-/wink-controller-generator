<?php

namespace Wink\ControllerGenerator\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'bio',
        'avatar',
        'timezone',
        'locale',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the age attribute.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }
}