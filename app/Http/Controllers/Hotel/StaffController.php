<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Post-onboarding staff management — the onboarding wizard only invites ONE
 * staff member; this is where owners/managers invite everyone after that,
 * with the tier staff-limit actually enforced (spec note #8: enforce in
 * controllers, not just the UI).
 */
class StaffController extends Controller
{
    public function __construct(
        protected SmsService $sms,
        protected EmailService $email,
    ) {}

    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    public function index()
    {
        $hotel = $this->hotel();

        return view('hotel.staff.index', [
            'staff' => $hotel->users()->where('role', '!=', 'owner')->latest()->get(),
            'staffLimit' => $hotel->staffLimit(),
            'staffCount' => $hotel->staffCount(),
            'canInviteMore' => $hotel->canInviteMoreStaff(),
        ]);
    }

    public function invite(Request $request)
    {
        $this->authorizeManage();
        $hotel = $this->hotel();

        if (! $hotel->canInviteMoreStaff()) {
            return back()->withErrors(['role' => "Your {$hotel->tier} tier allows up to {$hotel->staffLimit()} staff logins. Upgrade to invite more."]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'in:manager,receptionist,cashier,housekeeper,room_service,accountant'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
        ]);

        if (blank($validated['email']) && blank($validated['phone'])) {
            return back()->withErrors(['email' => 'Provide at least an email or a phone number for the invite.']);
        }

        $token = Str::random(40);

        $staff = User::create([
            'hotel_id' => $hotel->id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make(Str::random(24)),
            'role' => $validated['role'],
            'is_active' => true,
            'invite_token' => $token,
            'invite_expires_at' => now()->addDays(7),
            'must_set_password' => true,
        ]);

        $inviteUrl = route('staff.invite.accept', ['token' => $token]);

        if ($staff->email) {
            $this->email->sendStaffInvite($staff->email, $hotel->name, $inviteUrl);
        }
        if ($staff->phone) {
            $this->sms->send($staff->phone, "You've been invited to join {$hotel->name} on AfricStay. Set your password: {$inviteUrl}");
        }

        ActivityLog::record($hotel->id, Auth::user(), 'INVITE_STAFF', 'staff', 'User', $staff->id, $staff->name,
            Auth::user()->name." invited {$staff->name} as {$staff->role}.");

        return back()->with('success', "Invite sent to {$staff->name}.");
    }

    public function deactivate(string $staff)
    {
        $this->authorizeManage();
        $hotel = $this->hotel();
        $staff = $hotel->users()->where('role', '!=', 'owner')->findOrFail($staff);

        $staff->update(['is_active' => false]);

        ActivityLog::record($hotel->id, Auth::user(), 'DEACTIVATE_STAFF', 'staff', 'User', $staff->id, $staff->name,
            Auth::user()->name." deactivated {$staff->name}'s account.");

        return back()->with('success', "{$staff->name}'s account has been deactivated.");
    }

    public function reactivate(string $staff)
    {
        $this->authorizeManage();
        $hotel = $this->hotel();

        if (! $hotel->canInviteMoreStaff()) {
            return back()->withErrors(['staff' => "Your {$hotel->tier} tier is at its staff limit. Upgrade to reactivate more staff."]);
        }

        $staff = $hotel->users()->where('role', '!=', 'owner')->findOrFail($staff);
        $staff->update(['is_active' => true]);

        return back()->with('success', "{$staff->name}'s account has been reactivated.");
    }

    protected function authorizeManage(): void
    {
        if (! in_array(Auth::user()->role, ['owner', 'manager'])) {
            abort(403, 'Only owners and managers can manage staff.');
        }
    }
}
