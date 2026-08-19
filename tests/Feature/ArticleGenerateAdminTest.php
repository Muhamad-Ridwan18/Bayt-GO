<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleGenerateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_trigger_n8n_webhook(): void
    {
        config(['services.n8n_articles.webhook_url' => 'https://n8n.example.test/webhook/baytgo-content']);

        Http::fake([
            'https://n8n.example.test/webhook/baytgo-content' => Http::response(['message' => 'Workflow was started'], 200),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.articles.generate.store'), [
                'niche' => 'Umroh',
                'keywords' => 'umroh, muthowif',
                'target_audience' => 'jamaah umroh',
                'language' => ['Indonesia', 'English'],
            ])
            ->assertRedirect(route('admin.articles.generate'))
            ->assertSessionHas('status', 'Workflow was started');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://n8n.example.test/webhook/baytgo-content'
                && $request['niche'] === 'Umroh'
                && $request['language'] === ['Indonesia', 'English'];
        });
    }
}
