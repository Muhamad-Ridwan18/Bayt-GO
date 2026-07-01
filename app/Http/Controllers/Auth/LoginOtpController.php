<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginOtpController extends Controller
{
    public function __construct(
        private readonly LoginOtpService $loginOtp
    ) {}

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        try {
            $this->loginOtp->send($request->string('email')->toString());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => __('guest.login_otp.sent'),
        ]);
    }
}
