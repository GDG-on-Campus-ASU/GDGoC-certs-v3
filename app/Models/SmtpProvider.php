<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmtpProvider extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'is_global',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the password attribute, decrypting it.
     */
    public function getPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Set the password attribute, encrypting it.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? encrypt($value) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
