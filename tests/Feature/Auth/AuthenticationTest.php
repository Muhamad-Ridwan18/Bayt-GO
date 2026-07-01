<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_email_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $otp = '123456';

        Cache::put('login_otp_code:'.strtolower($user->email), hash('sha256', $otp), now()->addMinutes(10));

        $response = $this->post('/login', [
            'email' => $user->email,
            'otp' => $otp,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_otp(): void
    {
        $user = User::factory()->create();

        Cache::put('login_otp_code:'.strtolower($user->email), hash('sha256', '123456'), now()->addMinutes(10));

        $this->post('/login', [
            'email' => $user->email,
            'otp' => '000000',
        ]);

        $this->assertGuest();
    }

    public function test_login_otp_is_sent_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/login/otp/send', [
            'email' => $user->email,
        ]);

        $response->assertOk();
        Mail::assertSent(LoginOtpMail::class, fn (LoginOtpMail $mail) => $mail->hasTo($user->email));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
