<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicCertificateShowPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_show_query_is_optimized()
    {
        $user = User::factory()->create();
        $template = CertificateTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'content' => '<h1>Certificate for {Recipient_Name}</h1>',
            'type' => 'blade',
        ]);

        // Create a certificate with a large data payload
        $largeData = array_fill(0, 1000, 'Some large string to make the payload heavy');

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
            'unique_id' => 'test-optimization-id',
            'recipient_name' => 'Jane Doe',
            'recipient_email' => 'jane@example.com',
            'state' => 'attending',
            'event_type' => 'workshop',
            'event_title' => 'Optimization Workshop',
            'issue_date' => now(),
            'issuer_name' => 'Bolt',
            'org_name' => 'Speed Corp',
            'status' => 'issued',
            'data' => $largeData,
        ]);

        DB::enableQueryLog();

        $response = $this->get(route('public.certificate.show', $certificate->unique_id));

        $queries = DB::getQueryLog();

        $response->assertOk();
        $response->assertSee('Jane Doe');
        $response->assertSee('Optimization Workshop');

        // Analyze the query
        $certificateQuery = collect($queries)->first(function ($query) {
            $sql = $query['query'];
            return str_contains($sql, 'select') &&
                   (str_contains($sql, 'from "certificates"') || str_contains($sql, 'from `certificates`') || str_contains($sql, 'from certificates'));
        });

        $this->assertNotNull($certificateQuery, 'Certificate query not found');

        $this->assertStringNotContainsString('*', $certificateQuery['query'], 'Query should not select all columns');
        $this->assertStringContainsString('recipient_name', $certificateQuery['query'], 'Query should select recipient_name');
    }
}
