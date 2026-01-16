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

    public function test_index_selects_only_necessary_columns()
    {
        $user = User::factory()->create(['role' => 'leader']);
        Certificate::factory()->count(5)->create([
            'user_id' => $user->id,
            'data' => json_encode(['large' => 'payload', 'really' => 'large']),
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($user)
            ->get(route('dashboard.certificates.index'));

        $response->assertStatus(200);

        $log = DB::getQueryLog();
        // Pagination count query might be first, so we look for the select
        $selectQuery = collect($log)->first(fn ($query) => str_contains($query['query'], 'select') &&
            ! str_contains($query['query'], 'count(*)')
        );

        // We verify that specific columns are selected and 'data' is NOT selected
        // Note: The actual SQL might quote columns differently depending on driver, but usually it's "id", "user_id" etc.
        // We will check for presence of column names and absence of *

        $sql = $selectQuery['query'];

        $this->assertStringContainsString('recipient_name', $sql);
        $this->assertStringNotContainsString('data', $sql);
        $this->assertStringNotContainsString('*', $sql);
    }
}
