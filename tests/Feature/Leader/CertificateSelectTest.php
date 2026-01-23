<?php

namespace Tests\Feature\Leader;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CertificateSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_index_selects_specific_columns()
    {
        $user = User::factory()->create([
            'role' => 'leader',
            'org_name' => 'Test Org',
        ]);

        // Create a certificate with a large data payload
        Certificate::factory()->create([
            'user_id' => $user->id,
            'data' => array_fill(0, 1000, 'large payload data'), // Large JSON blob
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($user)
            ->get(route('dashboard.certificates.index'));

        $log = DB::getQueryLog();

        // Find the query that is fetching the data (not the count query)
        $query = collect($log)->pluck('query')->first(function ($q) {
            return ! str_contains($q, 'count(*)');
        });

        $this->assertNotNull($query, 'Data query not found in logs');

        // Assert that "data" is NOT in the select list
        // Note: We check that we are NOT selecting * (all) and explicitly NOT selecting data.
        // If it selects *, it selects data.
        // So we want to ensure we have a specific list of columns.

        $this->assertStringNotContainsString('select *', $query);
        $this->assertStringContainsString('"id"', $query);
        $this->assertStringContainsString('"recipient_name"', $query);

        // Verify "data" is NOT selected.
        // Note: this assertion might need adjustment if "data" appears in other parts of query,
        // but it shouldn't be in the select clause if we are explicit.
        // A safer check is to see if the model in the response has the data attribute loaded.

        $response->assertStatus(200);

        $certificates = $response->viewData('certificates');
        $firstCertificate = $certificates->first();

        // If we select specific columns, accessing 'data' should either return null (if not selected and no accessor)
        // or trigger a fresh query (if lazy loading protection is not on, but that's bad practice)
        // Actually, if it's not selected, it will be null in the model's attributes.

        $this->assertArrayNotHasKey('data', $firstCertificate->getAttributes());
    }
}
