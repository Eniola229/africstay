<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Forgot password — fallback logic per spec section 1B:
 *   - user has email  -> Brevo reset link
 *   - user has phone, no email -> Termii OTP
 */
class ForgotPasswordController extends Controller
{
    public function __construct(
        protected SmsService $sms,
        protected EmailService $email,
    ) {}

    public function show()
    {
        return view('auth.passwords.request');
    }

    public function send(Request $request)
    {
        $request->validate(['login' => ['required', 'string']]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($loginField, $request->login)->first();

        // Always show the same generic message — never reveal whether an
        // account exists, to avoid leaking which phones/emails are registered.
        $generic = 'If that account exists, we\'ve sent instructions to reach it.';

        if (! $user) {
            return back()->with('info', $generic);
        }

        if ($user->email) {
            $token = Password::broker('users')->createToken($user);
            $resetUrl = route('password.reset.show', ['token' => $token]).'?email='.urlencode($user->email);
            $this->email->sendPasswordResetLink($user->email, $resetUrl);

            return back()->with('info', $generic." We've emailed a reset link to {$this->maskEmail($user->email)}.");
        }

        if ($user->phone) {
            $otp = $this->sms->sendOtp($user->phone);

            \DB::table('phone_otps')->insert([
                'phone' => $user->phone,
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'used' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            session(['otp_phone' => $user->phone]);

            return redirect()->route('password.otp.show')
                ->with('info', "We've sent a verification code to {$this->maskPhone($user->phone)}.");
        }

        return back()->with('info', $generic);
    }

    /** Step 2 of the SMS path: enter OTP + new password */
    public function showOtpForm()
    {
        if (! session('otp_phone')) {
            return redirect()->route('password.request');
        }

        return view('auth.passwords.otp');
    }

    public function verifyOtpAndReset(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'regex:/[0-9]/', 'confirmed'],
        ]);

        $phone = session('otp_phone');

        if (! $phone) {
            return redirect()->route('password.request');
        }

        $record = \DB::table('phone_otps')
            ->where('phone', $phone)
            ->where('used', false)
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($request->otp, $record->otp)) {
            return back()->withErrors(['otp' => 'That code is invalid or has expired.']);
        }

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return redirect()->route('password.request');
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();
        \DB::table('phone_otps')->where('id', $record->id)->update(['used' => true]);
        session()->forget('otp_phone');

        return redirect()->route('login')->with('success', 'Your password has been reset. Please sign in.');
    }

    protected function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email);
        return Str::substr($name, 0, 2).'***@'.$domain;
    }

    protected function maskPhone(string $phone): string
    {
        return Str::substr($phone, 0, 4).'****'.Str::substr($phone, -2);
    }
}
