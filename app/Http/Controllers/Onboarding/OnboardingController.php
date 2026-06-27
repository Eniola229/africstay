<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\Room;
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
 *  Step 2: choose subscription tier + billing cycle -> redirects to PAID checkout
 *          (Flutterwave/Paystack). Steps 3 & 4 are only reachable once payment
 *          is confirmed — see EnsureSubscriptionActive middleware.
 *  Step 3: add rooms, with photos/videos (skippable)
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

        // Can't reach steps 3/4 in the wizard view without an active subscription —
        // bounce back to the plan picker (EnsureSubscriptionActive also guards this
        // at the route level, this just keeps the wizard's own step numbers sane).
        if ($step >= 3 && $hotel->subscription_status === 'pending_payment') {
            return redirect()->route('hotel.subscription.plans');
        }

        return view('onboarding.wizard', [
            'hotel' => $hotel,
            'step' => $step,
            'roomTypes' => ['standard', 'deluxe', 'suite', 'family'],
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
            'logo_url' => ['nullable', 'url'],
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

    /**
     * Step 2 — choose tier + billing cycle. This does NOT activate anything
     * itself — it hands off to the paid checkout flow. The hotel only reaches
     * step 3 after SubscriptionBillingService::confirmPayment() runs from a
     * verified webhook.
     */
    public function saveTier(Request $request)
    {
        $validated = $request->validate([
            'tier' => ['required', 'in:starter,growth,pro,enterprise'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        if ($validated['tier'] === 'enterprise') {
            $owner = Auth::user();
            $hotel = $this->hotel();

            \App\Models\EnterpriseInquiry::create([
                'contact_name' => $owner->name,
                'hotel_name' => $hotel->name,
                'email' => $owner->email,
                'phone' => $owner->phone,
                'message' => 'Selected Enterprise during onboarding.',
                'status' => 'new',
            ]);

            return redirect()->route('onboarding.show', ['step' => 2])
                ->with('info', 'Enterprise is custom-priced — our team will reach out. For now, pick Starter, Growth or Pro to get started today.');
        }

        return redirect()->route('hotel.subscription.checkout.start', [
            'tier' => $validated['tier'],
            'billing_cycle' => $validated['billing_cycle'],
        ]);
    }

    /** Step 3 — add rooms with photos/videos (skippable). */
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
            'rooms.*.max_guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'rooms.*.images' => ['nullable', 'array'],
            'rooms.*.images.*.url' => ['required_with:rooms.*.images', 'url'],
            'rooms.*.images.*.public_id' => ['nullable', 'string'],
            'rooms.*.videos' => ['nullable', 'array'],
            'rooms.*.videos.*.url' => ['required_with:rooms.*.videos', 'url'],
            'rooms.*.videos.*.public_id' => ['nullable', 'string'],
        ]);

        $limit = Hotel::TIER_ROOM_LIMITS[$hotel->tier];
        $existingCount = $hotel->rooms()->count();
        if ($limit !== null && ($existingCount + count($validated['rooms'])) > $limit) {
            return back()->withErrors(['rooms' => "Your {$hotel->tier} tier allows up to {$limit} rooms total."]);
        }

        foreach ($validated['rooms'] as $roomData) {
            $room = Room::create([
                'hotel_id' => $hotel->id,
                'room_number' => $roomData['room_number'],
                'type' => $roomData['type'],
                'price_per_night' => (int) round($roomData['price_per_night'] * 100),
                'max_guests' => $roomData['max_guests'] ?? 2,
                'status' => 'available',
            ]);

            foreach (($roomData['images'] ?? []) as $i => $image) {
                $room->media()->create([
                    'type' => 'image',
                    'url' => $image['url'],
                    'cloudinary_public_id' => $image['public_id'] ?? null,
                    'is_primary' => $i === 0,
                    'sort_order' => $i,
                ]);
            }

            foreach (($roomData['videos'] ?? []) as $i => $video) {
                $room->media()->create([
                    'type' => 'video',
                    'url' => $video['url'],
                    'cloudinary_public_id' => $video['public_id'] ?? null,
                    'sort_order' => $i,
                ]);
            }
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
}
