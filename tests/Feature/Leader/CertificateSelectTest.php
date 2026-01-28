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

    public function test_certificate_index_selects_specific_columns()
    {
        $user = User::factory()->create();
        $template = CertificateTemplate::factory()->create(['user_id' => $user->id]);
        Certificate::factory()->create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
        ]);

        $this->actingAs($user);

        DB::enableQueryLog();

        $response = $this->get(route('dashboard.certificates.index'));

        $response->assertStatus(200);

        $log = DB::getQueryLog();

        // Find the query related to certificates pagination
        // It should be the one selecting from certificates table, but NOT the count(*) one
        $certificateQuery = collect($log)->first(function ($query) {
            $isCertTable = str_contains($query['query'], 'from "certificates"') || str_contains($query['query'], 'from `certificates`');
            $isNotCount = !str_contains($query['query'], 'count(*)');
            return $isCertTable && $isNotCount;
        });

        $this->assertNotNull($certificateQuery, 'Certificate query not found in query log');

        $sql = $certificateQuery['query'];

        // Assert that specific columns are selected
        $expectedColumns = [
            'id',
            'user_id',
            'recipient_name',
            'recipient_email',
            'event_title',
            'event_type',
            'state',
            'status',
            'issue_date',
            'revoked_at',
            'revocation_reason',
            'created_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertStringContainsString($column, $sql, "Query does not select column: $column");
        }

        // Assert that 'data' column is NOT selected (implicit by not being * and assuming we list explicit columns)
        // But if it was select *, assertions above would pass because specific columns are present in *? No, * is just *.
        // Unless logic expands *. But usually SQL log shows select *

        $this->assertStringNotContainsString('select *', $sql, 'Query should not use SELECT *');

        // Also explicitly check that 'data' is not mentioned in the select part
        // This is tricky if data is part of a where clause, but here it shouldn't be.
        // But the safest bet is checking for "data" string.
        // However, if we simply avoid select *, that is enough proof of optimization.
        // But let's check that we aren't selecting "data" explicitly either.

        // We can't easily parse SQL here, but if we ensure "data" is not in the string, that's good.
        // BUT, if we have a where clause (unlikely here) it might appear.
        // In index() we just do latest()->paginate(), so no where on 'data'.
        $this->assertStringNotContainsString('data', $sql, 'Query should not select the data column');
    }
}
