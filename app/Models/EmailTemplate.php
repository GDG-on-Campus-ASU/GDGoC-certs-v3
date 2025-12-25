<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'subject',
        'body',
        'is_global',
        'original_template_id',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originalTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'original_template_id');
    }
}
