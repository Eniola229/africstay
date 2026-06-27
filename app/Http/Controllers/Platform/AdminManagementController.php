<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformActivityLog;
use App\Models\PlatformAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * super_admin only — managing other platform admins and viewing the full audit trail.
 */
class AdminManagementController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        $admins = PlatformAdmin::latest()->get();

        $stats = [
            'total'       => $admins->count(),
            'active'      => $admins->where('is_active', true)->count(),
            'inactive'    => $admins->where('is_active', false)->count(),
            'by_role'     => $admins->groupBy('role')->map->count(),
        ];

        return view('platform.admins.index', compact('admins', 'stats'));
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:platform_admins,email'],
            'role'     => ['required', 'in:super_admin,support,finance,operations'],
            'password' => ['required', 'string', 'min:8', 'regex:/[0-9]/'],
        ]);

        $admin = PlatformAdmin::create([
            ...$validated,
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        PlatformActivityLog::record(
            Auth::guard('platform')->user(),
            'CREATE_PLATFORM_ADMIN',
            'settings',
            'PlatformAdmin', $admin->id, $admin->name,
            Auth::guard('platform')->user()->name . " created a new platform admin: {$admin->name} ({$admin->role})."
        );

        return back()->with('success', "{$admin->name} added as {$admin->role}.");
    }

    public function changeRole(Request $request, string $admin)
    {
        $this->authorizeSuperAdmin();
        $admin = PlatformAdmin::findOrFail($admin);

        $validated = $request->validate(['role' => ['required', 'in:super_admin,support,finance,operations']]);
        $old = $admin->role;
        $admin->update(['role' => $validated['role']]);

        PlatformActivityLog::record(
            Auth::guard('platform')->user(),
            'CHANGE_ADMIN_ROLE',
            'settings',
            'PlatformAdmin', $admin->id, $admin->name,
            Auth::guard('platform')->user()->name . " changed {$admin->name}'s role from {$old} to {$validated['role']}."
        );

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

        PlatformActivityLog::record(
            Auth::guard('platform')->user(),
            $admin->is_active ? 'ACTIVATE_ADMIN' : 'DEACTIVATE_ADMIN',
            'settings',
            'PlatformAdmin', $admin->id, $admin->name,
            Auth::guard('platform')->user()->name . ' ' . ($admin->is_active ? 'activated' : 'deactivated') . " {$admin->name}."
        );

        return back()->with('success', "{$admin->name} " . ($admin->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function activityLog(Request $request)
    {
        $this->authorizeSuperAdmin();

        $query = PlatformActivityLog::with('admin');

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }
        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->latest()->paginate(40)->withQueryString();

        // Stats for the activity log page
        $logStats = [
            'total_today'    => PlatformActivityLog::whereDate('created_at', today())->count(),
            'total_week'     => PlatformActivityLog::where('created_at', '>=', now()->subDays(7))->count(),
            'unique_admins'  => PlatformActivityLog::where('created_at', '>=', now()->subDays(7))->distinct('platform_admin_id')->count('platform_admin_id'),
            'action_types'   => PlatformActivityLog::distinct('action')->pluck('action')->sort()->values(),
        ];

        return view('platform.admins.activity-log', compact('logs', 'logStats'));
    }

    protected function authorizeSuperAdmin(): void
    {
        if (Auth::guard('platform')->user()->role !== 'super_admin') {
            abort(403, 'Only super admins can manage platform admin accounts.');
        }
    }
}