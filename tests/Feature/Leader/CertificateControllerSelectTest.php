<?php

namespace Tests\Feature\Leader;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateControllerSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_optimizes_selected_columns()
    {
        $user = User::factory()->create();
        $template = CertificateTemplate::factory()->create(['user_id' => $user->id]);

        Certificate::factory()->create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
            'recipient_name' => 'Test Recipient',
            'data' => ['some' => 'big data'],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.certificates.index'));

        $response->assertStatus(200);

        $certificates = $response->viewData('certificates');
        $this->assertCount(1, $certificates);

        $certificate = $certificates->first();

        // Verify required columns are present
        $this->assertEquals('Test Recipient', $certificate->recipient_name);
        $this->assertNotNull($certificate->id);

        // Verify 'data' column is NOT loaded (optimization)
        // usage of array_key_exists on getAttributes ensures we are checking the loaded data, not the accessor
        $this->assertFalse(array_key_exists('data', $certificate->getAttributes()), 'The data column should not be loaded for performance.');

        // Verify 'file_path' column is NOT loaded
        $this->assertFalse(array_key_exists('file_path', $certificate->getAttributes()), 'The file_path column should not be loaded for performance.');
    }
}
