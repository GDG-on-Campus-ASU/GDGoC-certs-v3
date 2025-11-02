<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'content',
        'type',
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
        return $this->belongsTo(CertificateTemplate::class, 'original_template_id');
    }
}
