<?php

namespace Tests\Unit\Security;

use App\Models\OidcSetting;
use Tests\TestCase;

class ModelSerializationSecurityTest extends TestCase
{
    /**
     * Test that critical secrets are hidden from serialization.
     */
    public function test_client_secret_is_hidden_from_serialization()
    {
        $settings = new OidcSetting([
            'client_id' => 'test-client-id',
            'client_secret' => 'very-secret-value',
            'login_endpoint_url' => 'https://example.com/login',
            'token_endpoint_url' => 'https://example.com/token',
            'userinfo_endpoint_url' => 'https://example.com/userinfo',
        ]);

        // When we convert the model to an array (or JSON)
        $array = $settings->toArray();
        $json = $settings->toJson();

        // The secret should NOT be present
        $this->assertArrayNotHasKey('client_secret', $array, 'client_secret should be hidden from array serialization');
        $this->assertStringNotContainsString('very-secret-value', $json, 'client_secret value should not be in JSON');
    }

    /**
     * Test that SMTP password is hidden from serialization.
     */
    public function test_smtp_password_is_hidden_from_serialization()
    {
        $provider = new \App\Models\SmtpProvider([
            'name' => 'Test Provider',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user',
            'password' => 'secret-smtp-password',
            'encryption' => 'tls',
            'from_address' => 'test@example.com',
            'from_name' => 'Test',
        ]);

        $array = $provider->toArray();
        $json = $provider->toJson();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertStringNotContainsString('secret-smtp-password', $json);
    }
}
