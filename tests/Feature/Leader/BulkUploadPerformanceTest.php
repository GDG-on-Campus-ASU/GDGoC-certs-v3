<?php

namespace Tests\Feature\Leader;

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

    public function test_bulk_upload_does_not_have_n_plus_one_query_problem()
    {
        Queue::fake();

        $user = User::factory()->create();

        // Create templates
        $certTemplate = CertificateTemplate::factory()->create(['user_id' => $user->id]);
        $emailTemplate = EmailTemplate::factory()->create(['user_id' => $user->id]);

        // Create SMTP provider
        SmtpProvider::create([
            'user_id' => $user->id,
            'name' => 'Test SMTP',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user',
            'password' => 'password',
            'encryption' => 'tls',
            'from_address' => 'test@example.com',
            'from_name' => 'Test Sender',
        ]);

        // Create a CSV with 10 rows
        $csvHeader = "recipient_name,recipient_email,state,event_type,event_title,issue_date\n";
        $csvRow = "John Doe,john@example.com,attending,workshop,Test Workshop,2023-01-01\n";
        $csvContent = $csvHeader . str_repeat($csvRow, 10);

        $file = UploadedFile::fake()->createWithContent('certificates.csv', $csvContent);

        DB::enableQueryLog();

        $this->actingAs($user)
            ->post(route('dashboard.certificates.bulk.store'), [
                'certificate_template_id' => $certTemplate->id,
                'email_template_id' => $emailTemplate->id,
                'csv_file' => $file,
            ]);

        $log = DB::getQueryLog();

        // Filter queries to find the one fetching SMTP provider
        $smtpQueries = array_filter($log, function ($query) {
            // Loose match to cover different DB drivers/query variations
            return str_contains($query['query'], 'smtp_providers');
        });

        $count = count($smtpQueries);

        // We assert that the number of queries is at most 1.
        // Currently, it will be 1 (for the first check?) + 10 (inside loop) = 11?
        // Or if it's not cached, 10.
        // If cached on user model relation, maybe 1? No, `->first()` executes a new query.

        $this->assertLessThanOrEqual(1, $count, "N+1 query detected: {$count} SMTP provider queries found. Expected <= 1.");
    }
}
