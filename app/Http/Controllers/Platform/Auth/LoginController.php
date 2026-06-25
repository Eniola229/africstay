<?php

namespace App\Http\Controllers\Platform\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlatformActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Platform admin auth — entirely separate from hotel auth.
 * Guard: "platform". Table: platform_admins. No self-registration.
 */
class LoginController extends Controller
{
    public function show()
    {
        return view('platform.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $request->validate([], []); // rate limiting handled by 'throttle:5,1' on the route

        if (! Auth::guard('platform')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $admin = Auth::guard('platform')->user();

        if (! $admin->is_active) {
            Auth::guard('platform')->logout();
            throw ValidationException::withMessages([
                'email' => 'Your platform admin account has been deactivated.',
            ]);
        }

        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        PlatformActivityLog::record($admin, 'LOGIN', 'auth', 'PlatformAdmin', $admin->id, $admin->name,
            "{$admin->name} ({$admin->role}) logged into the platform admin panel.");

        return redirect()->intended(route('platform.dashboard'));
    }

    public function logout(Request $request)
    {
        $admin = Auth::guard('platform')->user();

        if ($admin) {
            PlatformActivityLog::record($admin, 'LOGOUT', 'auth', 'PlatformAdmin', $admin->id, $admin->name,
                "{$admin->name} logged out of the platform admin panel.");
        }

        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
