<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OidcSetting extends Model
{
    protected $fillable = [
        'client_id',
        'client_secret',
        'scope',
        'login_endpoint_url',
        'token_endpoint_url',
        'userinfo_endpoint_url',
        'token_validation_endpoint_url',
        'end_session_endpoint_url',
        'identity_key',
        'link_existing_users',
        'create_new_users',
        'redirect_on_expiry',
    ];

    protected $casts = [
        'link_existing_users' => 'boolean',
        'create_new_users' => 'boolean',
        'redirect_on_expiry' => 'boolean',
    ];

    protected $hidden = [
        'client_secret',
    ];

    /**
     * Get the client_secret attribute, decrypting it.
     */
    public function getClientSecretAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Set the client_secret attribute, encrypting it.
     */
    public function setClientSecretAttribute($value)
    {
        $this->attributes['client_secret'] = $value ? encrypt($value) : null;
    }

    /**
     * Check if OIDC is properly configured with all required settings.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->client_id)
            && ! empty($this->client_secret)
            && ! empty($this->login_endpoint_url)
            && ! empty($this->token_endpoint_url)
            && ! empty($this->userinfo_endpoint_url);
    }

    /**
     * Get the configured OIDC settings or null if not configured.
     */
    public static function getConfigured(): ?self
    {
        $settings = self::first();

        if ($settings && $settings->isConfigured()) {
            return $settings;
        }

        return null;
    }
}
