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
use App\Support\PhoneNumber;

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
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $normalizedPhone = $this->passwordResetOtp->consumeAndValidate(
                $request->string('token')->toString(),
                $request->string('otp')->toString()
            );
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors());
        }

        $local08 = str_starts_with($normalizedPhone, '62') ? '0'.substr($normalizedPhone, 2) : $normalizedPhone;
        $local8 = str_starts_with($local08, '0') ? substr($local08, 1) : $local08;
        /** @var User|null $user */
        $user = User::query()
            ->whereIn('phone', array_values(array_unique([$normalizedPhone, $local08, $local8])))
            ->first();
        if (! $user) {
            $suffix = substr($normalizedPhone, -9);
            if ($suffix !== false && $suffix !== '') {
                $candidates = User::query()->where('phone', 'like', '%'.$suffix)->limit(50)->get(['id', 'phone']);
                foreach ($candidates as $candidate) {
                    if (PhoneNumber::normalize($candidate->phone) === $normalizedPhone) {
                        $user = $candidate;
                        break;
                    }
                }
            }
        }
        if (! $user) {
            return back()->withErrors(['token' => 'Akun untuk sesi reset ini tidak ditemukan.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Password berhasil direset. Silakan login.');
    }
}
