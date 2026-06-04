<?php

namespace Tests\Unit\Support;

use App\Support\BookingPaymentReturn;
use Illuminate\Http\Request;
use Tests\TestCase;

class BookingPaymentReturnTest extends TestCase
{
    public function test_detects_moota_success_return(): void
    {
        $request = Request::create('/bookings/x', 'GET', [
            'source' => 'moota',
            'status' => 'SUCCESS',
        ]);

        $this->assertTrue(BookingPaymentReturn::isAwaitingGatewayConfirmation($request));
    }

    public function test_detects_payment_success_query(): void
    {
        $request = Request::create('/bookings/x', 'GET', ['payment' => 'success']);

        $this->assertTrue(BookingPaymentReturn::isAwaitingGatewayConfirmation($request));
    }

    public function test_ignores_unrelated_query(): void
    {
        $request = Request::create('/bookings/x', 'GET', ['foo' => 'bar']);

        $this->assertFalse(BookingPaymentReturn::isAwaitingGatewayConfirmation($request));
    }
}
