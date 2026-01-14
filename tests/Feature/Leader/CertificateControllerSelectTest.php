<?php

namespace Tests\Feature\Leader;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CertificateControllerSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_optimizes_selected_columns(): void
    {
        $user = User::factory()->create(['role' => 'leader']);

        // Create a certificate with large data
        Certificate::factory()->create([
            'user_id' => $user->id,
            'data' => array_fill(0, 1000, 'large_data_block'), // Simulate large JSON
            'file_path' => str_repeat('a', 255),
            'unique_id' => 'test-unique-id',
        ]);

        // Enable query logging
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('dashboard.certificates.index'));

        $response->assertOk();

        // Get the executed queries
        $queries = DB::getQueryLog();

        // Find the query that fetches certificates
        // It will look something like: select * from "certificates" where "certificates"."user_id" = ? and "certificates"."user_id" is not null order by "created_at" desc limit 20 offset 0
        $certificateQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'from "certificates"') && !str_contains($query['query'], 'count(*)');
        });

        $this->assertNotNull($certificateQuery, 'Certificate query not found');

        // Verify that we are NOT selecting *
        $this->assertStringNotContainsString('select *', $certificateQuery['query']);

        // Verify that we ARE selecting specific columns
        $this->assertStringContainsString('select "id", "unique_id", "recipient_name"', $certificateQuery['query']);
        $this->assertStringContainsString('"file_path"', $certificateQuery['query']);

        // Verify that we are NOT selecting data
        $this->assertStringNotContainsString('"data"', $certificateQuery['query']);
    }
}
