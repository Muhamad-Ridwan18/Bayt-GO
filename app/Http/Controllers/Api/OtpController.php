<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegistrationOtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function __construct(
        private readonly RegistrationOtpService $registrationOtp
    ) {}

    public function send(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'role' => ['required', Rule::in(['customer', 'muthowif'])],
        ]);

        try {
            $this->registrationOtp->send($request->string('phone')->toString());
            return response()->json(['message' => 'Kode OTP berhasil dikirim.']);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Gagal mengirim OTP.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        try {
            $normalized = $this->registrationOtp->verify(
                $request->string('phone')->toString(),
                $request->string('otp')->toString()
            );
            return response()->json([
                'message' => 'Verifikasi berhasil.',
                'verified' => true
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Kode OTP salah.',
            ], 422);
        }
    }
}
