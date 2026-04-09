<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        private readonly PasswordResetOtpService $passwordResetOtp
    ) {}

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $this->passwordResetOtp->otpEnabled()) {
            return back()->withErrors([
                'phone' => 'Reset password via WhatsApp tidak aktif. Periksa konfigurasi Fonnte.',
            ]);
        }

        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ]);

        try {
            $payload = $this->passwordResetOtp->send($request->string('phone')->toString());
        } catch (ValidationException $e) {
            return back()->withInput($request->only('phone'))
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('password.reset', ['token' => $payload['reset_token']])
            ->with('status', 'Kode reset password sudah dikirim ke WhatsApp '.$payload['masked_phone'].'.');
    }
}
