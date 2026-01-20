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

    public function test_certificate_index_selects_only_necessary_columns()
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

        // Find the query that fetches certificates
        // We look for the main select query, ignoring the count(*) query for pagination
        $certificateQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'from "certificates"')
                && ! str_contains($query['query'], 'count(*)');
        });

        // Fallback for different quote styles (SQLite/Postgres vs MySQL)
        if (! $certificateQuery) {
            $certificateQuery = collect($queries)->first(function ($query) {
                return str_contains($query['query'], 'from `certificates`')
                    && ! str_contains($query['query'], 'count(*)');
            });
        }

        $this->assertNotNull($certificateQuery, 'Certificate query not found in: '.json_encode($queries));

        $sql = $certificateQuery['query'];

        // Assert that we are strictly selecting columns, not selecting *
        $this->assertStringNotContainsString('select *', $sql);

        // Assert specific columns are present
        $this->assertStringContainsString('recipient_name', $sql);
        $this->assertStringContainsString('id', $sql);

        // Assert the heavy 'data' column is NOT present
        // We use a loose check because quoting might vary, but 'data' is the column name.
        // We want to ensure 'data' is not selected.
        // We must be careful that 'data' isn't part of some other word, but in this schema it's just 'data'.
        // To be safe, we can check for '"data"' or '`data`' or ' data ' or ', data'

        // Given the goal is optimization, finding 'data' anywhere in the select clause is bad.
        // Since we aren't filtering by it, it shouldn't be in the query at all.
        $this->assertStringNotContainsString('data', $sql, "The query should not select the 'data' column. Query: $sql");
    }
}
