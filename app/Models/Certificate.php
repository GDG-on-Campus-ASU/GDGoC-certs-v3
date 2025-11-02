<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'unique_id',
        'recipient_name',
        'recipient_email',
        'state',
        'event_type',
        'event_title',
        'issue_date',
        'issuer_name',
        'org_name',
        'data',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
