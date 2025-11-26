<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'certificate_template_id',
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
        'status',
        'revoked_at',
        'revocation_reason',
        'file_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'data' => 'array',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }
}
