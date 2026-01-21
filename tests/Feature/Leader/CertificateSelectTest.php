<?php

namespace Tests\Feature\Leader;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CertificateSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_optimizes_selected_columns(): void
    {
        $user = User::factory()->create();
        $template = CertificateTemplate::factory()->create(['user_id' => $user->id]);

        Certificate::factory()->create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
            'data' => ['large' => 'payload'],
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('dashboard.certificates.index'));

        $response->assertStatus(200);

        $queries = DB::getQueryLog();

        // Find the main query on certificates table (ignoring count query for pagination)
        $certificateQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'select') &&
                   str_contains($query['query'], 'certificates') &&
                   ! str_contains($query['query'], 'count(*)');
        });

        $this->assertNotNull($certificateQuery, 'Certificate select query not found');

        $sql = $certificateQuery['query'];

        // Assert that we are selecting specific columns explicitly
        $this->assertStringContainsString('recipient_name', $sql);
        $this->assertStringContainsString('event_title', $sql);

        // Assert that we are NOT selecting *
        $this->assertStringNotContainsString(' * ', $sql);
        $this->assertStringNotContainsString('certificates.*', $sql);
        $this->assertStringNotContainsString('"certificates".*', $sql);
    }
}
