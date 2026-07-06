<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class PasswordResetApiController extends Controller
{
    public function __construct(
        private readonly PasswordResetOtpService $passwordResetOtp
    ) {}

    public function sendOtp(Request $request)
    {
        if (! $this->passwordResetOtp->otpEnabled()) {
            return response()->json([
                'message' => 'Reset password via WhatsApp tidak aktif.',
            ], 503);
        }

        $request->validate([
            'phone' => ['required', 'string', 'min:8', 'max:32'],
        ]);

        $payload = $this->passwordResetOtp->send($request->string('phone')->toString());

        return response()->json([
            'message' => 'Kode reset password sudah dikirim ke WhatsApp '.$payload['masked_phone'].'.',
            'reset_token' => $payload['reset_token'],
            'masked_phone' => $payload['masked_phone'],
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $normalizedPhone = $this->passwordResetOtp->consumeAndValidate(
                $request->string('token')->toString(),
                $request->string('otp')->toString()
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        $user = $this->passwordResetOtp->resolveUserByPhone($normalizedPhone);
        if ($user === null) {
            return response()->json([
                'message' => 'Akun untuk sesi reset ini tidak ditemukan.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'remember_token' => Str::random(60),
        ])->save();

        return response()->json([
            'message' => 'Password berhasil direset. Silakan masuk.',
        ]);
    }
}
