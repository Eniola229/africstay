<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Hotel owner self-registration → creates a Hotel + the owner's User record
 * in one transaction, logs in, and sends them into the 4-step onboarding wizard.
 */
class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'hotel_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/[0-9]/', 'confirmed'],
        ], [
            'password.regex' => 'Password must include at least one number.',
        ]);

        $hotel = DB::transaction(function () use ($validated) {
            $hotel = Hotel::create([
                'name' => $validated['hotel_name'],
                'slug' => $this->uniqueSlug($validated['hotel_name']),
                'phone' => $validated['phone'], // fallback contact until Step 1 of onboarding sets full address
                'tier' => 'starter',
                'onboarding_step' => 1,
                'onboarding_completed' => false,
            ]);

            $owner = User::create([
                'hotel_id' => $hotel->id,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'owner',
                'is_active' => true,
            ]);

            $hotel->update(['owner_id' => $owner->id]);

            ActivityLog::record($hotel->id, $owner, 'REGISTER_HOTEL', 'auth', 'Hotel', $hotel->id, $hotel->name,
                "Owner {$owner->name} registered {$hotel->name} on AfricStay.");

            return $hotel;
        });

        Auth::login($hotel->owner);

        return redirect()->route('onboarding.show', ['step' => 1]);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Hotel::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
