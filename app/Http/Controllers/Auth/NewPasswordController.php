<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetOtpService $passwordResetOtp
    ) {}

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $normalizedPhone = $this->passwordResetOtp->consumeAndValidate(
                $request->string('token')->toString(),
                $request->string('phone')->toString(),
                $request->string('otp')->toString()
            );
        } catch (ValidationException $e) {
            return back()->withInput($request->only('phone'))
                ->withErrors($e->errors());
        }

        /** @var User|null $user */
        $user = User::query()->where('phone', $normalizedPhone)->first();
        if (! $user) {
            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => 'Akun dengan nomor WhatsApp ini tidak ditemukan.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Password berhasil direset. Silakan login.');
    }
}
