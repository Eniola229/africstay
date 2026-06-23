<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 4-step onboarding wizard, run right after owner registration.
 *  Step 1: hotel details (address, city, state, phone, optional email/logo)
 *  Step 2: choose subscription tier
 *  Step 3: add rooms (skippable)
 *  Step 4: invite first staff member (optional)
 */
class OnboardingController extends Controller
{
    public function __construct(
        protected SmsService $sms,
        protected EmailService $email,
    ) {}

    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    public function show(Request $request)
    {
        $hotel = $this->hotel();

        if ($hotel->onboarding_completed) {
            return redirect()->route('hotel.dashboard');
        }

        $step = (int) $request->query('step', $hotel->onboarding_step);
        $step = max(1, min(4, $step));

        return view('onboarding.wizard', [
            'hotel' => $hotel,
            'step' => $step,
            'tiers' => $this->tierData(),
        ]);
    }

    /** Step 1 — hotel details */
    public function saveDetails(Request $request)
    {
        $hotel = $this->hotel();

        $validated = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo_url' => ['nullable', 'url'], // populated by Cloudinary widget on the frontend
        ]);

        $hotel->update([
            ...$validated,
            'logo' => $validated['logo_url'] ?? $hotel->logo,
            'onboarding_step' => 2,
        ]);

        ActivityLog::record($hotel->id, Auth::user(), 'UPDATE_HOTEL_DETAILS', 'settings', 'Hotel', $hotel->id, $hotel->name,
            'Owner completed onboarding step 1 — hotel details.');

        return redirect()->route('onboarding.show', ['step' => 2]);
    }

    /** Step 2 — choose tier */
    public function saveTier(Request $request)
    {
        $hotel = $this->hotel();

        $validated = $request->validate([
            'tier' => ['required', 'in:starter,growth,pro,enterprise'],
        ]);

        if ($validated['tier'] === 'enterprise') {
            // Enterprise is sales-assisted, not self-serve — see spec.
            return redirect()->route('onboarding.show', ['step' => 2])
                ->with('info', 'Enterprise is custom-priced. Our team will reach out after you finish setup — for now, continue with Starter.');
        }

        $fee = Hotel::TIER_FEES[$validated['tier']];

        $hotel->update(['tier' => $validated['tier'], 'onboarding_step' => 3]);

        Subscription::create([
            'hotel_id' => $hotel->id,
            'tier' => $validated['tier'],
            'monthly_fee' => $fee,
            'started_at' => now(),
            'next_billing_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        ActivityLog::record($hotel->id, Auth::user(), 'SELECT_TIER', 'settings', 'Hotel', $hotel->id, $hotel->name,
            "Owner selected the {$validated['tier']} tier during onboarding.");

        return redirect()->route('onboarding.show', ['step' => 3]);
    }

    /** Step 3 — add rooms (skippable). Lightweight quick-add, full room CRUD comes in the Room Management module. */
    public function saveRooms(Request $request)
    {
        $hotel = $this->hotel();

        if ($request->boolean('skip')) {
            $hotel->update(['onboarding_step' => 4]);
            return redirect()->route('onboarding.show', ['step' => 4]);
        }

        $validated = $request->validate([
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.room_number' => ['required', 'string', 'max:20'],
            'rooms.*.type' => ['required', 'in:standard,deluxe,suite,family'],
            'rooms.*.price_per_night' => ['required', 'numeric', 'min:0'],
        ]);

        $limit = Hotel::TIER_ROOM_LIMITS[$hotel->tier];
        if ($limit !== null && count($validated['rooms']) > $limit) {
            return back()->withErrors(['rooms' => "Your {$hotel->tier} tier allows up to {$limit} rooms."]);
        }

        foreach ($validated['rooms'] as $room) {
            \DB::table('rooms')->insert([
                'hotel_id' => $hotel->id,
                'room_number' => $room['room_number'],
                'type' => $room['type'],
                'price_per_night' => (int) round($room['price_per_night'] * 100), // kobo
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $hotel->update(['onboarding_step' => 4]);

        ActivityLog::record($hotel->id, Auth::user(), 'ADD_ROOMS', 'room', 'Hotel', $hotel->id, $hotel->name,
            'Owner added '.count($validated['rooms']).' room(s) during onboarding.');

        return redirect()->route('onboarding.show', ['step' => 4]);
    }

    /** Step 4 — invite first staff member (optional), then mark onboarding complete */
    public function saveStaff(Request $request)
    {
        $hotel = $this->hotel();

        if ($request->boolean('skip')) {
            return $this->finish($hotel);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'in:manager,receptionist,cashier,housekeeper,room_service,accountant'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
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
            'password' => Hash::make(Str::random(24)), // placeholder until they set their own
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
            "Owner invited {$staff->name} as {$staff->role} during onboarding.");

        return $this->finish($hotel);
    }

    protected function finish(Hotel $hotel)
    {
        $hotel->update(['onboarding_step' => 5, 'onboarding_completed' => true]);

        ActivityLog::record($hotel->id, Auth::user(), 'COMPLETE_ONBOARDING', 'settings', 'Hotel', $hotel->id, $hotel->name,
            'Owner completed the onboarding wizard.');

        return redirect()->route('hotel.dashboard')->with('success', 'Welcome to AfricStay! Your hotel is ready.');
    }

    protected function tierData(): array
    {
        return [
            'starter' => ['label' => 'Starter', 'price' => 20000, 'rooms' => '15 rooms', 'fee' => '1.5%'],
            'growth' => ['label' => 'Growth', 'price' => 50000, 'rooms' => '50 rooms', 'fee' => '1.0%'],
            'pro' => ['label' => 'Pro', 'price' => 80000, 'rooms' => 'Unlimited rooms', 'fee' => '0.75%'],
            'enterprise' => ['label' => 'Enterprise', 'price' => null, 'rooms' => 'Unlimited rooms & locations', 'fee' => 'Custom'],
        ];
    }
}