<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Models\SmtpProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BulkUploadPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_upload_does_not_have_n_plus_one_query_on_smtp_provider(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'org_name' => 'Test Org',
        ]);

        // Create SMTP provider
        SmtpProvider::create([
            'user_id' => $user->id,
            'name' => 'Test SMTP',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user',
            'password' => 'pass',
            'encryption' => 'tls',
            'from_address' => 'test@example.com',
            'from_name' => 'Test',
            'is_global' => false,
        ]);

        // Create templates
        $certTemplate = CertificateTemplate::factory()->create(['user_id' => $user->id]);
        $emailTemplate = EmailTemplate::factory()->create(['user_id' => $user->id]);

        // Create CSV content with 20 rows
        $header = ['recipient_name', 'recipient_email', 'state', 'event_type', 'event_title', 'issue_date'];
        $rows = [];
        $rows[] = $header;
        for ($i = 0; $i < 20; $i++) {
            $rows[] = ["User $i", "user$i@example.com", 'attending', 'workshop', 'Event Title', '2023-01-01'];
        }

        $content = '';
        foreach ($rows as $row) {
            $content .= implode(',', $row)."\n";
        }

        $file = UploadedFile::fake()->createWithContent('certificates.csv', $content);

        DB::enableQueryLog();

        $response = $this->actingAs($user)->post(route('dashboard.certificates.bulk.store'), [
            'certificate_template_id' => $certTemplate->id,
            'email_template_id' => $emailTemplate->id,
            'csv_file' => $file,
        ]);

        $queries = DB::getQueryLog();

        // Filter queries to find those selecting from smtp_providers
        $smtpQueries = array_filter($queries, function ($query) {
            return str_contains($query['query'], 'select') && str_contains($query['query'], 'smtp_providers');
        });

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // We print the count to verify
        // dump("SMTP Queries count: " . count($smtpQueries));

        // In the unoptimized state, this should be 20.
        // We want to assert that it is optimized (<= 1).
        // So this test will FAIL initially.
        $this->assertLessThanOrEqual(1, count($smtpQueries), 'Detected N+1 queries on smtp_providers table. Count: '.count($smtpQueries));
    }
}
