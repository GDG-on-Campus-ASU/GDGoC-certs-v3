<?php

namespace Tests\Feature;

use App\Models\DocumentationPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function createLargeDocumentationPage($order = 0)
    {
        return DocumentationPage::create([
            'title' => 'Test Page '.$order,
            'slug' => 'test-page-'.$order,
            'content' => Str::repeat('Large Content ', 1000),
            'order' => $order,
        ]);
    }

    public function test_admin_documentation_index_selects_specific_columns()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
        $this->createLargeDocumentationPage(1);
        $this->createLargeDocumentationPage(2);

        DB::enableQueryLog();

        $response = $this->actingAs($superadmin)->get(route('admin.documentation.index'));

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $selectQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'documentation_pages') && ! str_contains($query['query'], 'limit 1');
        });

        $this->assertNotNull($selectQuery, 'No query found for documentation pages list');

        $sql = $selectQuery['query'];
        $this->assertStringNotContainsString('*', $sql, 'Query selects all columns instead of specific ones');
        $this->assertStringContainsString('id', $sql);
        $this->assertStringContainsString('title', $sql);
        $this->assertStringContainsString('slug', $sql);
        $this->assertStringContainsString('order', $sql);
        $this->assertStringNotContainsString('content', $sql, 'Query selects content column');
    }

    public function test_leader_documentation_index_selects_specific_columns()
    {
        $user = User::factory()->create(['role' => 'leader', 'status' => 'active']);
        $this->createLargeDocumentationPage(1);

        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('dashboard.documentation.index'));

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $selectQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'documentation_pages') && ! str_contains($query['query'], 'limit 1');
        });

        $this->assertNotNull($selectQuery, 'No query found for documentation pages list');
        $sql = $selectQuery['query'];

        $this->assertStringNotContainsString('*', $sql, 'Query selects all columns instead of specific ones');
        $this->assertStringNotContainsString('content', $sql);
    }

    public function test_leader_documentation_show_selects_specific_columns_for_sidebar()
    {
        $user = User::factory()->create(['role' => 'leader', 'status' => 'active']);
        $page = $this->createLargeDocumentationPage(1);
        $this->createLargeDocumentationPage(2);

        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('dashboard.documentation.show', $page));

        $response->assertStatus(200);

        $queries = DB::getQueryLog();

        // The sidebar query is `DocumentationPage::orderBy('order')->get()`.
        // It should look like `select ... from "documentation_pages" order by "order" asc`

        $sidebarQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'order by "order" asc') && ! str_contains($query['query'], 'limit 1');
        });

        $this->assertNotNull($sidebarQuery, 'Sidebar query not found');
        $sql = $sidebarQuery['query'];

        $this->assertStringNotContainsString('*', $sql, 'Sidebar query selects all columns');
        $this->assertStringNotContainsString('content', $sql, 'Sidebar query selects content');
    }
}
