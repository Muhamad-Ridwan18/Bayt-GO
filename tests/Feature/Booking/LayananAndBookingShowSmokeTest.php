<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\MuthowifServiceType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\MuthowifBooking;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\User;
use App\ViewModels\Booking\BookingShowPageData;
use App\ViewModels\Layanan\LayananShowPageData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class LayananAndBookingShowSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{profile: MuthowifProfile, customer: User} */
    private function approvedMarketplaceProfile(): array
    {
        $muthowifUser = User::factory()->create([
            'role' => UserRole::Muthowif,
            'name' => 'Ustadz Smoke Test',
        ]);

        $profile = MuthowifProfile::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $muthowifUser->id,
            'phone' => '081234567890',
            'address' => 'Makkah',
            'nik' => '1234567890123456',
            'birth_date' => '1990-01-01',
            'photo_path' => 'photos/test.jpg',
            'ktp_image_path' => 'ktp/test.jpg',
            'verification_status' => 'approved',
            'wallet_balance' => 0,
            'languages' => ['Bahasa Indonesia', 'Arab'],
            'work_experiences' => ['5 tahun pengalaman'],
        ]);

        MuthowifService::query()->create([
            'muthowif_profile_id' => $profile->id,
            'type' => MuthowifServiceType::Group,
            'name' => 'Paket Group',
            'daily_price' => 500000,
            'min_pilgrims' => 2,
            'max_pilgrims' => 10,
        ]);

        MuthowifService::query()->create([
            'muthowif_profile_id' => $profile->id,
            'type' => MuthowifServiceType::PrivateJamaah,
            'name' => 'Paket Private',
            'daily_price' => 750000,
            'min_pilgrims' => 1,
            'max_pilgrims' => 4,
        ]);

        $customer = User::factory()->create(['role' => UserRole::Customer]);

        return ['profile' => $profile->fresh(['user', 'services.addOns']), 'customer' => $customer];
    }

    public function test_layanan_show_page_renders(): void
    {
        ['profile' => $profile] = $this->approvedMarketplaceProfile();

        $response = $this->get(route('layanan.show', $profile));

        $response->assertOk();
        $response->assertSee('Ustadz Smoke Test', false);
        $response->assertSee('Packages', false);
    }

    public function test_layanan_show_page_data_builds_without_error(): void
    {
        ['profile' => $profile] = $this->approvedMarketplaceProfile();

        $page = LayananShowPageData::make(
            Request::create('/layanan/'.$profile->slug, 'GET', [
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeeks(2)->toDateString(),
            ]),
            $profile,
            ['can_submit' => false, 'reason' => 'guest', 'start' => null, 'end' => null],
            now()->addWeek()->toDateString(),
            now()->addWeeks(2)->toDateString(),
        );

        $this->assertSame('Ustadz Smoke Test', $page->muthowifName);
        $this->assertTrue($page->canBook === false);
        $this->assertNotEmpty($page->seoTitle);
    }

    public function test_layanan_book_page_renders_for_customer(): void
    {
        ['profile' => $profile, 'customer' => $customer] = $this->approvedMarketplaceProfile();

        $start = now()->addWeek()->toDateString();
        $end = now()->addWeeks(2)->toDateString();

        $response = $this->actingAs($customer)->get(route('layanan.book', [
            'publicProfile' => $profile,
            'start_date' => $start,
            'end_date' => $end,
        ]));

        $response->assertOk();
        $response->assertSee('Ustadz Smoke Test', false);
    }

    public function test_layanan_index_page_renders_without_search(): void
    {
        $response = $this->get(route('layanan.index'));

        $response->assertOk();
        $response->assertSee(__('layanan.hero_title'), false);
    }

    public function test_customer_booking_index_renders(): void
    {
        ['profile' => $profile, 'customer' => $customer] = $this->approvedMarketplaceProfile();

        MuthowifBooking::query()->create([
            'booking_code' => 'BG-INDEX-1',
            'muthowif_profile_id' => $profile->id,
            'customer_id' => $customer->id,
            'service_type' => MuthowifServiceType::PrivateJamaah,
            'pilgrim_count' => 2,
            'starts_on' => now()->addWeek()->toDateString(),
            'ends_on' => now()->addWeeks(2)->toDateString(),
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'daily_price_snapshot' => 750000,
            'total_amount' => 1_500_000,
        ]);

        $response = $this->actingAs($customer)->get(route('bookings.index'));

        $response->assertOk();
        $response->assertSee('BG-INDEX-1', false);
        $response->assertSee('Ustadz Smoke Test', false);
    }

    public function test_muthowif_booking_index_renders(): void
    {
        ['profile' => $profile, 'customer' => $customer] = $this->approvedMarketplaceProfile();
        $muthowif = $profile->user;

        MuthowifBooking::query()->create([
            'booking_code' => 'BG-MUTH-1',
            'muthowif_profile_id' => $profile->id,
            'customer_id' => $customer->id,
            'service_type' => MuthowifServiceType::PrivateJamaah,
            'pilgrim_count' => 2,
            'starts_on' => now()->addWeek()->toDateString(),
            'ends_on' => now()->addWeeks(2)->toDateString(),
            'status' => BookingStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'daily_price_snapshot' => 750000,
            'total_amount' => 1_500_000,
        ]);

        $response = $this->actingAs($muthowif)->get(route('muthowif.bookings.index'));

        $response->assertOk();
        $response->assertSee('BG-MUTH-1', false);
        $response->assertSee($customer->name, false);
    }

    public function test_customer_booking_show_and_fragment_render(): void
    {
        ['profile' => $profile, 'customer' => $customer] = $this->approvedMarketplaceProfile();

        $booking = MuthowifBooking::query()->create([
            'booking_code' => 'BG-SMOKE-1',
            'muthowif_profile_id' => $profile->id,
            'customer_id' => $customer->id,
            'service_type' => MuthowifServiceType::PrivateJamaah,
            'pilgrim_count' => 2,
            'starts_on' => now()->addWeek()->toDateString(),
            'ends_on' => now()->addWeeks(2)->toDateString(),
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'daily_price_snapshot' => 750000,
            'total_amount' => 1_500_000,
        ]);

        $show = $this->actingAs($customer)->get(route('bookings.show', $booking));
        $show->assertOk();
        $show->assertSee('BG-SMOKE-1', false);
        $show->assertSee('Ustadz Smoke Test', false);

        $fragment = $this->actingAs($customer)->get(route('bookings.show.fragment', $booking));
        $fragment->assertOk();
        $fragment->assertSee('data-live-part="main"', false);
        $fragment->assertSee('BG-SMOKE-1', false);
    }

    public function test_booking_show_page_data_computes_pricing(): void
    {
        ['profile' => $profile, 'customer' => $customer] = $this->approvedMarketplaceProfile();

        $booking = MuthowifBooking::query()->create([
            'booking_code' => 'BG-SMOKE-2',
            'muthowif_profile_id' => $profile->id,
            'customer_id' => $customer->id,
            'service_type' => MuthowifServiceType::PrivateJamaah,
            'pilgrim_count' => 2,
            'starts_on' => now()->addWeek()->toDateString(),
            'ends_on' => now()->addWeek()->toDateString(),
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'daily_price_snapshot' => 750000,
            'total_amount' => 750000,
        ]);

        $booking->load([
            'customer',
            'muthowifProfile.user',
            'muthowifProfile.services.addOns',
            'supportPackage',
            'review',
            'refundRequests',
            'rescheduleRequests',
        ]);

        $page = BookingShowPageData::make(
            Request::create('/bookings/'.$booking->id),
            $booking,
            [],
            null,
            null,
            null,
            collect(),
            null,
            false,
            null,
            collect(),
        );

        $this->assertSame(1, $page->nights);
        $this->assertSame(750000.0, $page->baseSubtotal);
        $this->assertSame('Ustadz Smoke Test', $page->muthowifName);
        $this->assertTrue($page->showsPaymentSection);
    }
}
