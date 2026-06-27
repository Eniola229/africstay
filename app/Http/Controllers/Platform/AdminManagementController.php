<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformActivityLog;
use App\Models\PlatformAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** super_admin only — managing other platform admins and viewing the full audit trail. */
class AdminManagementController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        return view('platform.admins.index', ['admins' => PlatformAdmin::latest()->get()]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:platform_admins,email'],
            'role' => ['required', 'in:super_admin,support,finance,operations'],
            'password' => ['required', 'string', 'min:8', 'regex:/[0-9]/'],
        ]);

        $admin = PlatformAdmin::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        PlatformActivityLog::record(Auth::guard('platform')->user(), 'CREATE_PLATFORM_ADMIN', 'settings', 'PlatformAdmin', $admin->id, $admin->name,
            Auth::guard('platform')->user()->name." created a new platform admin: {$admin->name} ({$admin->role}).");

        return back()->with('success', "{$admin->name} added as {$admin->role}.");
    }

    public function changeRole(Request $request, string $admin)
    {
        $this->authorizeSuperAdmin();
        $admin = PlatformAdmin::findOrFail($admin);

        $validated = $request->validate(['role' => ['required', 'in:super_admin,support,finance,operations']]);
        $old = $admin->role;
        $admin->update(['role' => $validated['role']]);

        PlatformActivityLog::record(Auth::guard('platform')->user(), 'CHANGE_ADMIN_ROLE', 'settings', 'PlatformAdmin', $admin->id, $admin->name,
            Auth::guard('platform')->user()->name." changed {$admin->name}'s role from {$old} to {$validated['role']}.");

        return back()->with('success', 'Role updated.');
    }

    public function toggleActive(string $admin)
    {
        $this->authorizeSuperAdmin();
        $admin = PlatformAdmin::findOrFail($admin);

        if ($admin->id === Auth::guard('platform')->id()) {
            return back()->withErrors(['admin' => 'You cannot deactivate your own account.']);
        }

        $admin->update(['is_active' => ! $admin->is_active]);

        PlatformActivityLog::record(Auth::guard('platform')->user(), $admin->is_active ? 'ACTIVATE_ADMIN' : 'DEACTIVATE_ADMIN', 'settings',
            'PlatformAdmin', $admin->id, $admin->name,
            Auth::guard('platform')->user()->name.' '.($admin->is_active ? 'activated' : 'deactivated')." {$admin->name}.");

        return back()->with('success', "{$admin->name} ".($admin->is_active ? 'activated' : 'deactivated').'.');
    }

    public function activityLog(Request $request)
    {
        $this->authorizeSuperAdmin();

        return view('platform.admins.activity-log', [
            'logs' => PlatformActivityLog::with('admin')->latest()->paginate(40),
        ]);
    }

    protected function authorizeSuperAdmin(): void
    {
        if (Auth::guard('platform')->user()->role !== 'super_admin') {
            abort(403, 'Only super admins can manage platform admin accounts.');
        }
    }
}
