<?php

namespace Tests\Unit\Security;

use App\Models\OidcSetting;
use Tests\TestCase;

class OidcSettingSecurityTest extends TestCase
{
    public function test_client_secret_is_hidden_in_serialization(): void
    {
        // Create a model instance with a secret
        $settings = new OidcSetting([
            'client_id' => 'test-client',
        ]);

        // We set the attribute directly to trigger the mutator
        $settings->client_secret = 'super-secret-value';

        // Check serialization
        $array = $settings->toArray();
        $json = $settings->toJson();

        // Verify client_secret is NOT present in the array/json
        $this->assertArrayNotHasKey('client_secret', $array, 'client_secret should be hidden in toArray()');
        $this->assertStringNotContainsString('client_secret', $json, 'client_secret should be hidden in toJson()');
        $this->assertStringNotContainsString('super-secret-value', $json, 'The secret value should not be exposed in JSON');
    }
}
