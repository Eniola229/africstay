<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Hotel user auth (owners + all staff). Guard: "web". Table: users.
 * Completely separate from the platform admin login — see Platform\Auth\LoginController.
 */
class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'], // email OR phone
            'password' => ['required'],
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::attempt([$loginField => $request->login, 'password' => $request->password], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => 'Your account has been deactivated. Contact your hotel owner or manager.',
            ]);
        }

        if ($user->hotel && ! $user->hotel->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => 'This hotel account is currently inactive. Contact AfricStay support.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->hotel_id) {
            ActivityLog::record($user->hotel_id, $user, 'LOGIN', 'auth', 'User', $user->id, $user->name,
                ucfirst($user->role)." {$user->name} logged in.");
        }

        // Owners mid-onboarding get bounced back into the wizard by
        // EnsureOnboardingComplete middleware on the next request.
        return redirect()->intended(route('hotel.dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user && $user->hotel_id) {
            ActivityLog::record($user->hotel_id, $user, 'LOGOUT', 'auth', 'User', $user->id, $user->name,
                ucfirst($user->role)." {$user->name} logged out.");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
