<?php

namespace Tests\Feature\Affiliate;

use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_as_affiliate(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user)
            ->post(route('affiliate.register'), ['code' => 'RIDWAN'])
            ->assertRedirect(route('affiliate.index'));

        $this->assertDatabaseHas('affiliates', [
            'user_id' => $user->id,
            'code' => 'RIDWAN',
            'status' => 'active',
        ]);
    }

    public function test_muthowif_can_register_as_affiliate(): void
    {
        $user = User::factory()->create(['role' => UserRole::Muthowif]);

        $this->actingAs($user)
            ->post(route('affiliate.register'), [])
            ->assertRedirect(route('affiliate.index'));

        $this->assertNotNull(Affiliate::query()->where('user_id', $user->id)->first());
    }

    public function test_admin_cannot_register_as_affiliate(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($user)
            ->post(route('affiliate.register'), ['code' => 'ADMINX'])
            ->assertForbidden();
    }

    public function test_affiliate_api_register_and_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($user)
            ->postJson('/api/affiliate/register', ['code' => 'API001'])
            ->assertCreated()
            ->assertJsonPath('affiliate.code', 'API001');

        $this->actingAs($user)
            ->getJson('/api/affiliate/dashboard')
            ->assertOk()
            ->assertJsonPath('affiliate.code', 'API001');
    }
}
