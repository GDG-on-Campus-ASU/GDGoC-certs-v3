<?php

namespace App\Models;

// Email verification disabled - users are created manually by superadmin
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'org_name',
        'role',
        'status',
        'termination_reason',
        'oauth_provider',
        'oauth_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's Gravatar URL.
     */
    public function gravatarUrl(int $size = 80): string
    {
        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }

    /**
     * Get the certificate templates that belong to the user.
     */
    public function certificateTemplates()
    {
        return $this->hasMany(CertificateTemplate::class);
    }

    /**
     * Get the email templates that belong to the user.
     */
    public function emailTemplates()
    {
        return $this->hasMany(EmailTemplate::class);
    }

    /**
     * Get the certificates that belong to the user.
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the SMTP providers that belong to the user.
     */
    public function smtpProviders()
    {
        return $this->hasMany(SmtpProvider::class);
    }
}
