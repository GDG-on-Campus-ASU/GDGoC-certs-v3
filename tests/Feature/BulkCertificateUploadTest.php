<?php

namespace Tests\Feature;

use App\Jobs\ProcessCertificateRow;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Models\SmtpProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BulkCertificateUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_upload_dispatches_jobs_and_is_performant()
    {
        Queue::fake();

        $user = User::factory()->create(['name' => 'Test User', 'org_name' => 'Test Org']);

        // Create templates
        $certTemplate = CertificateTemplate::factory()->create(['user_id' => $user->id, 'name' => 'Cert Template']);
        $emailTemplate = EmailTemplate::factory()->create(['user_id' => $user->id, 'name' => 'Email Template']);

        // Create an SMTP provider for the user
        $smtpProvider = SmtpProvider::forceCreate([
            'user_id' => $user->id,
            'name' => 'Test Provider',
            'host' => 'smtp.mailtrap.io',
            'port' => 2525,
            'username' => 'testuser',
            'password' => 'testpass',
            'encryption' => 'tls',
            'from_address' => 'hello@example.com',
            'from_name' => 'Test Sender',
        ]);

        // Create a CSV with 5 rows
        $csvContent = "recipient_name,recipient_email,state,event_type,event_title,issue_date\n";
        for ($i = 0; $i < 5; $i++) {
            $csvContent .= "User $i,user$i@example.com,attending,workshop,Event $i,2023-01-01\n";
        }

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        \Illuminate\Support\Facades\DB::enableQueryLog();

        $response = $this->actingAs($user)->post(route('dashboard.certificates.bulk.store'), [
            'certificate_template_id' => $certTemplate->id,
            'email_template_id' => $emailTemplate->id,
            'csv_file' => $file,
        ]);

        // With 5 rows, we currently expect:
        // 1. User retrieval (auth)
        // 2. Certificate Template retrieval
        // 3. Email Template retrieval
        // 4. SMTP Provider retrieval * 5 (N+1)
        // Total should be around 3 + 5 = 8 queries (plus potentially others)
        // dump(\Illuminate\Support\Facades\DB::getQueryLog());

        $queryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
        // Post-optimization:
        // 1. User retrieval (auth check inside actingAs/controller)
        // 2. Certificate Template retrieval
        // 3. Email Template retrieval
        // 4. SMTP Provider retrieval (now once outside loop)
        // Total should be significantly less than (3 + 5 rows) = 8.
        // It should be around 4 or 5.
        $this->assertLessThan(8, $queryCount, "Query count is $queryCount, expected < 8 (N+1 fixed)");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert jobs were dispatched
        Queue::assertPushed(ProcessCertificateRow::class, 5);

        // We can't easily assert query count here retrospectively without DB::enableQueryLog() around the controller action
        // but checking the logic is sound is the first step.
    }
}
