<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RegistrationOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegisterOtpController extends Controller
{
    public function __construct(
        private readonly RegistrationOtpService $registrationOtp
    ) {}

    public function send(Request $request): JsonResponse
    {
        if (! $this->registrationOtp->otpEnabled()) {
            return response()->json([
                'message' => 'Verifikasi OTP WhatsApp tidak aktif. Set FONNTE_TOKEN dan FONNTE_OTP_ENABLED di .env',
            ], 503);
        }

        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'role' => ['required', Rule::in(['customer', 'muthowif'])],
        ]);

        try {
            $this->registrationOtp->send($request->string('phone')->toString());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Kode OTP dikirim ke WhatsApp Anda.',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        if (! $this->registrationOtp->otpEnabled()) {
            return response()->json([
                'message' => 'Verifikasi OTP tidak aktif.',
            ], 503);
        }

        $request->merge([
            'otp' => preg_replace('/\D+/', '', (string) $request->input('otp', '')),
        ]);

        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        try {
            $normalized = $this->registrationOtp->verify(
                $request->string('phone')->toString(),
                $request->string('otp')->toString()
            );
            $this->registrationOtp->rememberVerifiedPhone($normalized);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Nomor WhatsApp terverifikasi.',
            'verified' => true,
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $this->registrationOtp->clearVerificationSession();

        return response()->json(['ok' => true]);
    }
}
