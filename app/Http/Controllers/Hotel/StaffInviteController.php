<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffInviteController extends Controller
{
    public function show(string $token)
    {
        $staff = User::where('invite_token', $token)
            ->where('invite_expires_at', '>=', now())
            ->first();

        if (! $staff) {
            return redirect()->route('login')->with('error', 'This invite link is invalid or has expired.');
        }

        return view('auth.passwords.accept-invite', ['staff' => $staff, 'token' => $token]);
    }

    public function accept(Request $request, string $token)
    {
        $staff = User::where('invite_token', $token)
            ->where('invite_expires_at', '>=', now())
            ->first();

        if (! $staff) {
            return redirect()->route('login')->with('error', 'This invite link is invalid or has expired.');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'regex:/[0-9]/', 'confirmed'],
        ]);

        $staff->forceFill([
            'password' => Hash::make($request->password),
            'invite_token' => null,
            'invite_expires_at' => null,
            'must_set_password' => false,
        ])->save();

        ActivityLog::record($staff->hotel_id, $staff, 'ACCEPT_INVITE', 'staff', 'User', $staff->id, $staff->name,
            "{$staff->name} accepted their staff invite and set a password.");

        Auth::login($staff);

        return redirect()->route('hotel.dashboard')->with('success', 'Welcome! Your account is ready.');
    }
}
